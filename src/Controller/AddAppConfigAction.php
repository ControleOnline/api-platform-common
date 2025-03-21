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
use Symfony\Component\Security\Core\Security;



class AddAppConfigAction
{
  public function __construct(
    private Security $security,
    private EntityManagerInterface $manager,
    private ConfigService $configService,
    private HydratorService $hydratorService
  ) {}

  public function __invoke(Request $request): JsonResponse
  {
    try {
      $json = json_decode($request->getContent(), true);
      $people = $this->manager->getRepository(People::class)->find($json['people']);
      $module = $this->manager->getRepository(Module::class)->find($json['module']);

      $config = $this->configService->addConfig(
        $people,
        $json['configKey'],
        $json['configValue'],
        $module,
        $json['visibility']
      );

      return new JsonResponse($this->hydratorService->item(Config::class, $config->getId(), "config:read"), Response::HTTP_OK);
    } catch (Exception $e) {
      return new JsonResponse($this->hydratorService->error($e));
    }
  }
}
