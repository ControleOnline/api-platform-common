<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\State\ProcessorInterface;
use ControleOnline\Entity\CronJob;
use ControleOnline\Service\CronJobService;

class CronJobPersistProcessor implements ProcessorInterface
{
    public function __construct(
        private CronJobService $cronJobService,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof CronJob) {
            return $data;
        }

        if ($operation instanceof DeleteOperationInterface) {
            $this->cronJobService->deleteCronJobEntity($data);

            return null;
        }

        return $this->cronJobService->saveCronJobEntity($data);
    }
}
