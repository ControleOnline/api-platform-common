<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Config;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\HydratorService;
use Symfony\Component\HttpFoundation\Request;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class DiscoveryMainConfigsAction
{
  public function __construct(
    private HydratorService $hydratorService,
    private ConfigService $configService
  ) {}

  public function __invoke(Request $request): JsonResponse
  {
    try {
      $configs = $this->configService->discoveryMainConfigsFromJson(
        $request->getContent(),
        $request->headers->get('device')
      );
      return new JsonResponse($this->hydratorService->collectionData($configs, Config::class, 'config:read'));
    } catch (\InvalidArgumentException $e) {
      return new JsonResponse(['error' => $e->getMessage()], 400);
    } catch (Exception $e) {
      return new JsonResponse($this->hydratorService->error($e));
    }
  }
}
