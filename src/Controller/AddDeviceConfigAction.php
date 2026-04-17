<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\DeviceConfig;
use ControleOnline\Entity\People;
use ControleOnline\Service\DeviceService;
use ControleOnline\Service\HydratorService;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AddDeviceConfigAction
{
  public function __construct(
    private EntityManagerInterface $manager,
    private HydratorService $hydratorService,
    private DeviceService $deviceService
  ) {}

  public function __invoke(Request $request): JsonResponse
  {
    try {
      $json = json_decode($request->getContent(), true) ?? [];
      $device = $this->deviceService->resolveDeviceIdentifier($request, $json);

      if ($device === '') {
        return new JsonResponse([
          'error' => 'DEVICE header or body field "device" is required.'
        ], Response::HTTP_BAD_REQUEST);
      }

      $people = $this->manager->getRepository(People::class)->find(preg_replace("/[^0-9]/", "", $json['people']));
      $configs = $this->deviceService->normalizeDeviceConfigsPayload($json['configs'] ?? []);
      $type = $this->deviceService->resolveDeviceConfigTypeFromRequest($request, $json);
      $device_config = $this->deviceService->addDeviceConfigs(
        $people,
        $configs,
        $device,
        $type
      );
      return new JsonResponse($this->hydratorService->item(DeviceConfig::class, $device_config->getId(), 'device_config:read'), Response::HTTP_OK);
    } catch (Exception $e) {
      return new JsonResponse($this->hydratorService->error($e));
    }
  }
}
