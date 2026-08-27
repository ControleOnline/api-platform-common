<?php

namespace ControleOnline\Library\Postalcode;

use ControleOnline\Library\Postalcode\BrasilApi\BrasilApiServiceProvider;
use ControleOnline\Library\Postalcode\Entity\Address;
use ControleOnline\Library\Postalcode\Exception\InvalidParameterException;
use ControleOnline\Library\Postalcode\Exception\PostalcodeNotFoundException;
use ControleOnline\Library\Postalcode\Exception\ProviderRequestException;
use ControleOnline\Library\Postalcode\GoogleMaps\GoogleMapsServiceProvider;
use ControleOnline\Library\Postalcode\Postmon\PostmonServiceProvider;
use ControleOnline\Library\Postalcode\Viacep\ViacepServiceProvider;

/**
 * CEP lookup balancer.
 * Order (priority): ViaCEP → BrasilAPI → Postmon → Google Maps.
 * Postmon has been unreliable (HTTP 503); keep as tertiary fallback only.
 * Does not persist any address; pure external lookup.
 */
class PostalcodeProviderBalancer
{
  private array $providers = [
    'viacep'     => ViacepServiceProvider::class,
    'brasilapi'  => BrasilApiServiceProvider::class,
    'postmon'    => PostmonServiceProvider::class,
    'googlemaps' => GoogleMapsServiceProvider::class,
  ];

  private ?string $currentProviderKey = null;
  private $currentProvider = null;
  private array $tried = [];

  public function search(string $postalCode): Address
  {
    $postalCode = preg_replace('/\D+/', '', $postalCode) ?? '';
    if (strlen($postalCode) !== 8) {
      throw new InvalidParameterException('CEP must have exactly 8 digits');
    }

    $keys = array_keys($this->providers);
    if ($this->currentProviderKey === null) {
      $this->currentProviderKey = $keys[0];
      $this->currentProvider = new $this->providers[$this->currentProviderKey]();
      $this->tried = [];
    }

    try {
      $this->tried[] = $this->currentProviderKey;
      $address = $this->currentProvider->getAddress($postalCode);
      if (method_exists($address, 'setProvider') && !$address->getProvider()) {
        $address->setProvider($this->currentProviderKey);
      }
      return $address;
    } catch (InvalidParameterException $e) {
      throw $e;
    } catch (\Exception $e) {
      // Provider unavailable / empty response → try next
      if ($this->hasNextProvider()) {
        $this->setNextProvider();
        return $this->search($postalCode);
      }
      // All providers exhausted
      $msg = $e->getMessage();
      if ($this->looksLikeNotFound($msg)) {
        throw new PostalcodeNotFoundException(
          sprintf('CEP %s not found by providers (%s)', $postalCode, implode(',', $this->tried)),
          0,
          $e
        );
      }
      throw new ProviderRequestException(
        sprintf('Postalcode services unavailable after trying: %s. Last error: %s', implode(',', $this->tried), $msg),
        0,
        $e
      );
    }
  }

  public function getProviderCodeName(): string
  {
    return $this->currentProviderKey ?? (array_keys($this->providers)[0] ?? '');
  }

  public function getTriedProviders(): array
  {
    return $this->tried;
  }

  private function hasNextProvider(): bool
  {
    $keys = array_keys($this->providers);
    $idx = array_search($this->currentProviderKey, $keys, true);
    return $idx !== false && ($idx + 1) < count($keys);
  }

  private function setNextProvider(): void
  {
    $keys = array_keys($this->providers);
    $idx = array_search($this->currentProviderKey, $keys, true);
    $nextIdx = ($idx === false) ? 0 : $idx + 1;
    if ($nextIdx >= count($keys)) {
      throw new ProviderRequestException('Postalcode services are not available');
    }
    $this->currentProviderKey = $keys[$nextIdx];
    $this->currentProvider = new $this->providers[$this->currentProviderKey]();
  }

  private function looksLikeNotFound(string $message): bool
  {
    $m = strtolower($message);
    return str_contains($m, 'not found')
      || str_contains($m, '404')
      || str_contains($m, 'format error')
      || str_contains($m, 'empty');
  }
}
