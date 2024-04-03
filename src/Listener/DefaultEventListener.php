<?php

namespace ControleOnline\Listener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;

class DefaultEventListener
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ContainerInterface $container,
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

    private function execute($entity, $method)
    {
        $serviceName = str_replace('Entity', 'Service', get_class($entity)) . 'Service';
        if ($this->container->has($serviceName)) {
            $service = $this->container->get($serviceName);

            if (method_exists($service, $method)) {
                $service->$method($entity);
                $this->manager->refresh($entity);
            }
        }
    }
}
