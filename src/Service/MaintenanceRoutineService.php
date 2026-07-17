<?php

namespace ControleOnline\Service;

use Cron\CronExpression;

class MaintenanceRoutineService
{
    public const ROUTINES_CONFIG_KEY = 'maintenance-routines';
    public const ROUTINE_CLEANUP_LOGS = 'cleanup_logs';
    public const ROUTINE_CLEANUP_EPHEMERAL_INTEGRATIONS = 'cleanup_ephemeral_integrations';

    public function __construct(
        private ConfigService $configService,
        private PeopleRoleService $peopleRoleService,
        private LogCleanupService $logCleanupService,
        private ?IntegrationService $integrationService = null,
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
