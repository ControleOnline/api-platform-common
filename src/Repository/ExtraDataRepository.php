<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\ExtraData;
use ControleOnline\Entity\ExtraFields;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ExtraData|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExtraData|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExtraData[]    findAll()
 * @method ExtraData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExtraDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtraData::class);
    }

    public function findOneByExtraFieldsEntityNameEntityId(
        ExtraFields $extraFields,
        string $entityName,
        int|string $entityId
    ): ?ExtraData {
        $normalizedEntityName = trim($entityName);
        $normalizedEntityId = trim((string) $entityId);

        if ($extraFields->getId() <= 0 || $normalizedEntityName === '' || $normalizedEntityId === '') {
            return null;
        }

        $extraData = $this->createQueryBuilder('ed')
            ->andWhere('ed.extra_fields = :extraFields')
            ->andWhere('ed.entity_name = :entityName')
            ->andWhere('ed.entity_id = :entityId')
            ->setParameter('extraFields', $extraFields)
            ->setParameter('entityName', $normalizedEntityName)
            ->setParameter('entityId', $normalizedEntityId)
            ->orderBy('ed.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $extraData instanceof ExtraData ? $extraData : null;
    }

    public function findOneByExtraFieldsEntityNameValue(
        ExtraFields $extraFields,
        string $entityName,
        string $value
    ): ?ExtraData {
        $normalizedEntityName = trim($entityName);
        $normalizedValue = trim($value);

        if ($extraFields->getId() <= 0 || $normalizedEntityName === '' || $normalizedValue === '') {
            return null;
        }

        $extraData = $this->createQueryBuilder('ed')
            ->andWhere('ed.extra_fields = :extraFields')
            ->andWhere('ed.entity_name = :entityName')
            ->andWhere('ed.value = :value')
            ->setParameter('extraFields', $extraFields)
            ->setParameter('entityName', $normalizedEntityName)
            ->setParameter('value', $normalizedValue)
            ->orderBy('ed.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $extraData instanceof ExtraData ? $extraData : null;
    }

    /**
     * @return ExtraData[]
     */
    public function findByEntityNameEntityId(string $entityName, int|string $entityId): array
    {
        $normalizedEntityName = trim($entityName);
        $normalizedEntityId = trim((string) $entityId);

        if ($normalizedEntityName === '' || $normalizedEntityId === '') {
            return [];
        }

        return $this->createQueryBuilder('ed')
            ->andWhere('ed.entity_name = :entityName')
            ->andWhere('ed.entity_id = :entityId')
            ->setParameter('entityName', $normalizedEntityName)
            ->setParameter('entityId', $normalizedEntityId)
            ->orderBy('ed.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function upsertValue(
        ExtraFields $extraFields,
        string $entityName,
        int|string $entityId,
        string $value,
        ?string $source = null
    ): void {
        $normalizedEntityName = trim($entityName);
        $normalizedEntityId = trim((string) $entityId);
        $normalizedValue = trim($value);
        $normalizedSource = trim((string) $source);

        if (
            $extraFields->getId() <= 0
            || $normalizedEntityName === ''
            || $normalizedEntityId === ''
            || $normalizedValue === ''
        ) {
            return;
        }

        $connection = $this->getEntityManager()->getConnection();
        $table = 'extra_data';
        $lookupParameters = [
            'extra_fields_id' => $extraFields->getId(),
            'entity_id' => $normalizedEntityId,
            'entity_name' => $normalizedEntityName,
        ];

        $connection->beginTransaction();

        try {
            $existingExtraData = $connection->fetchAssociative(
                'SELECT id, data_value, source
                    FROM ' . $table . '
                    WHERE extra_fields_id = :extra_fields_id
                      AND entity_id = :entity_id
                      AND entity_name = :entity_name
                    ORDER BY id DESC
                    LIMIT 1
                    FOR UPDATE',
                $lookupParameters
            );

            if ($existingExtraData) {
                $updateParameters = [
                    'id' => (int) $existingExtraData['id'],
                    'data_value' => $normalizedValue,
                ];

                $updateSql = 'UPDATE ' . $table . ' SET data_value = :data_value';
                if ($normalizedSource !== '') {
                    $updateSql .= ', source = :source';
                    $updateParameters['source'] = $normalizedSource;
                }

                $updateSql .= ' WHERE id = :id';
                $connection->executeStatement($updateSql, $updateParameters);
            } else {
                $insertColumns = ['extra_fields_id', 'entity_id', 'entity_name', 'data_value'];
                $insertPlaceholders = [':extra_fields_id', ':entity_id', ':entity_name', ':data_value'];
                $insertParameters = [
                    'extra_fields_id' => $extraFields->getId(),
                    'entity_id' => $normalizedEntityId,
                    'entity_name' => $normalizedEntityName,
                    'data_value' => $normalizedValue,
                ];

                if ($normalizedSource !== '') {
                    $insertColumns[] = 'source';
                    $insertPlaceholders[] = ':source';
                    $insertParameters['source'] = $normalizedSource;
                }

                try {
                    $connection->executeStatement(
                        sprintf(
                            'INSERT INTO %s (%s) VALUES (%s)',
                            $table,
                            implode(', ', $insertColumns),
                            implode(', ', $insertPlaceholders)
                        ),
                        $insertParameters
                    );
                } catch (UniqueConstraintViolationException $exception) {
                    $existingExtraData = $connection->fetchAssociative(
                        'SELECT id, data_value, source
                            FROM ' . $table . '
                            WHERE extra_fields_id = :extra_fields_id
                              AND entity_id = :entity_id
                              AND entity_name = :entity_name
                            ORDER BY id DESC
                            LIMIT 1
                            FOR UPDATE',
                        $lookupParameters
                    );

                    if (!$existingExtraData) {
                        throw $exception;
                    }

                    $updateParameters = [
                        'id' => (int) $existingExtraData['id'],
                        'data_value' => $normalizedValue,
                    ];

                    $updateSql = 'UPDATE ' . $table . ' SET data_value = :data_value';
                    if ($normalizedSource !== '') {
                        $updateSql .= ', source = :source';
                        $updateParameters['source'] = $normalizedSource;
                    }

                    $updateSql .= ' WHERE id = :id';
                    $connection->executeStatement($updateSql, $updateParameters);
                }
            }

            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @return ExtraData[]
     */
    public function findMarketplaceLegacyRows(array $contexts, array $entityNames): array
    {
        $normalizedContexts = array_values(array_filter(array_map('trim', $contexts)));
        $normalizedEntityNames = array_values(array_filter(array_map(static fn ($value) => strtolower(trim((string) $value)), $entityNames)));

        if ($normalizedContexts === [] || $normalizedEntityNames === []) {
            return [];
        }

        return $this->createMarketplaceLegacyRowsQueryBuilder($normalizedContexts, $normalizedEntityNames)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return iterable<int, ExtraData>
     */
    public function iterateMarketplaceLegacyRows(array $contexts, array $entityNames): iterable
    {
        $normalizedContexts = array_values(array_filter(array_map('trim', $contexts)));
        $normalizedEntityNames = array_values(array_filter(array_map(static fn ($value) => strtolower(trim((string) $value)), $entityNames)));

        if ($normalizedContexts === [] || $normalizedEntityNames === []) {
            return [];
        }

        return $this->createMarketplaceLegacyRowsQueryBuilder($normalizedContexts, $normalizedEntityNames)
            ->getQuery()
            ->toIterable();
    }

    /**
     * @param array<int, int|string> $ids
     */
    public function deleteByIds(array $ids): int
    {
        $normalizedIds = array_values(array_unique(array_filter(array_map(
            static fn ($value): int => (int) $value,
            $ids
        ), static fn (int $value): bool => $value > 0)));

        if ($normalizedIds === []) {
            return 0;
        }

        return (int) $this->createQueryBuilder('ed')
            ->delete(ExtraData::class, 'ed')
            ->where('ed.id IN (:ids)')
            ->setParameter('ids', $normalizedIds)
            ->getQuery()
            ->execute();
    }

    private function createMarketplaceLegacyRowsQueryBuilder(array $contexts, array $entityNames): QueryBuilder
    {
        return $this->createQueryBuilder('ed')
            ->join('ed.extra_fields', 'ef')
            ->andWhere('ef.context IN (:contexts)')
            ->andWhere('LOWER(ed.entity_name) IN (:entityNames)')
            ->setParameter('contexts', $contexts)
            ->setParameter('entityNames', $entityNames)
            ->orderBy('ed.entity_name', 'ASC')
            ->addOrderBy('ed.entity_id', 'ASC')
            ->addOrderBy('ef.name', 'ASC')
            ;
    }
}
