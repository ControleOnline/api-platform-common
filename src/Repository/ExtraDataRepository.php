<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\ExtraData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;

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
}
