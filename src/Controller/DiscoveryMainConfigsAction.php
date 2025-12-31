<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\Device;
use ControleOnline\Entity\People;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\HydratorService;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class DiscoveryMainConfigsAction
{
  public function __construct(
    private EntityManagerInterface $manager,
    private HydratorService $hydratorService,
    private ConfigService $configService
  ) {}

  public function __invoke(Request $request): JsonResponse
  {
    try {
      $json = json_decode($request->getContent(), true);
      $device = $this->manager->getRepository(Device::class)->findOneBy(['device' => $request->headers->get('device')]);
      $people = $this->manager->getRepository(People::class)->find(preg_replace("/[^0-9]/", "", $json['people']));
      $configs = $this->configService->discoveryMainConfigs($people, $device);
      return new JsonResponse($this->hydratorService->collectionData($configs, Config::class, 'config:read'));
    } catch (Exception $e) {
      return new JsonResponse($this->hydratorService->error($e));
    }
  }
}
