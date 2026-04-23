<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\Language;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Translate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Translate|null find($id, $lockMode = null, $lockVersion = null)
 * @method Translate|null findOneBy(array $criteria, array $orderBy = null)
 * @method Translate[]    findAll()
 * @method Translate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TranslateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Translate::class);
    }

    public function findForOverview(
        People $people,
        Language $language,
        array $filters = []
    ): array {
        $queryBuilder = $this->createQueryBuilder('translate')
            ->andWhere('translate.people = :people')
            ->andWhere('translate.language = :language')
            ->setParameter('people', $people)
            ->setParameter('language', $language)
            ->orderBy('translate.store', 'ASC')
            ->addOrderBy('translate.type', 'ASC')
            ->addOrderBy('translate.key', 'ASC');

        if (!empty($filters['store'])) {
            $queryBuilder
                ->andWhere('translate.store = :store')
                ->setParameter('store', $filters['store']);
        }

        if (!empty($filters['type'])) {
            $queryBuilder
                ->andWhere('translate.type = :type')
                ->setParameter('type', $filters['type']);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
