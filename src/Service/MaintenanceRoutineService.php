<?php

namespace ControleOnline\Service;

use Cron\CronExpression;

class MaintenanceRoutineService
{
    public const ROUTINES_CONFIG_KEY = 'maintenance-routines';
    public const ROUTINE_CLEANUP_LOGS = 'cleanup_logs';
    public const ROUTINE_CLEANUP_EPHEMERAL_INTEGRATIONS = 'cleanup_ephemeral_integrations';
    public const ROUTINE_OPEN_OVERDUE_OPPORTUNITIES = 'open_overdue_opportunities';

    public function __construct(
        private ConfigService $configService,
        private PeopleRoleService $peopleRoleService,
        private LogCleanupService $logCleanupService,
        private ?IntegrationService $integrationService = null,
        private ?OverdueOpportunityMaintenanceService $overdueOpportunityMaintenanceService = null,
    ) {}

    public function getRoutineDefinitions(): array
    {
        return [
            self::ROUTINE_CLEANUP_LOGS => [
                'key' => self::ROUTINE_CLEANUP_LOGS,
                'title' => 'Limpeza de logs',
                'description' => 'Remove logs expirados conforme a politica configurada.',
            ],
            self::ROUTINE_CLEANUP_EPHEMERAL_INTEGRATIONS => [
                'key' => self::ROUTINE_CLEANUP_EPHEMERAL_INTEGRATIONS,
                'title' => 'Limpeza de integracoes efemeras',
                'description' => 'Remove Websocket e PushNotification abertos ha mais de 24 horas.',
            ],
            self::ROUTINE_OPEN_OVERDUE_OPPORTUNITIES => [
                'key' => self::ROUTINE_OPEN_OVERDUE_OPPORTUNITIES,
                'title' => 'Oportunidades vencidas para aberto',
                'description' => 'Move oportunidades de CRM de pendente para aberto quando a data de retorno ja passou.',
            ],
        ];
    }

    public function getConfiguredRoutines(): array
    {
        try {
            $mainCompany = $this->peopleRoleService->getMainCompany();
        } catch (\Throwable) {
            return $this->normalizeConfiguredRoutines([]);
        }

        return $this->normalizeConfiguredRoutines(
            $this->configService->getConfig(
                $mainCompany,
                self::ROUTINES_CONFIG_KEY,
                true
            )
        );
    }

    public function normalizeConfiguredRoutines(mixed $value): array
    {
        $configured = is_array($value) ? $value : [];
        $normalized = [];

        foreach ($this->getRoutineDefinitions() as $routineKey => $definition) {
            $configuredRoutine = is_array($configured[$routineKey] ?? null)
                ? $configured[$routineKey]
                : [];

            $cronExpression = trim((string) ($configuredRoutine['cronExpression'] ?? ''));

            $normalized[$routineKey] = [
                'key' => $routineKey,
                'title' => $definition['title'],
                'description' => $definition['description'],
                'enabled' => array_key_exists('enabled', $configuredRoutine)
                    ? (bool) $configuredRoutine['enabled']
                    : false,
                'cronExpression' => $cronExpression,
                'isValid' => $this->isValidCronExpression($cronExpression),
            ];
        }

        return $normalized;
    }

    public function getDueRoutines(?\DateTimeImmutable $now = null): array
    {
        $referenceTime = $now ?? new \DateTimeImmutable('now');

        return array_values(array_filter(
            $this->getConfiguredRoutines(),
            fn(array $routine): bool => $this->isRoutineDue($routine, $referenceTime)
        ));
    }

    public function runRoutine(string $routineKey): array
    {
        return match ($routineKey) {
            self::ROUTINE_CLEANUP_LOGS => [
                'key' => $routineKey,
                'status' => 'success',
                'summary' => $this->logCleanupService->cleanup(),
            ],
            self::ROUTINE_CLEANUP_EPHEMERAL_INTEGRATIONS => [
                'key' => $routineKey,
                'status' => $this->integrationService instanceof IntegrationService ? 'success' : 'ignored',
                'summary' => $this->integrationService instanceof IntegrationService
                    ? $this->integrationService->cleanupExpiredEphemeralIntegrations()
                    : ['message' => 'IntegrationService indisponivel.'],
            ],
            self::ROUTINE_OPEN_OVERDUE_OPPORTUNITIES => [
                'key' => $routineKey,
                'status' => $this->overdueOpportunityMaintenanceService instanceof OverdueOpportunityMaintenanceService
                    ? 'success'
                    : 'ignored',
                'summary' => $this->overdueOpportunityMaintenanceService instanceof OverdueOpportunityMaintenanceService
                    ? $this->overdueOpportunityMaintenanceService->openPendingOpportunities()
                    : ['message' => 'OverdueOpportunityMaintenanceService indisponivel.'],
            ],
            default => [
                'key' => $routineKey,
                'status' => 'ignored',
                'summary' => ['message' => 'Rotina sem executor registrado.'],
            ],
        };
    }

    public function isRoutineDue(array $routine, \DateTimeImmutable $now): bool
    {
        if (!($routine['enabled'] ?? false)) {
            return false;
        }

        $cronExpression = trim((string) ($routine['cronExpression'] ?? ''));
        if (!$this->isValidCronExpression($cronExpression)) {
            return false;
        }

        try {
            return CronExpression::factory($cronExpression)->isDue($now);
        } catch (\Throwable) {
            return false;
        }
    }

    public function isValidCronExpression(?string $cronExpression): bool
    {
        $expression = trim((string) $cronExpression);
        if ($expression === '') {
            return false;
        }

        try {
            CronExpression::factory($expression);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
