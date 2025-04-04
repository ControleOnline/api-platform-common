<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Device;
use ControleOnline\Entity\DeviceConfig;
use ControleOnline\Entity\People;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\DeviceService;
use ControleOnline\Service\HydratorService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;



class AddDeviceConfigAction
{
  public function __construct(
    private Security $security,
    private EntityManagerInterface $manager,
    private ConfigService $configService,
    private HydratorService $hydratorService,
    private DeviceService $deviceService
  ) {}

  public function __invoke(Request $request): JsonResponse
  {
    try {
      $json = json_decode($request->getContent(), true);
      $people = $this->manager->getRepository(People::class)->find(preg_replace("/[^0-9]/", "", $json['people']));
      $configs = json_decode($json['configs'], true);
      $device_config = $this->deviceService->addDeviceConfigs($people, $configs, $json['device']);


      return new JsonResponse($this->hydratorService->item(DeviceConfig::class, $device_config->getId(), 'device_config:read'), Response::HTTP_OK);
    } catch (Exception $e) {
      return new JsonResponse($this->hydratorService->error($e));
    }
  }
}
