<?php

namespace ControleOnline\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\AbstractLogger;
use Stringable;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DatabaseLogger extends AbstractLogger
{
    private const ENTITY_NAMESPACE_PREFIX = 'ControleOnline\\Entity\\';
    private const CONTEXT_KEY_MODIFIER_TOKENS = [
        'current',
        'existing',
        'external',
        'internal',
        'last',
        'latest',
        'local',
        'new',
        'old',
        'original',
        'previous',
        'remote',
        'source',
        'target',
    ];
    private const ENTITY_CONTEXT_KEYS = [
        'logEntity',
        'log_entity',
        'entity',
        'subject',
        'resource',
    ];
    private const ENTITY_CLASS_CONTEXT_KEYS = [
        'entityClass',
        'entity_class',
        'logClass',
        'log_class',
        'class',
    ];
    private const ENTITY_ROW_CONTEXT_KEYS = [
        'entityRow',
        'entity_row',
        'logRow',
        'log_row',
        'row',
    ];

    public function __construct(
        private Connection $connection,
        private TokenStorageInterface $tokenStorage,
        private string $channel
    ) {}

    public function log($level, Stringable|string $message, array $context = []): void
    {
        try {
            $entityReference = $this->resolveEntityReference($context);
            $payload = [
                'channel' => $this->channel,
                'level' => strtolower(trim((string) $level)) ?: 'info',
                'message' => (string) $message,
            ];

            $normalizedContext = $this->normalizeValue($context);
            if (is_array($normalizedContext) && $normalizedContext !== []) {
                $payload['context'] = $normalizedContext;
            }

            $this->connection->insert('log', [
                'type' => $entityReference ? 'entity' : 'generic',
                'action' => $payload['level'],
                'class' => $entityReference['class'] ?? null,
                'object' => json_encode(
                    $payload,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
                ),
                'row' => $entityReference['row'] ?? null,
                'user_id' => $this->resolveCurrentUserId(),
            ]);
        } catch (\Throwable) {
        }
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

    private function resolveEntityReference(array $context): ?array
    {
        foreach (self::ENTITY_CONTEXT_KEYS as $key) {
            if (!array_key_exists($key, $context)) {
                continue;
            }

            $reference = $this->extractEntityReference($context[$key]);
            if ($reference !== null) {
                return $reference;
            }
        }

        $explicitClass = $this->findContextValue($context, self::ENTITY_CLASS_CONTEXT_KEYS);
        $explicitRow = $this->findContextValue($context, self::ENTITY_ROW_CONTEXT_KEYS);

        if (is_string($explicitClass) && $explicitClass !== '' && is_numeric($explicitRow)) {
            $reference = $this->buildEntityReference($explicitClass, (int) $explicitRow);
            if ($reference !== null) {
                return $reference;
            }
        }

        return $this->searchEntityReference($context);
    }

    private function findContextValue(array $context, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $context)) {
                return $context[$key];
            }
        }

        return null;
    }

    private function searchEntityReference(mixed $value, int $depth = 0): ?array
    {
        if ($depth > 3) {
            return null;
        }

        $reference = $this->extractEntityReference($value);
        if ($reference !== null) {
            return $reference;
        }

        if (!is_array($value)) {
            return null;
        }

        foreach ($value as $key => $item) {
            $reference = $this->extractEntityReferenceFromContextEntry($key, $item);
            if ($reference !== null) {
                return $reference;
            }
        }

        foreach ($value as $item) {
            $reference = $this->searchEntityReference($item, $depth + 1);
            if ($reference !== null) {
                return $reference;
            }
        }

        return null;
    }

    private function extractEntityReference(mixed $value): ?array
    {
        if (is_object($value)) {
            if (!method_exists($value, 'getId')) {
                return null;
            }

            return $this->buildEntityReference($value::class, $value->getId());
        }

        if (is_array($value)) {
            $className = $this->findContextValue($value, self::ENTITY_CLASS_CONTEXT_KEYS);
            $rowId = $this->findContextValue($value, self::ENTITY_ROW_CONTEXT_KEYS);

            if (is_string($className) && is_numeric($rowId)) {
                return $this->buildEntityReference($className, (int) $rowId);
            }

            return null;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        if (!preg_match('/^(?:Entity:)?(ControleOnline\\\\Entity\\\\[A-Za-z0-9_\\\\]+)#(\d+)$/', $normalized, $matches)) {
            return null;
        }

        return $this->buildEntityReference($matches[1], (int) $matches[2]);
    }

    private function extractEntityReferenceFromContextEntry(mixed $key, mixed $value): ?array
    {
        if (!is_string($key) || !is_numeric($value)) {
            return null;
        }

        $entityClass = $this->resolveEntityClassFromContextKey($key);
        if ($entityClass === null) {
            return null;
        }

        return $this->buildEntityReference($entityClass, (int) $value);
    }

    private function resolveEntityClassFromContextKey(string $contextKey): ?string
    {
        $tokens = $this->tokenizeContextKey($contextKey);
        if ($tokens === []) {
            return null;
        }

        $hasIdSuffix = end($tokens) === 'id';
        if ($hasIdSuffix) {
            array_pop($tokens);
        }

        if ($tokens === [] || (!$hasIdSuffix && count($tokens) < 2)) {
            return null;
        }

        foreach ($this->candidateStartIndexes($tokens) as $startIndex) {
            $candidateClass = self::ENTITY_NAMESPACE_PREFIX . implode('', array_map(
                static fn(string $token): string => ucfirst($token),
                array_slice($tokens, $startIndex)
            ));

            if (class_exists($candidateClass)) {
                return $candidateClass;
            }
        }

        return null;
    }

    private function candidateStartIndexes(array $tokens): array
    {
        $startIndexes = range(0, count($tokens) - 1);

        if (count($tokens) > 1 && in_array($tokens[0], self::CONTEXT_KEY_MODIFIER_TOKENS, true)) {
            return array_merge(range(1, count($tokens) - 1), [0]);
        }

        return $startIndexes;
    }

    private function tokenizeContextKey(string $contextKey): array
    {
        $tokens = preg_split(
            '/[_\\-\\s]+|(?<=[a-z0-9])(?=[A-Z])/',
            trim($contextKey)
        );

        if (!is_array($tokens)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn(string $token): string => strtolower($token),
            $tokens
        )));
    }

    private function buildEntityReference(string $className, mixed $rowId): ?array
    {
        $normalizedClass = trim($className);
        if (
            $normalizedClass === ''
            || !class_exists($normalizedClass)
            || !is_numeric($rowId)
        ) {
            return null;
        }

        $resolvedClass = $normalizedClass;
        if (!str_starts_with($resolvedClass, self::ENTITY_NAMESPACE_PREFIX)) {
            foreach (class_parents($resolvedClass) as $parentClass) {
                if (str_starts_with($parentClass, self::ENTITY_NAMESPACE_PREFIX)) {
                    $resolvedClass = $parentClass;
                    break;
                }
            }
        }

        if (!str_starts_with($resolvedClass, self::ENTITY_NAMESPACE_PREFIX)) {
            return null;
        }

        $normalizedRow = (int) $rowId;
        if ($normalizedRow <= 0) {
            return null;
        }

        return [
            'class' => $resolvedClass,
            'row' => $normalizedRow,
        ];
    }

    private function normalizeValue(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 4) {
            return '[max-depth]';
        }

        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeValue($item, $depth + 1);
            }

            return $normalized;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if ($value instanceof \Throwable) {
            return [
                'class' => $value::class,
                'code' => $value->getCode(),
                'file' => $value->getFile(),
                'line' => $value->getLine(),
                'message' => $value->getMessage(),
            ];
        }

        if (is_object($value)) {
            $entityReference = $this->extractEntityReference($value);
            if ($entityReference !== null) {
                return sprintf('Entity:%s#%d', $entityReference['class'], $entityReference['row']);
            }

            if ($value instanceof Stringable) {
                return (string) $value;
            }

            return $value::class;
        }

        if (is_resource($value)) {
            return sprintf('resource:%s', get_resource_type($value));
        }

        return $value;
    }
}
