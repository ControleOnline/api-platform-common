<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ControleOnline\Entity\CronJob;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CronJobPersistProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PeopleRoleService $peopleRoleService,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof CronJob) {
            return $data;
        }

        try {
            $mainCompany = $this->peopleRoleService->getMainCompany();
        } catch (\Throwable $exception) {
            throw new AccessDeniedHttpException(
                'Nao foi possivel resolver a empresa principal para salvar o cron job.',
                $exception
            );
        }

        $data->setPeople($mainCompany);
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
