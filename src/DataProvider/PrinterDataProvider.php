<?php

namespace ControleOnline\DataProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ControleOnline\Entity\People;
use ControleOnline\Service\DeviceService;
use ControleOnline\Service\PeopleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
as Security;
use ControleOnline\Entity\Device;
use ControleOnline\Service\HydratorService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PrinterDataProvider implements ProviderInterface
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private HydratorService $hydratorService,
        private Security $security,
        private DeviceService $deviceService,
        private PeopleService $peopleService

    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        try {
            $token = $this->security->getToken();
            $currentUser = $token?->getUser();
            if (!is_object($currentUser)) {
                return new JsonResponse(
                    $this->hydratorService->error(new Exception('Authentication required')),
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $filters = $context['filters'] ?? [];
            $peopleId = preg_replace("/[^0-9]/", "", (string) ($filters['people'] ?? ''));
            $people = $peopleId
                ? $this->entityManager->getRepository(People::class)->find($peopleId)
                : null;
            $myCompanies = array_map(
                fn($company) => $company->getId(),
                $this->peopleService->getMyCompanies()
            );

            if (!$people || !in_array($people->getId(), $myCompanies, true)) {
                return new JsonResponse(
                    $this->hydratorService->error(new Exception('Company access denied')),
                    Response::HTTP_FORBIDDEN
                );
            }

            $printers = $this->deviceService->getPrinters($people);
            return new JsonResponse(
                $this->hydratorService->collectionData(
                    $printers,
                    Device::class,
                    'device:read',
                    [],
                    count($printers)
                )
            );
        } catch (Throwable $e) {
            $exception = $e instanceof Exception
                ? $e
                : new Exception($e->getMessage(), (int) $e->getCode(), $e);

            return new JsonResponse(
                $this->hydratorService->error($exception),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
