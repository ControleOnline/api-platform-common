<?php

namespace ControleOnline\Listener;

use ControleOnline\Service\ExtraDataService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Doctrine\ORM\EntityManagerInterface;

class DefaultEventListener
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ContainerInterface $container,
        private ExtraDataService $ExtraDataService
    ) {
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->execute($args->getEntity(), 'beforePersist');
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->execute($args->getEntity(), 'afterPersist');
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->execute($args->getEntity(), 'beforePersist');
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->execute($args->getEntity(), 'afterPersist');
    }
    public function preRemove(LifecycleEventArgs $args)
    {
        $this->execute($args->getEntity(), 'beforeDelete');
    }
    private function execute($entity, $method)
    {
        $class = get_class($entity);
        $serviceName = str_replace('Entity', 'Service', $class) . 'Service';
        $this->ExtraDataService->persist($entity);
        if ($this->container->has($serviceName)) {
            $service = $this->container->get($serviceName);
            if (method_exists($service, $method)) {
                $entity = $service->$method($entity);
                if ('afterPersist' === $method && $entity)
                    $this->manager->refresh($entity);
            }
        }
    }
}
