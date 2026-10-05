<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\DeviceConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method DeviceConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeviceConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeviceConfig[]    findAll()
 * @method DeviceConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeviceConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private ?RequestStack $requestStack = null)
    {
        parent::__construct($registry, DeviceConfig::class);
    }

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $request = $this->requestStack?->getCurrentRequest();
        $company = $criteria['people'] ?? null;
        $companyId = is_object($company) && method_exists($company, 'getId') ? $company->getId() : $company;
        if (!$request?->isMethod('POST') || !preg_match('#/orders/[0-9]+/confirm$#', $request->getPathInfo()) ||
            array_keys($criteria) !== ['people'] || !$companyId || $orderBy !== null || $limit !== null || $offset !== null) {
            return parent::findBy($criteria, $orderBy, $limit, $offset);
        }

        // Recipient discovery is reused only during this confirmation, never between requests.
        $key = '_confirm_devices_' . spl_object_id($this) . '_' . $companyId;
        $cached = $request->attributes->get($key);
        if (is_array($cached) && $cached !== []) return $cached;
        $configs = parent::findBy($criteria, $orderBy, $limit, $offset);
        if ($configs !== []) $request->attributes->set($key, $configs);
        return $configs;
    }
}
