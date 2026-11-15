<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private ?RequestStack $requestStack = null)
    {
        parent::__construct($registry, Status::class);
    }

    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        $request = $this->requestStack?->getCurrentRequest();
        $keys = array_keys($criteria);
        sort($keys);
        if (!$request?->isMethod('POST') || !preg_match('#/orders/[0-9]+/confirm$#', $request->getPathInfo()) ||
            $keys !== ['context', 'realStatus', 'status']) return parent::findOneBy($criteria, $orderBy);

        ksort($criteria);
        $key = '_confirm_status_' . spl_object_id($this) . '_' . hash('sha256', serialize([$criteria, $orderBy]));
        $cached = $request->attributes->get($key);
        // A state change in this request must invalidate the prior lookup.
        if ($cached instanceof Status && $cached->getContext() === $criteria['context'] &&
            $cached->getRealStatus() === $criteria['realStatus'] && $cached->getStatus() === $criteria['status']) return $cached;

        $status = parent::findOneBy($criteria, $orderBy);
        if ($status instanceof Status) $request->attributes->set($key, $status);
        return $status;
    }
}
