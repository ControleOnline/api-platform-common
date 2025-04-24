<?php

namespace ControleOnline\DataProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ControleOnline\Entity\People;
use ControleOnline\Service\DeviceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
as Security;
use ControleOnline\Entity\Device;
use ControleOnline\Service\HydratorService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class PrinterDataProvider implements ProviderInterface
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private HydratorService $hydratorService,
        private Security $security,
        private DeviceService $deviceService

    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        try {
            $currentUser = $this->security->getToken()->getUser();
            if (!$currentUser && !$this->security->isGranted('ROLE_ADMIN')) {
                throw new \Exception('You should not pass!!!');
            }
            $filters = $context['filters'];
            $people = $this->entityManager->getRepository(People::class)->find($filters['people']);
            $printers = $this->deviceService->getPrinters($people);
            return new JsonResponse($this->hydratorService->collectionData($printers, Device::class, 'device:read'));
        } catch (Exception $e) {
            return new JsonResponse($this->hydratorService->error($e));
        }
    }
}
