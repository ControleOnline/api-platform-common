<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Status;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class StatusService
{


    protected $request;
    public function __construct(
        private EntityManagerInterface $manager,
        private ?RequestStack $requestStack = null,

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
        $request = $this->requestStack?->getCurrentRequest();
        $key = '_status_lookup_' . spl_object_id($this->manager) . '_' . hash('sha256', serialize([$realStatus, $name, $context]));
        $cached = $request?->attributes->get($key);
        if ($cached instanceof Status && $cached->getRealStatus() === $realStatus && $cached->getContext() === $context) {
            return $cached;
        }
        $status = $this->manager->getRepository(Status::class)->findOneBy([
            'realStatus' => $realStatus,
            'status' => $name,
            'context' => $context,
        ]);

        if ($status instanceof Status) {
            $request?->attributes->set($key, $status);
            return $status;
        }

        // Prefer an existing row with the same realStatus + context (any label).
        $status = $this->manager->getRepository(Status::class)->findOneBy([
            'realStatus' => $realStatus,
            'context' => $context,
        ]);
        if ($status instanceof Status) {
            $request?->attributes->set($key, $status);
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

        $request?->attributes->set($key, $status);
        return $status;
    }
}
