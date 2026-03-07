<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\Import;
use ControleOnline\Entity\People;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Import|null find($id, $lockMode = null, $lockVersion = null)
 * @method Import|null findOneBy(array $criteria, array $orderBy = null)
 * @method Import[]    findAll()
 * @method Import[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImportRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Import::class);
  }

  public function getOpenImports(int $limit)
  {
    return $this->createQueryBuilder('i')
      ->join('i.status', 's')
      ->where('s.key = :status')
      ->setParameter('status', 'pending')
      ->setMaxResults($limit)
      ->orderBy('i.id', 'ASC')
      ->getQuery()
      ->getResult();
  }
}
