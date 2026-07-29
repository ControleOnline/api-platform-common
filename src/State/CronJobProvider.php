<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ControleOnline\Service\CronJobService;

class CronJobProvider implements ProviderInterface
{
    public function __construct(
        private CronJobService $cronJobService,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $id = (int) ($uriVariables['id'] ?? 0);

        if ($id > 0) {
            return $this->cronJobService->findCronJobEntity($id);
        }

        return $this->cronJobService->getConfiguredJobEntities();
    }
}
