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
        bool $requireImage = false,
        ?int $showcaseId = null
    ): int {
        $queryBuilder = $this->createPublicShopQuery($companyId, $search, $requireImage, $showcaseId);

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
        int $itemsPerPage,
        ?int $showcaseId = null
    ): array {
        return $this->createPublicShopQuery($companyId, $search, $requireImage, $showcaseId)
            ->addSelect('categoryFiles', 'categoryFile')
            ->orderBy('category.name', 'ASC')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getResult();
    }

    public function findPublicShopCategory(int $id, int $companyId, ?int $showcaseId = null): ?Category
    {
        return $this->createPublicShopQuery($companyId, '', false, $showcaseId)
            ->addSelect('categoryFiles', 'categoryFile')
            ->andWhere('category.id = :categoryId')
            ->setParameter('categoryId', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function createPublicShopQuery(
        int $companyId,
        string $search,
        bool $requireImage,
        ?int $showcaseId = null
    ): \Doctrine\ORM\QueryBuilder {
        $queryBuilder = $this->createQueryBuilder('category')
            ->leftJoin('category.categoryFiles', 'categoryFiles')
            ->leftJoin('categoryFiles.file', 'categoryFile')
            ->andWhere('IDENTITY(category.company) = :publicShopCompany')
            ->andWhere('category.context = :publicShopContext')
            ->setParameter('publicShopCompany', $companyId)
            ->setParameter('publicShopContext', 'products');

        if ($showcaseId !== null) {
            $queryBuilder
                ->leftJoin('ControleOnline\Entity\Category', 'publicShopChild1', 'WITH', 'publicShopChild1.parent = category')
                ->leftJoin('ControleOnline\Entity\Category', 'publicShopChild2', 'WITH', 'publicShopChild2.parent = publicShopChild1')
                ->leftJoin('ControleOnline\Entity\Category', 'publicShopChild3', 'WITH', 'publicShopChild3.parent = publicShopChild2')
                ->leftJoin('ControleOnline\Entity\Category', 'publicShopChild4', 'WITH', 'publicShopChild4.parent = publicShopChild3')
                ->innerJoin(
                    'ControleOnline\Entity\ProductCategory',
                    'productCategory',
                    'WITH',
                    'productCategory.category = category'
                    . ' OR productCategory.category = publicShopChild1'
                    . ' OR productCategory.category = publicShopChild2'
                    . ' OR productCategory.category = publicShopChild3'
                    . ' OR productCategory.category = publicShopChild4'
                )
                ->innerJoin('ControleOnline\Entity\ProductShowcaseItem', 'showcaseItem', 'WITH', 'showcaseItem.product = productCategory.product')
                ->innerJoin('showcaseItem.product', 'showcaseProduct')
                ->andWhere('IDENTITY(showcaseItem.showcase) = :publicShopShowcase')
                ->andWhere('showcaseItem.active = true')
                ->andWhere('showcaseItem.published = true')
                ->andWhere('showcaseProduct.active = true')
                ->setParameter('publicShopShowcase', $showcaseId)
            ;
        }

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
