<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\DeviceConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DeviceConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeviceConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeviceConfig[]    findAll()
 * @method DeviceConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeviceConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceConfig::class);
    }
}
