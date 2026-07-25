<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\Timezone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Timezone>
 */
class TimezoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Timezone::class);
    }

    /**
     * Retorna todos os timezones (uso administrativo)
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retorna apenas timezones habilitados
     */
    public function findEnabled(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retorna timezones filtrando por status
     */
    public function findByEnabled(bool $enabled): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.enabled = :enabled')
            ->setParameter('enabled', $enabled)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}