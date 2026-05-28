<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\ExtraData;
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
