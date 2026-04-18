<?php

namespace ControleOnline\Listener;

use ControleOnline\Entity\Log;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LogListener
{
    private array $pendingLogs = [];
    private bool $writingLogs = false;

    public function __construct(private TokenStorageInterface $tokenStorage) {}

    public function onFlush(OnFlushEventArgs $event): void
    {
        if ($this->writingLogs) {
            return;
        }

        $em = $event->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->queueLog(
                $em,
                $entity,
                'insert',
                $this->extractEntityState($entity, $em),
                true
            );
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->queueLog(
                $em,
                $entity,
                'update',
                $uow->getEntityChangeSet($entity)
            );
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $changes = $uow->getOriginalEntityData($entity);
            if ($changes === []) {
                $changes = $this->extractEntityState($entity, $em);
            }

            $this->queueLog($em, $entity, 'delete', $changes);
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if ($this->writingLogs || $this->pendingLogs === []) {
            return;
        }

        $this->writingLogs = true;

        try {
            $this->writeLogs($event->getObjectManager());
        } finally {
            $this->writingLogs = false;
        }
    }

    private function queueLog(
        EntityManagerInterface $em,
        object $entity,
        string $action,
        array $changes,
        bool $resolveRowAfterFlush = false
    ): void {
        if ($entity instanceof Log) {
            return;
        }

        if ($action === 'update' && $changes === []) {
            return;
        }

        $metadata = $em->getClassMetadata($entity::class);

        $this->pendingLogs[] = [
            'action' => $action,
            'class' => $metadata->getName(),
            'object' => $this->normalizeChanges($changes),
            'row' => $resolveRowAfterFlush ? null : $this->resolveRowId($entity, $metadata),
            'entity' => $resolveRowAfterFlush ? $entity : null,
            'metadata' => $resolveRowAfterFlush ? $metadata : null,
        ];
    }

    private function writeLogs(EntityManagerInterface $em): void
    {
        $logs = $this->pendingLogs;
        $this->pendingLogs = [];

        $userId = $this->resolveCurrentUserId();
        $connection = $em->getConnection();

        foreach ($logs as $logData) {
            $rowId = $logData['row'];
            $object = $logData['object'];

            if (
                $rowId === null
                && isset($logData['entity'], $logData['metadata'])
                && is_object($logData['entity'])
                && $logData['metadata'] instanceof ClassMetadata
            ) {
                $rowId = $this->resolveRowId($logData['entity'], $logData['metadata']);
                $object = $this->appendResolvedIdentifier($object, $rowId, $logData['metadata']);
            }

            if ($rowId === null) {
                continue;
            }

            $connection->insert('log', [
                'type' => 'entity',
                'action' => $logData['action'],
                'class' => $logData['class'],
                'object' => json_encode(
                    $object,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
                ),
                'user_id' => $userId,
                'row' => $rowId,
            ]);
        }
    }

    private function extractEntityState(object $entity, EntityManagerInterface $em): array
    {
        $metadata = $em->getClassMetadata($entity::class);
        $state = [];

        foreach ($metadata->getFieldNames() as $fieldName) {
            $state[$fieldName] = $metadata->getFieldValue($entity, $fieldName);
        }

        foreach ($metadata->getAssociationNames() as $associationName) {
            if ($metadata->isCollectionValuedAssociation($associationName)) {
                continue;
            }

            $state[$associationName] = $metadata->getFieldValue($entity, $associationName);
        }

        return $state;
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

    private function resolveRowId(object $entity, ClassMetadata $metadata): ?int
    {
        $identifierValues = $metadata->getIdentifierValues($entity);
        if (count($identifierValues) !== 1) {
            return null;
        }

        $id = array_values($identifierValues)[0];
        if ($id === null || $id === '') {
            return null;
        }

        return is_int($id) ? $id : (is_numeric($id) ? (int) $id : null);
    }

    private function appendResolvedIdentifier(array $object, ?int $rowId, ClassMetadata $metadata): array
    {
        if ($rowId === null) {
            return $object;
        }

        $identifierFields = $metadata->getIdentifierFieldNames();
        if (count($identifierFields) !== 1) {
            return $object;
        }

        $identifierField = $identifierFields[0];
        if (($object[$identifierField] ?? null) !== null) {
            return $object;
        }

        $object[$identifierField] = $rowId;

        return $object;
    }

    private function normalizeChanges(array $changes): array
    {
        $normalized = [];

        foreach ($changes as $field => $value) {
            if (
                is_array($value)
                && count($value) === 2
                && array_key_exists(0, $value)
                && array_key_exists(1, $value)
            ) {
                $normalized[$field] = [
                    $this->normalizeValue($value[0]),
                    $this->normalizeValue($value[1]),
                ];
                continue;
            }

            $normalized[$field] = $this->normalizeValue($value);
        }

        return $normalized;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeValue($item);
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

        if (is_object($value)) {
            if (method_exists($value, 'getId')) {
                $entityId = $value->getId();
                return sprintf('%s#%s', $value::class, $entityId ?? 'null');
            }

            return $value::class;
        }

        return $value;
    }
}
