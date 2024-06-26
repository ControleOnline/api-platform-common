<?php

namespace ControleOnline\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ControleOnline\Service\ExtraDataService;
use Doctrine\ORM\EntityManagerInterface;

class DefaultEventSubscriber implements EventSubscriber
{

    private ExtraDataService $ExtraDataService;

    public function __construct(EntityManagerInterface $manager, ContainerInterface $container, ExtraDataService $ExtraDataService)
    {
        $this->ExtraDataService = $ExtraDataService;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $this->ExtraDataService->persist();
    }
}
