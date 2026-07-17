<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\CronJob;
use ControleOnline\Repository\CronJobRepository;
use Cron\CronExpression;

class CronJobService
{
    public function __construct(
        private CronJobRepository $cronJobRepository,
        private PeopleRoleService $peopleRoleService,
    ) {}

    public function getConfiguredJobs(): array
    {
        try {
            $mainCompany = $this->peopleRoleService->getMainCompany();
        } catch (\Throwable) {
            return [];
        }

        return $this->normalizeConfiguredJobs(
            $this->cronJobRepository->findMainCompanyJobs($mainCompany)
        );
    }

    public function getConfiguredJob(string $jobKey): ?array
    {
        $normalizedKey = $this->normalizeJobKey($jobKey);
        $jobs = $this->getConfiguredJobs();

        return $jobs[$normalizedKey] ?? null;
    }

    public function normalizeConfiguredJobs(mixed $value): array
    {
        if ($value instanceof \Traversable) {
            $value = iterator_to_array($value, true);
        }

        if (!is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $jobKey => $definition) {
            $job = $this->normalizeConfiguredJob($jobKey, $definition);
            if ($job === null) {
                continue;
            }

            $normalized[$job['key']] = $job;
        }

        return $normalized;
    }

    private function normalizeConfiguredJob(mixed $jobKey, mixed $definition): ?array
    {
        if ($definition instanceof CronJob) {
            $jobKey = $definition->getJobKey();
            $cronExpression = trim((string) $definition->getCronExpression());

            return $this->buildNormalizedJob([
                'id' => $definition->getId(),
                'key' => $jobKey,
                'title' => $definition->getTitle(),
                'description' => $definition->getDescription(),
                'enabled' => $definition->isEnabled(),
                'cronExpression' => $cronExpression,
                'command' => $definition->getCommand(),
                'arguments' => $definition->getArguments(),
                'background' => $definition->isBackground(),
                'sortOrder' => $definition->getSortOrder(),
            ]);
        }

        if (!is_array($definition)) {
            return null;
        }

        $normalizedKey = $this->normalizeJobKey((string) ($definition['key'] ?? $definition['jobKey'] ?? $jobKey));
        if ($normalizedKey === '') {
            return null;
        }

        return $this->buildNormalizedJob([
            'id' => $definition['id'] ?? null,
            'key' => $normalizedKey,
            'title' => $definition['title'] ?? '',
            'description' => $definition['description'] ?? '',
            'enabled' => $definition['enabled'] ?? false,
            'cronExpression' => $definition['cronExpression'] ?? '',
            'command' => $definition['command'] ?? '',
            'arguments' => $definition['arguments'] ?? [],
            'background' => $definition['background'] ?? false,
            'sortOrder' => $definition['sortOrder'] ?? 0,
        ]);
    }

    private function buildNormalizedJob(array $definition): ?array
    {
        $normalizedKey = $this->normalizeJobKey((string) ($definition['key'] ?? ''));
        if ($normalizedKey === '') {
            return null;
        }

        $cronExpression = trim((string) ($definition['cronExpression'] ?? ''));

        return [
            'id' => isset($definition['id']) ? (int) $definition['id'] : null,
            'key' => $normalizedKey,
            'title' => trim((string) ($definition['title'] ?? '')),
            'description' => trim((string) ($definition['description'] ?? '')),
            'enabled' => $this->normalizeBoolean($definition['enabled'] ?? false),
            'cronExpression' => $cronExpression,
            'command' => trim((string) ($definition['command'] ?? '')),
            'arguments' => $this->normalizeArguments($definition['arguments'] ?? []),
            'background' => $this->normalizeBoolean($definition['background'] ?? false),
            'sortOrder' => (int) ($definition['sortOrder'] ?? 0),
            'isValid' => $this->isValidCronExpression($cronExpression),
        ];
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

    private function normalizeJobKey(string $jobKey): string
    {
        return trim($jobKey);
    }

    private function normalizeBoolean(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $normalized ?? $default;
    }

    private function normalizeArguments(mixed $arguments): array
    {
        if ($arguments instanceof \Traversable) {
            $arguments = iterator_to_array($arguments, false);
        }

        if (!is_array($arguments)) {
            $argument = trim((string) $arguments);
            if ($argument === '') {
                return [];
            }

            return array_values(array_filter(
                array_map(
                    static fn(string $item): string => trim($item),
                    preg_split('/[\r\n,|]+/', $argument) ?: [],
                ),
                static fn(string $item): bool => $item !== ''
            ));
        }

        $normalized = [];

        foreach ($arguments as $argument) {
            $normalizedArgument = trim((string) $argument);
            if ($normalizedArgument === '') {
                continue;
            }

            $normalized[] = $normalizedArgument;
        }

        return $normalized;
    }
}
