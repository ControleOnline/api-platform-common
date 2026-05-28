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
