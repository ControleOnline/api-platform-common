<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\CronJob;
use ControleOnline\Entity\People;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CronJob>
 */
class CronJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CronJob::class);
    }

    /**
     * @return CronJob[]
     */
    public function findMainCompanyJobs(People $people): array
    {
        return $this->createQueryBuilder('cron_job')
            ->andWhere('cron_job.people = :people')
            ->setParameter('people', $people)
            ->orderBy('cron_job.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
