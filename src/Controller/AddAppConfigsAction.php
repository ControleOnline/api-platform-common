<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\Module;
use ControleOnline\Entity\People;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\HydratorService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class AddAppConfigsAction
{
  public function __construct(
    private EntityManagerInterface $manager,
    private ConfigService $configService,
    private HydratorService $hydratorService
  ) {}

  public function __invoke(Request $request): JsonResponse
  {
    try {
      $json = json_decode($request->getContent(), true) ?? [];
      $people = $this->manager->getRepository(People::class)->find(
        preg_replace("/[^0-9]/", "", $json['people'] ?? '')
      );
      $module = $this->manager->getRepository(Module::class)->find(
        preg_replace("/[^0-9]/", "", $json['module'] ?? '')
      );
      $visibility = $json['visibility'] ?? 'public';
      $configs = is_array($json['configs'] ?? null) ? $json['configs'] : [];
      $savedItems = [];

      foreach ($configs as $configItem) {
        $configKey = $configItem['configKey'] ?? null;

        if (!$configKey) {
          continue;
        }

        $configValue = $this->normalizeConfigValue($configItem['configValue'] ?? '');
        $config = $this->configService->addConfig(
          $people,
          $configKey,
          $configValue,
          $module,
          $visibility
        );

        $savedItems[] = $this->hydratorService->item(
          Config::class,
          $config->getId(),
          'config:read'
        );
      }

      return new JsonResponse(
        $this->hydratorService->result($savedItems),
        Response::HTTP_OK
      );
    } catch (Exception $e) {
      return new JsonResponse($this->hydratorService->error($e));
    }
  }

  private function normalizeConfigValue(mixed $configValue): mixed
  {
    if (!is_string($configValue)) {
      return $configValue;
    }

    if (trim($configValue) === '') {
      return '';
    }

    try {
      return json_decode($configValue, true, 512, JSON_THROW_ON_ERROR);
    } catch (Exception $e) {
      return $configValue;
    }
  }
}
