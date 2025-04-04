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

class DiscoveryDeviceConfigAction
{
  public function __construct(
    private EntityManagerInterface $manager,
    private HydratorService $hydratorService,
    private DeviceService $deviceService
  ) {}

  public function __invoke(Request $request): JsonResponse
  {
    try {
      $json = json_decode($request->getContent(), true);
      $device = $this->manager->getRepository(People::class)->findOneBy(['device' => $request->headers->get('device')]);
      $people = $this->manager->getRepository(People::class)->find(preg_replace("/[^0-9]/", "", $json['people']));
      $device_config = $this->deviceService->discoveryMainConfigs($device, $people);
      return new JsonResponse($this->hydratorService->item(DeviceConfig::class, $device_config->getId(), 'device_config:read'), Response::HTTP_OK);
    } catch (Exception $e) {
      return new JsonResponse($this->hydratorService->error($e));
    }
  }
}
