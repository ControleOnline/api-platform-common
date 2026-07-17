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
            ->orderBy('cron_job.sortOrder', 'ASC')
            ->addOrderBy('cron_job.jobKey', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMainCompanyJob(People $people, string $jobKey): ?CronJob
    {
        return $this->createQueryBuilder('cron_job')
            ->andWhere('cron_job.people = :people')
            ->andWhere('cron_job.jobKey = :jobKey')
            ->setParameter('people', $people)
            ->setParameter('jobKey', trim($jobKey))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
