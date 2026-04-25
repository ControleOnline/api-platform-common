<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Config;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\HydratorService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AddAppConfigsAction
{
  public function __construct(
    private ConfigService $configService,
    private HydratorService $hydratorService
  ) {}

  public function __invoke(Request $request): JsonResponse
  {
    try {
      $savedConfigs = $this->configService->addConfigsFromJson($request->getContent());
      $savedItems = array_map(
        fn (Config $config) => $this->hydratorService->item(
          Config::class,
          $config->getId(),
          'config:read'
        ),
        $savedConfigs
      );

      return new JsonResponse(
        $this->hydratorService->result($savedItems),
        Response::HTTP_OK
      );
    } catch (AccessDeniedException $e) {
      return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
    } catch (Exception $e) {
      return new JsonResponse($this->hydratorService->error($e));
    }
  }
}
