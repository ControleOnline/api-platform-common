<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\CronJobCommandCatalogService;
use Symfony\Component\HttpFoundation\JsonResponse;

class DiscoveryCronJobCommandsAction
{
    public function __construct(
        private CronJobCommandCatalogService $cronJobCommandCatalogService,
    ) {}

    public function __invoke(): JsonResponse
    {
        $commands = $this->cronJobCommandCatalogService->getAvailableCommands();

        return new JsonResponse([
            'member' => $commands,
            'totalItems' => count($commands),
        ]);
    }
}
