<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Status;
use Doctrine\ORM\EntityManagerInterface;

class StatusService
{


    protected $request;
    public function __construct(
        private EntityManagerInterface $manager,

    ) {}

    public function discoveryStatus($realStatus, $status, $context): Status
    {
        return $this->manager->getRepository(Status::class)->findOneBy([
            'realStatus' => $realStatus,
            'context' => $context,
            'status' => $status
        ]);
    }
}
