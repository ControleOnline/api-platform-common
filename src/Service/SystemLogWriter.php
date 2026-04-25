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
        $normalizedType = strtolower(trim($type)) ?: SystemLogConfigService::POLICY_GENERIC;
        $normalizedAction = strtolower(trim($action)) ?: 'info';
        $normalizedChannel = trim((string) ($channel ?? ($payload['channel'] ?? '')));
        $resolvedChannel = $normalizedChannel !== '' ? $normalizedChannel : null;

        if (!$this->systemLogConfigService->shouldPersist($normalizedType, $resolvedChannel)) {
            return false;
        }

        if ($resolvedChannel !== null && !isset($payload['channel'])) {
            $payload['channel'] = $resolvedChannel;
        }

        $this->connection->insert('log', [
            'type' => $normalizedType,
            'action' => $normalizedAction,
            'class' => $class,
            'object' => json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
            ),
            'row' => $row,
            'user_id' => $this->resolveCurrentUserId(),
        ]);

        return true;
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
