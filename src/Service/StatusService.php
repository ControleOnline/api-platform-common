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



    public function discoveryRealStatus($realStatus, $context, $name): Status
    {
        $status =  $this->manager->getRepository(Status::class)->findOneBy([
            'realStatus' => $realStatus,
            'context' => $context,
        ]);

        if (!$status)
            return $this->discoveryStatus($realStatus, $name, $context);

        return $status;
    }

    public function discoveryStatus($realStatus, $name, $context): Status
    {
        $status = $this->manager->getRepository(Status::class)->findOneBy([
            'realStatus' => $realStatus,
            'status' => $name,
            'context' => $context,
        ]);

        if ($status instanceof Status) {
            return $status;
        }

        // Prefer an existing row with the same realStatus + context (any label).
        $status = $this->manager->getRepository(Status::class)->findOneBy([
            'realStatus' => $realStatus,
            'context' => $context,
        ]);
        if ($status instanceof Status) {
            return $status;
        }

        $status = new Status();
        $status->setRealStatus($realStatus);
        $status->setStatus($name);
        $status->setContext($context);
        $status->setVisibility('1');
        $status->setNotify(1);
        $status->setSystem(0);
        $status->setColor('');

        $this->manager->persist($status);
        $this->manager->flush();

        return $status;
    }
}
