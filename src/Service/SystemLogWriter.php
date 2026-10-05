<?php

namespace ControleOnline\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SystemLogWriter
{
    public function __construct(
        private Connection $connection,
        private TokenStorageInterface $tokenStorage,
        private SystemLogConfigService $systemLogConfigService,
    ) {}

    public function write(
        string $type,
        string $action,
        ?string $class = null,
        ?int $row = null,
        array $payload = [],
        ?string $channel = null,
    ): bool {
        $record = $this->prepareRecord($type, $action, $class, $row, $payload, $channel);
        if ($record === null) return false;
        $this->connection->insert('log', $record);
        return true;
    }

    /** Preserve one audit row per event while avoiding one round trip per row. */
    public function writeMany(array $records): int
    {
        $rows = [];
        foreach ($records as $record) {
            $row = $this->prepareRecord($record['type'], $record['action'],
                $record['class'] ?? null, $record['row'] ?? null,
                $record['payload'] ?? [], $record['channel'] ?? null);
            if ($row !== null) $rows[] = $row;
        }
        if ($rows === []) return 0;
        $columns = array_map($this->connection->quoteIdentifier(...), array_keys($rows[0]));
        $prefix = 'INSERT INTO ' . $this->connection->quoteIdentifier('log')
            . ' (' . implode(', ', $columns) . ') VALUES ';
        // Bound parameter counts; the caller's existing transaction is preserved.
        foreach (array_chunk($rows, 100) as $chunk) {
            $values = [];
            foreach ($chunk as $row) array_push($values, ...array_values($row));
            $this->connection->executeStatement($prefix . implode(', ',
                array_fill(0, count($chunk), '(?, ?, ?, ?, ?, ?)')), $values);
        }
        return count($rows);
    }

    private function prepareRecord(
        string $type, string $action, ?string $class, ?int $row,
        array $payload, ?string $channel,
    ): ?array {
        $normalizedType = strtolower(trim($type)) ?: SystemLogConfigService::POLICY_GENERIC;
        $normalizedAction = strtolower(trim($action)) ?: 'info';
        $normalizedChannel = trim((string) ($channel ?? ($payload['channel'] ?? '')));
        $resolvedChannel = $normalizedChannel !== '' ? $normalizedChannel : null;

        if (!$this->systemLogConfigService->shouldPersist($normalizedType, $resolvedChannel)) {
            return null;
        }

        if ($resolvedChannel !== null && !isset($payload['channel'])) {
            $payload['channel'] = $resolvedChannel;
        }

        return [
            'type' => $normalizedType,
            'action' => $normalizedAction,
            'class' => $class,
            'object' => json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
            ),
            'row' => $row,
            'user_id' => $this->resolveCurrentUserId(),
        ];
    }

    private function resolveCurrentUserId(): ?int
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        if (!is_object($user) || !method_exists($user, 'getId')) {
            return null;
        }

        $id = $user->getId();
        if ($id === null || $id === '') {
            return null;
        }

        return is_int($id) ? $id : (is_numeric($id) ? (int) $id : null);
    }
}
