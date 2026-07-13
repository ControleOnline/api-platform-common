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

    public function countPublicShopCategories(
        int $companyId,
        string $search = '',
        bool $requireImage = false
    ): int {
        $queryBuilder = $this->createPublicShopQuery($companyId, $search, $requireImage);

        return (int) $queryBuilder
            ->select('COUNT(DISTINCT category.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Category[]
     */
    public function findPublicShopCategories(
        int $companyId,
        string $search,
        bool $requireImage,
        int $page,
        int $itemsPerPage
    ): array {
        return $this->createPublicShopQuery($companyId, $search, $requireImage)
            ->addSelect('categoryFiles', 'categoryFile')
            ->orderBy('category.name', 'ASC')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getResult();
    }

    public function findPublicShopCategory(int $id, int $companyId): ?Category
    {
        return $this->createPublicShopQuery($companyId, '', false)
            ->addSelect('categoryFiles', 'categoryFile')
            ->andWhere('category.id = :categoryId')
            ->setParameter('categoryId', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function createPublicShopQuery(
        int $companyId,
        string $search,
        bool $requireImage
    ): \Doctrine\ORM\QueryBuilder {
        $queryBuilder = $this->createQueryBuilder('category')
            ->leftJoin('category.categoryFiles', 'categoryFiles')
            ->leftJoin('categoryFiles.file', 'categoryFile')
            ->andWhere('IDENTITY(category.company) = :publicShopCompany')
            ->andWhere('category.context = :publicShopContext')
            ->setParameter('publicShopCompany', $companyId)
            ->setParameter('publicShopContext', 'products');

        if ($search !== '') {
            $queryBuilder
                ->andWhere('LOWER(category.name) LIKE :publicShopSearch')
                ->setParameter('publicShopSearch', '%' . mb_strtolower($search) . '%');
        }

        if ($requireImage) {
            $queryBuilder->andWhere('categoryFile.fileType = :publicShopFileType')
                ->setParameter('publicShopFileType', 'image');
        }

        return $queryBuilder;
    }
}
