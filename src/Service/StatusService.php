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
        $status =  $this->manager->getRepository(Status::class)->findOneBy([
            'realStatus' => $realStatus,
            'context' => $context,
            'status' => $status
        ]);
        if (!$status) {
            $status = new Status();
            $status->setRealStatus($realStatus);
            $status->setStatus($status);
            $status->setContext($context);
            $this->manager->persist($status);
            $this->manager->flush();
        }


        return $status;
    }
}
