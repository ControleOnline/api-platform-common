<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\ExtraFields;
use ControleOnline\Entity\ExtraData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ExtraFields|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExtraFields|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExtraFields[]    findAll()
 * @method ExtraFields[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExtraFieldsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtraFields::class);
    }

    /**
     * @return ExtraFields[]
     */
    public function findUnusedMarketplaceFields(array $contexts, array $fieldNames): array
    {
        $normalizedContexts = array_values(array_filter(array_map('trim', $contexts)));
        $normalizedFieldNames = array_values(array_filter(array_map('trim', $fieldNames)));

        if ($normalizedContexts === [] || $normalizedFieldNames === []) {
            return [];
        }

        return $this->createQueryBuilder('ef')
            ->leftJoin(ExtraData::class, 'ed', 'WITH', 'ed.extra_fields = ef')
            ->andWhere('ef.context IN (:contexts)')
            ->andWhere('ef.name IN (:fieldNames)')
            ->groupBy('ef.id')
            ->having('COUNT(ed.id) = 0')
            ->setParameter('contexts', $normalizedContexts)
            ->setParameter('fieldNames', $normalizedFieldNames)
            ->getQuery()
            ->getResult();
    }
}
