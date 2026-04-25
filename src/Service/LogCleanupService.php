<?php

namespace ControleOnline\Service;

use Doctrine\DBAL\Connection;

class LogCleanupService
{
    public function __construct(
        private Connection $connection,
        private SystemLogConfigService $systemLogConfigService,
    ) {}

    public function cleanup(): array
    {
        $deletedByPolicy = [];

        foreach ($this->systemLogConfigService->getLogPolicy() as $policyKey => $policy) {
            $retentionDays = $policy['retentionDays'] ?? null;
            if ($retentionDays === null || !is_numeric($retentionDays) || (int) $retentionDays <= 0) {
                continue;
            }

            $deletedByPolicy[$policyKey] = $this->cleanupPolicy(
                $policyKey,
                (new \DateTimeImmutable(sprintf('-%d days', (int) $retentionDays)))
                    ->format('Y-m-d H:i:s')
            );
        }

        return [
            'deletedByPolicy' => $deletedByPolicy,
            'deletedTotal' => array_sum($deletedByPolicy),
        ];
    }

    private function cleanupPolicy(string $policyKey, string $cutoff): int
    {
        [$whereSql, $params] = $this->buildPolicyWhereClause($policyKey, $cutoff);

        return $this->connection->executeStatement(
            sprintf('DELETE FROM log WHERE created_at < :cutoff AND %s', $whereSql),
            $params
        );
    }

    private function buildPolicyWhereClause(string $policyKey, string $cutoff): array
    {
        $params = ['cutoff' => $cutoff];
        $specialChannels = $this->systemLogConfigService->getSpecialGenericChannels();

        switch ($policyKey) {
            case SystemLogConfigService::POLICY_ENTITY:
                return ['type = :type', $params + ['type' => SystemLogConfigService::POLICY_ENTITY]];

            case SystemLogConfigService::POLICY_OPERATION_PATTERNS:
                return [
                    'type = :type',
                    $params + ['type' => SystemLogConfigService::POLICY_OPERATION_PATTERNS],
                ];

            case SystemLogConfigService::POLICY_BACKEND_ERROR:
                return [
                    "type = :type AND JSON_UNQUOTE(JSON_EXTRACT(object, '$.channel')) = :channel",
                    $params + [
                        'type' => SystemLogConfigService::POLICY_GENERIC,
                        'channel' => 'backend-error',
                    ],
                ];

            case SystemLogConfigService::POLICY_FRONTEND_DEBUG:
                return [
                    "type = :type AND JSON_UNQUOTE(JSON_EXTRACT(object, '$.channel')) = :channel",
                    $params + [
                        'type' => SystemLogConfigService::POLICY_GENERIC,
                        'channel' => 'frontend-debug',
                    ],
                ];

            default:
                $channelParams = [];
                $placeholders = [];
                $index = 0;

                foreach (array_keys($specialChannels) as $channelName) {
                    $parameterName = sprintf('channel_%d', ++$index);
                    $placeholders[] = ':' . $parameterName;
                    $channelParams[$parameterName] = $channelName;
                }

                $whereSql = 'type = :type';
                if ($placeholders !== []) {
                    $whereSql .= sprintf(
                        " AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(object, '$.channel')), '') NOT IN (%s)",
                        implode(', ', $placeholders)
                    );
                }

                return [
                    $whereSql,
                    $params + ['type' => SystemLogConfigService::POLICY_GENERIC] + $channelParams,
                ];
        }
    }
}
