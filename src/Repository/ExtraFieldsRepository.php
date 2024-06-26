<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\ExtraFields;
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
}
