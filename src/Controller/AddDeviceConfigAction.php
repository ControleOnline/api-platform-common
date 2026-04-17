<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\DeviceConfig;
use ControleOnline\Service\DeviceService;
use ControleOnline\Service\HydratorService;
use Symfony\Component\HttpFoundation\Request;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AddDeviceConfigAction
{
  public function __construct(
    private HydratorService $hydratorService,
    private DeviceService $deviceService
  ) {}

  public function __invoke(Request $request): JsonResponse
  {
    try {
      $device_config = $this->deviceService->addDeviceConfigFromContent(
        $request,
        $request->getContent()
      );
      return new JsonResponse($this->hydratorService->item(DeviceConfig::class, $device_config->getId(), 'device_config:read'), Response::HTTP_OK);
    } catch (\InvalidArgumentException $e) {
      return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
    } catch (Exception $e) {
      return new JsonResponse($this->hydratorService->error($e));
    }
  }
}
