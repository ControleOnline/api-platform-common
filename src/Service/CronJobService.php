<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\CronJob;
use Cron\CronExpression;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;

class CronJobService
{
    public function __construct(
        private Connection $connection,
        private DatabaseSwitchService $databaseSwitchService,
    ) {}

    public function getConfiguredJobs(?string $serverHost = null): array
    {
        try {
            $jobs = $this->fetchConfiguredJobs($serverHost);
        } catch (\Throwable) {
            return [];
        }

        return $this->normalizeConfiguredJobs($jobs);
    }

    public function getConfiguredJob(string $jobIdentifier, ?string $serverHost = null): ?array
    {
        $normalizedIdentifier = $this->normalizeJobIdentifier($jobIdentifier);
        $jobs = $this->getConfiguredJobs($serverHost);

        if ($normalizedIdentifier !== '' && array_key_exists($normalizedIdentifier, $jobs)) {
            return $jobs[$normalizedIdentifier];
        }

        foreach ($jobs as $job) {
            if ($this->normalizeJobIdentifier((string) ($job['command'] ?? '')) === $normalizedIdentifier) {
                return $job;
            }
        }

        return null;
    }

    public function getDueJobExecutions(?\DateTimeInterface $now = null, ?string $serverHost = null): array
    {
        $now ??= new \DateTimeImmutable();
        $jobs = $this->getConfiguredJobs($serverHost);
        $executions = [];

        foreach ($jobs as $job) {
            if (!$this->isRunnableNow($job, $now)) {
                continue;
            }

            if (strtolower((string) ($job['command'] ?? '')) === 'websocket:start') {
                continue;
            }

            if (($job['scope'] ?? 'tenant') === 'master') {
                $executions[] = $job + ['domain' => null];
                continue;
            }

            foreach ($this->getTargetTenants($job) as $tenant) {
                $executions[] = $job + [
                    'databaseId' => (int) $tenant['id'],
                    'domain' => trim((string) $tenant['app_host']),
                ];
            }
        }

        return $executions;
    }

    public function recordExecution(
        array $job,
        string $status,
        \DateTimeImmutable $startedAt,
        \DateTimeImmutable $finishedAt,
        ?int $exitCode,
        array $summary = []
    ): void {
        $this->databaseSwitchService->switchBackToOriginalDatabase();

        if (!$this->tableExists('cron_job_logs')) {
            return;
        }

        $status = strtolower(trim($status));
        if (!in_array($status, ['started', 'success', 'failure', 'ignored', 'error'], true)) {
            $status = 'failure';
        }

        $jobId = $this->normalizeNullableInt($job['id'] ?? null);
        $databaseId = $this->normalizeNullableInt($job['databaseId'] ?? null);
        $serverId = $this->normalizeNullableInt($job['serverId'] ?? null);
        $message = trim((string) ($summary['message'] ?? ''));
        $output = trim((string) (($summary['output'] ?? '') . "\n" . ($summary['errorOutput'] ?? '')));
        $durationMs = max(0, ((int) $finishedAt->format('Uv')) - ((int) $startedAt->format('Uv')));

        if ($jobId !== null && $this->tableExists('cron_jobs')) {
            $this->connection->executeStatement(
                'UPDATE `cron_jobs`
                 SET `last_execution_at` = :last_execution_at,
                     `last_status` = :last_status,
                     `updated_at` = :updated_at
                 WHERE `id` = :id',
                [
                    'last_execution_at' => $finishedAt,
                    'last_status' => $status,
                    'updated_at' => $finishedAt,
                    'id' => $jobId,
                ],
                [
                    'last_execution_at' => Types::DATETIME_IMMUTABLE,
                    'last_status' => ParameterType::STRING,
                    'updated_at' => Types::DATETIME_IMMUTABLE,
                    'id' => ParameterType::INTEGER,
                ]
            );
        }

        $this->connection->executeStatement(
            'INSERT INTO `cron_job_logs` (
                `cron_job_id`,
                `database_id`,
                `server_id`,
                `status`,
                `exit_code`,
                `message`,
                `output`,
                `started_at`,
                `finished_at`,
                `duration_ms`
             ) VALUES (
                :cron_job_id,
                :database_id,
                :server_id,
                :status,
                :exit_code,
                :message,
                :output,
                :started_at,
                :finished_at,
                :duration_ms
             )',
            [
                'cron_job_id' => $jobId,
                'database_id' => $databaseId,
                'server_id' => $serverId,
                'status' => $status,
                'exit_code' => $exitCode,
                'message' => $message !== '' ? $message : null,
                'output' => $output !== '' ? mb_substr($output, 0, 60000) : null,
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'duration_ms' => $durationMs,
            ],
            [
                'cron_job_id' => $jobId === null ? ParameterType::NULL : ParameterType::INTEGER,
                'database_id' => $databaseId === null ? ParameterType::NULL : ParameterType::INTEGER,
                'server_id' => $serverId === null ? ParameterType::NULL : ParameterType::INTEGER,
                'status' => ParameterType::STRING,
                'exit_code' => $exitCode === null ? ParameterType::NULL : ParameterType::INTEGER,
                'message' => $message !== '' ? ParameterType::STRING : ParameterType::NULL,
                'output' => $output !== '' ? ParameterType::STRING : ParameterType::NULL,
                'started_at' => Types::DATETIME_IMMUTABLE,
                'finished_at' => Types::DATETIME_IMMUTABLE,
                'duration_ms' => ParameterType::INTEGER,
            ]
        );
    }

    public function cleanupExecutionLogs(int $retentionDays = 30): int
    {
        $this->databaseSwitchService->switchBackToOriginalDatabase();

        if (!$this->tableExists('cron_job_logs')) {
            return 0;
        }

        $retentionDays = max(1, $retentionDays);
        $cutoff = (new \DateTimeImmutable(sprintf('-%d days', $retentionDays)));

        return $this->connection->executeStatement(
            'DELETE FROM `cron_job_logs` WHERE `created_at` < :cutoff',
            ['cutoff' => $cutoff],
            ['cutoff' => Types::DATETIME_IMMUTABLE]
        );
    }

    public function findCronJobEntity(int $id): ?CronJob
    {
        $this->databaseSwitchService->switchBackToOriginalDatabase();

        if (!$this->tableExists('cron_jobs')) {
            return null;
        }

        $row = $this->connection->fetchAssociative(
            'SELECT
                `id`,
                `database_id`,
                `server_id`,
                `scope`,
                `title`,
                `description`,
                `enabled`,
                `cron_expression` AS `cronExpression`,
                `command`,
                `arguments`,
                `last_execution_at` AS `lastExecutionAt`,
                `last_status` AS `lastStatus`
             FROM `cron_jobs`
             WHERE `id` = :id
             LIMIT 1',
            ['id' => $id],
            ['id' => ParameterType::INTEGER]
        );

        if (!is_array($row) || $row === []) {
            return null;
        }

        return $this->buildCronJobEntity($this->buildNormalizedJob($row) ?? []);
    }

    /**
     * @return CronJob[]
     */
    public function getConfiguredJobEntities(?string $serverHost = null): array
    {
        return array_values(array_filter(array_map(
            fn(array $job): ?CronJob => $this->buildCronJobEntity($job),
            $this->getConfiguredJobs($serverHost)
        )));
    }

    public function saveCronJobEntity(CronJob $cronJob): CronJob
    {
        $this->databaseSwitchService->switchBackToOriginalDatabase();

        if (!$this->tableExists('cron_jobs')) {
            throw new \RuntimeException('Central cron_jobs table is not available.');
        }

        $payload = [
            'database_id' => $cronJob->getDatabaseId(),
            'server_id' => $cronJob->getServerId(),
            'scope' => $cronJob->getScope(),
            'title' => $cronJob->getTitle(),
            'description' => $cronJob->getDescription(),
            'enabled' => $cronJob->isEnabled() ? 1 : 0,
            'cron_expression' => $cronJob->getCronExpression(),
            'command' => $cronJob->getCommand(),
            'arguments' => json_encode($cronJob->getArguments(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
        $types = [
            'database_id' => $payload['database_id'] === null ? ParameterType::NULL : ParameterType::INTEGER,
            'server_id' => $payload['server_id'] === null ? ParameterType::NULL : ParameterType::INTEGER,
            'scope' => ParameterType::STRING,
            'title' => ParameterType::STRING,
            'description' => ParameterType::STRING,
            'enabled' => ParameterType::INTEGER,
            'cron_expression' => ParameterType::STRING,
            'command' => ParameterType::STRING,
            'arguments' => ParameterType::STRING,
        ];

        if ($cronJob->getId() > 0) {
            $this->connection->executeStatement(
                'UPDATE `cron_jobs`
                 SET `database_id` = :database_id,
                     `server_id` = :server_id,
                     `scope` = :scope,
                     `title` = :title,
                     `description` = :description,
                     `enabled` = :enabled,
                     `cron_expression` = :cron_expression,
                     `command` = :command,
                     `arguments` = :arguments,
                     `updated_at` = NOW()
                 WHERE `id` = :id',
                $payload + ['id' => $cronJob->getId()],
                $types + ['id' => ParameterType::INTEGER]
            );

            return $cronJob;
        }

        $this->connection->executeStatement(
            'INSERT INTO `cron_jobs` (
                `database_id`,
                `server_id`,
                `scope`,
                `title`,
                `description`,
                `enabled`,
                `cron_expression`,
                `command`,
                `arguments`
             ) VALUES (
                :database_id,
                :server_id,
                :scope,
                :title,
                :description,
                :enabled,
                :cron_expression,
                :command,
                :arguments
             )',
            $payload,
            $types
        );
        $cronJob->setId((int) $this->connection->lastInsertId());

        return $cronJob;
    }

    public function deleteCronJobEntity(CronJob $cronJob): void
    {
        $id = $cronJob->getId();
        if ($id <= 0) {
            return;
        }

        $this->databaseSwitchService->switchBackToOriginalDatabase();
        $this->connection->executeStatement(
            'DELETE FROM `cron_jobs` WHERE `id` = :id',
            ['id' => $id],
            ['id' => ParameterType::INTEGER]
        );
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
            $cronExpression = trim((string) $definition->getCronExpression());
            $jobIdentifier = $this->resolveJobIdentifier(
                $definition->getId(),
                $definition->getCommand(),
                ''
            );

            return $this->buildNormalizedJob([
                'id' => $definition->getId(),
                'key' => $jobIdentifier,
                'databaseId' => $definition->getDatabaseId(),
                'serverId' => $definition->getServerId(),
                'scope' => $definition->getScope(),
                'title' => $definition->getTitle(),
                'description' => $definition->getDescription(),
                'enabled' => $definition->isEnabled(),
                'cronExpression' => $cronExpression,
                'command' => $definition->getCommand(),
                'arguments' => $definition->getArguments(),
                'lastExecutionAt' => $definition->getLastExecutionAt(),
                'lastStatus' => $definition->getLastStatus(),
            ]);
        }

        if (!is_array($definition)) {
            return null;
        }

        $normalizedKey = $this->resolveJobIdentifier(
            $definition['id'] ?? null,
            (string) ($definition['command'] ?? ''),
            (string) ($definition['key'] ?? $definition['jobKey'] ?? $jobKey)
        );
        if ($normalizedKey === '') {
            return null;
        }

        return $this->buildNormalizedJob([
            'id' => $definition['id'] ?? null,
            'key' => $normalizedKey,
            'databaseId' => $definition['database_id'] ?? $definition['databaseId'] ?? null,
            'serverId' => $definition['server_id'] ?? $definition['serverId'] ?? null,
            'scope' => $definition['scope'] ?? 'tenant',
            'title' => $definition['title'] ?? '',
            'description' => $definition['description'] ?? '',
            'enabled' => $definition['enabled'] ?? false,
            'cronExpression' => $definition['cronExpression'] ?? '',
            'command' => $definition['command'] ?? '',
            'arguments' => $definition['arguments'] ?? [],
            'lastExecutionAt' => $definition['lastExecutionAt'] ?? null,
            'lastStatus' => $definition['lastStatus'] ?? null,
        ]);
    }

    private function buildNormalizedJob(array $definition): ?array
    {
        $normalizedKey = $this->normalizeJobIdentifier((string) ($definition['key'] ?? ''));
        if ($normalizedKey === '') {
            return null;
        }

        $cronExpression = trim((string) ($definition['cronExpression'] ?? ''));

        return [
            'id' => isset($definition['id']) ? (int) $definition['id'] : null,
            'key' => $normalizedKey,
            'databaseId' => $this->normalizeNullableInt($definition['databaseId'] ?? null),
            'serverId' => $this->normalizeNullableInt($definition['serverId'] ?? null),
            'scope' => $this->normalizeScope($definition['scope'] ?? 'tenant'),
            'title' => trim((string) ($definition['title'] ?? '')),
            'description' => trim((string) ($definition['description'] ?? '')),
            'enabled' => $this->normalizeBoolean($definition['enabled'] ?? false),
            'cronExpression' => $cronExpression,
            'command' => trim((string) ($definition['command'] ?? '')),
            'arguments' => $this->normalizeArguments($definition['arguments'] ?? []),
            'lastExecutionAt' => $definition['lastExecutionAt'] ?? null,
            'lastStatus' => $this->normalizeNullableText($definition['lastStatus'] ?? null),
            'isValid' => $this->isValidCronExpression($cronExpression),
        ];
    }

    private function fetchConfiguredJobs(?string $serverHost = null): array
    {
        $this->databaseSwitchService->switchBackToOriginalDatabase();

        if (!$this->tableExists('cron_jobs')) {
            return [];
        }

        $params = [];
        $types = [];
        $where = ['1 = 1'];
        $joinServers = $this->tableExists('servers');

        if ($serverHost !== null && trim($serverHost) !== '' && $joinServers) {
            $where[] = '(`cron_jobs`.`server_id` IS NULL OR `servers`.`app_host` = :server_host OR `servers`.`host` = :server_host)';
            $params['server_host'] = trim($serverHost);
            $types['server_host'] = ParameterType::STRING;
        }

        return $this->connection->executeQuery(
            sprintf(
                'SELECT
                    `cron_jobs`.`id`,
                    `cron_jobs`.`database_id`,
                    `cron_jobs`.`server_id`,
                    `cron_jobs`.`scope`,
                    `cron_jobs`.`title`,
                    `cron_jobs`.`description`,
                    `cron_jobs`.`enabled`,
                    `cron_jobs`.`cron_expression` AS `cronExpression`,
                    `cron_jobs`.`command`,
                    `cron_jobs`.`arguments`,
                    `cron_jobs`.`last_execution_at` AS `lastExecutionAt`,
                    `cron_jobs`.`last_status` AS `lastStatus`
                 FROM `cron_jobs`
                 %s
                 WHERE %s
                 ORDER BY `cron_jobs`.`id` ASC',
                $joinServers ? 'LEFT JOIN `servers` ON `servers`.`id` = `cron_jobs`.`server_id`' : '',
                implode(' AND ', $where)
            ),
            $params,
            $types
        )->fetchAllAssociative();
    }

    private function getTargetTenants(array $job): array
    {
        $this->databaseSwitchService->switchBackToOriginalDatabase();

        if (!$this->tableExists('databases')) {
            return [];
        }

        $params = [];
        $types = [];
        $where = ['TRIM(COALESCE(`app_host`, "")) <> ""'];

        $databaseId = $this->normalizeNullableInt($job['databaseId'] ?? null);
        if ($databaseId !== null) {
            $where[] = '`id` = :database_id';
            $params['database_id'] = $databaseId;
            $types['database_id'] = ParameterType::INTEGER;
        } elseif ($this->columnExists('databases', 'instalation_status')) {
            $where[] = '`instalation_status` = "installed"';
        }

        return $this->connection->executeQuery(
            sprintf(
                'SELECT `id`, `app_host`
                 FROM `databases`
                 WHERE %s
                 ORDER BY `id` ASC',
                implode(' AND ', $where)
            ),
            $params,
            $types
        )->fetchAllAssociative();
    }

    private function isRunnableNow(array $job, \DateTimeInterface $now): bool
    {
        if (!($job['enabled'] ?? false) || !($job['isValid'] ?? false)) {
            return false;
        }

        try {
            return CronExpression::factory((string) ($job['cronExpression'] ?? ''))->isDue($now);
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

    private function resolveJobIdentifier(mixed $id, string $command = '', string $fallback = ''): string
    {
        $normalizedId = filter_var($id, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
            ],
        ]);

        if (is_int($normalizedId) && $normalizedId > 0) {
            return (string) $normalizedId;
        }

        $normalizedFallback = trim($fallback);
        if ($normalizedFallback !== '') {
            return $normalizedFallback;
        }

        $normalizedCommand = trim($command);
        if ($normalizedCommand !== '') {
            return $normalizedCommand;
        }

        return '';
    }

    private function normalizeJobIdentifier(string $jobIdentifier): string
    {
        return trim($jobIdentifier);
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
        if (is_string($arguments) && str_starts_with(trim($arguments), '[')) {
            try {
                $decodedArguments = json_decode($arguments, true, 512, JSON_THROW_ON_ERROR);
                $arguments = is_array($decodedArguments) ? $decodedArguments : $arguments;
            } catch (\Throwable) {
            }
        }

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

    private function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
            ],
        ]);

        return is_int($normalized) ? $normalized : null;
    }

    private function normalizeScope(mixed $scope): string
    {
        $scope = strtolower(trim((string) $scope));

        return in_array($scope, ['master', 'tenant'], true) ? $scope : 'tenant';
    }

    private function buildCronJobEntity(array $job): ?CronJob
    {
        if ($job === []) {
            return null;
        }

        return (new CronJob())
            ->setId((int) ($job['id'] ?? 0))
            ->setDatabaseId($job['databaseId'] ?? null)
            ->setServerId($job['serverId'] ?? null)
            ->setScope((string) ($job['scope'] ?? 'tenant'))
            ->setTitle((string) ($job['title'] ?? ''))
            ->setDescription((string) ($job['description'] ?? ''))
            ->setEnabled((bool) ($job['enabled'] ?? false))
            ->setCronExpression((string) ($job['cronExpression'] ?? ''))
            ->setCommand((string) ($job['command'] ?? ''))
            ->setArguments($job['arguments'] ?? [])
            ->setLastExecutionAt($job['lastExecutionAt'] ?? null)
            ->setLastStatus($job['lastStatus'] ?? null);
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }

    private function normalizeNullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
