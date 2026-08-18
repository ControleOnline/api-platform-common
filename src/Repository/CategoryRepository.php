<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Loads the generic category graph for one tenant and context.
     *
     * Commercial consumers may project this graph afterwards, but this
     * repository deliberately has no knowledge of products or showcases.
     *
     * @return Category[]
     */
    public function findTreeCandidates(int $companyId, string $context): array
    {
        return $this->createQueryBuilder('category')
            ->distinct()
            ->addSelect(
                'parent',
                'categoryFiles',
                'categoryFile',
                'CASE WHEN category.sortOrder IS NULL THEN 1 ELSE 0 END AS HIDDEN categoryOrderNull'
            )
            ->leftJoin('category.parent', 'parent')
            ->leftJoin('category.categoryFiles', 'categoryFiles')
            ->leftJoin('categoryFiles.file', 'categoryFile')
            ->andWhere('IDENTITY(category.company) = :categoryCompany')
            ->andWhere('category.context = :categoryContext')
            ->setParameter('categoryCompany', $companyId)
            ->setParameter('categoryContext', $context)
            ->orderBy('categoryOrderNull', 'ASC')
            ->addOrderBy('category.sortOrder', 'ASC')
            ->addOrderBy('category.name', 'ASC')
            ->addOrderBy('category.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
