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

    public function discoveryStatus($realStatus, $name, $context): Status
    {
        $status =  $this->manager->getRepository(Status::class)->findOneBy([
            'realStatus' => $realStatus,
            'status' => $name,
            'context' => $context,
        ]);

        if (!$status) {
            $status = new Status();
            $status->setRealStatus($realStatus);
            $status->setStatus($name);
            $status->setContext($context);
            
            $this->manager->persist($status);
            $this->manager->flush();
        }


        return $status;
    }
}
