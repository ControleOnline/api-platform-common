<?php

namespace ControleOnline\Controller;

use ControleOnline\Library\Postalcode\Exception\InvalidParameterException;
use ControleOnline\Library\Postalcode\Exception\PostalcodeNotFoundException;
use ControleOnline\Library\Postalcode\Exception\ProviderRequestException;
use ControleOnline\Library\Postalcode\PostalcodeProviderBalancer;
use ControleOnline\Library\Utils\GMaps;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Entity\Country;
use ControleOnline\Entity\State;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Geo / postal-code helpers for address forms.
 *
 * GET /postal-codes/{cep} — centralized CEP lookup (does not persist).
 * Contract (normalized):
 *   cep, street, district, city, state, uf, country, latitude?, longitude?, provider?, map?, facade?
 *
 * app-community#283: when CEP providers omit coordinates, enrich via Google Maps geocode.
 */
#[AsController]
class AddressGeoController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route(path: '/postal-codes/{cep}', name: 'lookup_postal_code', methods: ['GET'])]
    public function lookupPostalCode(string $cep): JsonResponse
    {
        $digits = preg_replace('/\D+/', '', $cep) ?? '';
        if (strlen($digits) !== 8) {
            return new JsonResponse([
                'title' => 'Invalid CEP',
                'detail' => 'CEP must have exactly 8 digits',
                'status' => 400,
                'type' => 'invalid_cep',
            ], 400);
        }

        try {
            $balancer = new PostalcodeProviderBalancer();
            $address = $balancer->search($digits);
            $provider = $balancer->getProviderCodeName();
            if (method_exists($address, 'setProvider') && !$address->getProvider()) {
                $address->setProvider($provider);
            }

            $payload = method_exists($address, 'toArray') ? $address->toArray() : [];
            $normalized = [
                'cep' => $digits,
                'street' => $payload['street'] ?? '',
                'district' => $payload['district'] ?? '',
                'city' => $payload['city'] ?? '',
                'state' => $payload['state'] ?? ($payload['uf'] ?? ''),
                'uf' => $payload['uf'] ?? ($payload['state'] ?? ''),
                'country' => $payload['country'] ?? 'Brasil',
                'number' => $payload['number'] ?? '',
                'complement' => $payload['complement'] ?? '',
                'latitude' => $payload['latitude'] ?? null,
                'longitude' => $payload['longitude'] ?? null,
                'provider' => $payload['provider'] ?? $provider,
                'formatted' => $payload['formatted'] ?? null,
            ];

            $gmapsKey = $_ENV['GMAPS_KEY'] ?? getenv('GMAPS_KEY') ?: null;
            $normalized = $this->enrichCoordinatesFromGoogleMaps($normalized, $digits, $gmapsKey);

            $lat = $normalized['latitude'] ?? null;
            $lng = $normalized['longitude'] ?? null;
            $normalized['map'] = null;
            $normalized['facade'] = null;
            $normalized['hasMapsKey'] = (bool) $gmapsKey;

            if ($gmapsKey && $lat !== null && $lng !== null) {
                $normalized['map'] = [
                    'latitude' => (float) $lat,
                    'longitude' => (float) $lng,
                    'staticUrl' => sprintf(
                        'https://maps.googleapis.com/maps/api/staticmap?center=%s,%s&zoom=16&size=640x360&maptype=roadmap&markers=color:red%%7C%s,%s&key=%s',
                        $lat, $lng, $lat, $lng, $gmapsKey
                    ),
                ];
                $normalized['facade'] = [
                    'streetViewUrl' => sprintf(
                        'https://maps.googleapis.com/maps/api/streetview?size=640x360&location=%s,%s&fov=80&heading=0&pitch=0&key=%s',
                        $lat, $lng, $gmapsKey
                    ),
                ];
            } elseif ($gmapsKey) {
                $q = urlencode(trim(sprintf(
                    '%s, %s, %s, %s, Brasil',
                    $normalized['street'],
                    $normalized['district'],
                    $normalized['city'],
                    $normalized['uf']
                )));
                if ($q !== '') {
                    $normalized['map'] = [
                        'staticUrl' => sprintf(
                            'https://maps.googleapis.com/maps/api/staticmap?center=%s&zoom=16&size=640x360&maptype=roadmap&key=%s',
                            $q, $gmapsKey
                        ),
                    ];
                }
            }

            return new JsonResponse($normalized, 200);
        } catch (InvalidParameterException $e) {
            return new JsonResponse([
                'title' => 'Invalid CEP',
                'detail' => $e->getMessage(),
                'status' => 400,
                'type' => 'invalid_cep',
            ], 400);
        } catch (PostalcodeNotFoundException $e) {
            return new JsonResponse([
                'title' => 'CEP not found',
                'detail' => $e->getMessage(),
                'status' => 404,
                'type' => 'cep_not_found',
            ], 404);
        } catch (ProviderRequestException $e) {
            return new JsonResponse([
                'title' => 'Postal code lookup unavailable',
                'detail' => 'External providers failed; fill address manually',
                'status' => 502,
                'type' => 'provider_unavailable',
            ], 502);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'title' => 'Postal code lookup failed',
                'detail' => 'Unexpected error during CEP lookup',
                'status' => 502,
                'type' => 'lookup_failed',
            ], 502);
        }
    }

    /**
     * When primary CEP providers leave lat/lng empty, geocode via Google Maps
     * so franchise/address creation persists real coordinates (app-community#283).
     */
    private function enrichCoordinatesFromGoogleMaps(array $payload, string $cepDigits, ?string $gmapsKey): array
    {
        $lat = $payload['latitude'] ?? null;
        $lng = $payload['longitude'] ?? null;
        if ($lat !== null && $lat !== '' && $lng !== null && $lng !== '') {
            return $payload;
        }
        if (!$gmapsKey) {
            return $payload;
        }

        GMaps::setKey($gmapsKey);

        $queryParts = array_filter([
            $payload['street'] ?? null,
            $payload['district'] ?? null,
            $payload['city'] ?? null,
            $payload['uf'] ?? $payload['state'] ?? null,
            $cepDigits,
            'Brasil',
        ], static fn ($v) => $v !== null && trim((string) $v) !== '');

        $query = implode(', ', $queryParts);
        if ($query === '') {
            $query = $cepDigits . ', Brasil';
        }

        $result = GMaps::geocode($query);
        if ($result === null || empty($result->results[0]->geometry->location)) {
            $result = GMaps::geocode($cepDigits . ', Brasil');
        }

        if ($result !== null && !empty($result->results[0]->geometry->location)) {
            $loc = $result->results[0]->geometry->location;
            $payload['latitude'] = isset($loc->lat) ? (float) $loc->lat : null;
            $payload['longitude'] = isset($loc->lng) ? (float) $loc->lng : null;
            if (empty($payload['provider']) || $payload['provider'] === 'postmon' || $payload['provider'] === 'viacep') {
                $payload['provider'] = ($payload['provider'] ?? 'cep') . '+googlemaps';
            }
        }

        return $payload;
    }

    #[Route(path: '/address-geo/countries', name: 'address_geo_countries', methods: ['GET'])]
    public function countries(Request $request): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        $qb = $this->em->createQueryBuilder()
            ->select('c.id, c.countrycode AS code, c.countryname AS name')
            ->from(Country::class, 'c')
            ->orderBy('c.countryname', 'ASC');
        if ($q !== '') {
            $qb->andWhere('c.countryname LIKE :q OR c.countrycode LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }
        $items = $qb->getQuery()->getArrayResult();
        return new JsonResponse(['member' => $items, 'totalItems' => count($items)]);
    }

    #[Route(path: '/address-geo/states', name: 'address_geo_states', methods: ['GET'])]
    public function states(Request $request): JsonResponse
    {
        $country = trim((string) $request->query->get('country', 'BR'));
        $qb = $this->em->createQueryBuilder()
            ->select('s.id, s.state AS name, s.uf AS uf, c.countrycode AS countryCode')
            ->from(State::class, 's')
            ->join('s.country', 'c')
            ->orderBy('s.state', 'ASC');
        if ($country !== '') {
            $qb->andWhere('c.countrycode = :cc OR c.isoalpha3 = :cc')
                ->setParameter('cc', strtoupper($country));
        }
        $items = $qb->getQuery()->getArrayResult();
        return new JsonResponse(['member' => $items, 'totalItems' => count($items)]);
    }
}
