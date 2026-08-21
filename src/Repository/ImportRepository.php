<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\Import;
use ControleOnline\Entity\Status;
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

  public function __construct(
    ManagerRegistry $registry
  ) {
    parent::__construct($registry, Import::class);
  }

  public function getImportsByStatus($status, int $limit)
  {
    return $this->createQueryBuilder('i')
      ->where('i.status = :status')
      ->setParameter('status', $status)
      ->setMaxResults($limit)
      ->orderBy('i.id', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Imports elegíveis para o worker: status open + processing estagnados
   * (upload_date mais antigo que $staleBefore), ordenados por id ASC.
   *
   * @param Status $openStatus
   * @param Status $processingStatus
   * @param \DateTimeInterface $staleBefore
   * @param int $limit
   * @return Import[]
   */
  public function getImportsToProcess(
    Status $openStatus,
    Status $processingStatus,
    \DateTimeInterface $staleBefore,
    int $limit
  ): array {
    return $this->createQueryBuilder('i')
      ->where('i.status = :open')
      ->orWhere('i.status = :processing AND i.uploadDate < :staleBefore')
      ->setParameter('open', $openStatus)
      ->setParameter('processing', $processingStatus)
      ->setParameter('staleBefore', $staleBefore)
      ->setMaxResults($limit)
      ->orderBy('i.id', 'ASC')
      ->getQuery()
      ->getResult();
  }
}
