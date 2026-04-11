<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\Model;
use ControleOnline\Entity\People;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Model|null find($id, $lockMode = null, $lockVersion = null)
 * @method Model|null findOneBy(array $criteria, array $orderBy = null)
 * @method Model[]    findAll()
 * @method Model[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Model::class);
    }

    public function findCompanyContextModel(
        People $company,
        string $context,
        ?int $modelId = null
    ): ?Model {
        $qb = $this->createQueryBuilder('model')
            ->addSelect('file')
            ->leftJoin('model.file', 'file')
            ->andWhere('model.people = :company')
            ->andWhere('model.context = :context')
            ->setParameter('company', $company)
            ->setParameter('context', $context)
            ->orderBy('model.model', 'ASC')
            ->addOrderBy('model.id', 'ASC');

        if ($modelId !== null) {
            $qb->andWhere('model.id = :modelId')
                ->setParameter('modelId', $modelId);
        } else {
            $qb->setMaxResults(1);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
