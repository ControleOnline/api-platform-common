<?php

namespace ControleOnline\Service;

class CleanupLogsMaintenanceRoutine implements MaintenanceRoutineHandlerInterface
{
    public function __construct(
        private LogCleanupService $logCleanupService,
    ) {}

    public function getDefinition(): array
    {
        return [
            'key' => MaintenanceRoutineService::ROUTINE_CLEANUP_LOGS,
            'title' => 'Limpeza de logs',
            'description' => 'Remove logs expirados conforme a politica configurada.',
            'defaultEnabled' => true,
            'defaultCronExpression' => '* * * * *',
        ];
    }

    public function run(): array
    {
        return [
            'key' => MaintenanceRoutineService::ROUTINE_CLEANUP_LOGS,
            'status' => 'success',
            'summary' => $this->logCleanupService->cleanup(),
        ];
    }
}
