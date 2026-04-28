<?php

namespace ControleOnline\Service;

use Cron\CronExpression;

class MaintenanceRoutineService
{
    public const ROUTINES_CONFIG_KEY = 'maintenance-routines';
    public const ROUTINE_CLEANUP_LOGS = 'cleanup_logs';

    public function __construct(
        private ConfigService $configService,
        private PeopleRoleService $peopleRoleService,
        iterable $routineHandlers,
    ) {
        $this->routineHandlers = [];

        foreach ($routineHandlers as $handler) {
            if (!$handler instanceof MaintenanceRoutineHandlerInterface) {
                continue;
            }

            $definition = $handler->getDefinition();
            $routineKey = trim((string) ($definition['key'] ?? ''));
            if ($routineKey === '') {
                continue;
            }

            $this->routineHandlers[$routineKey] = $handler;
        }
    }

    /**
     * @var array<string, MaintenanceRoutineHandlerInterface>
     */
    private array $routineHandlers;

    public function getRoutineDefinitions(): array
    {
        $definitions = [];

        foreach ($this->routineHandlers as $routineKey => $handler) {
            $definition = $handler->getDefinition();
            $definitions[$routineKey] = [
                'key' => $routineKey,
                'title' => (string) ($definition['title'] ?? $routineKey),
                'description' => (string) ($definition['description'] ?? ''),
                'defaultEnabled' => array_key_exists('defaultEnabled', $definition)
                    ? (bool) $definition['defaultEnabled']
                    : false,
                'defaultCronExpression' => trim((string) (
                    $definition['defaultCronExpression'] ?? '* * * * *'
                )) ?: '* * * * *',
            ];
        }

        return $definitions;
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

            $cronExpression = trim((string) (
                $configuredRoutine['cronExpression']
                ?? $definition['defaultCronExpression']
            ));

            $normalized[$routineKey] = [
                'key' => $routineKey,
                'title' => $definition['title'],
                'description' => $definition['description'],
                'enabled' => array_key_exists('enabled', $configuredRoutine)
                    ? (bool) $configuredRoutine['enabled']
                    : (bool) $definition['defaultEnabled'],
                'cronExpression' => $cronExpression !== ''
                    ? $cronExpression
                    : $definition['defaultCronExpression'],
                'isValid' => $this->isValidCronExpression($cronExpression !== ''
                    ? $cronExpression
                    : $definition['defaultCronExpression']),
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
        $handler = $this->routineHandlers[$routineKey] ?? null;

        if (!$handler) {
            return [
                'key' => $routineKey,
                'status' => 'ignored',
                'summary' => ['message' => 'Rotina sem executor registrado.'],
            ];
        }

        $result = $handler->run();
        $result['key'] = trim((string) ($result['key'] ?? $routineKey));

        if ($result['key'] === '') {
            $result['key'] = $routineKey;
        }

        return $result;
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
