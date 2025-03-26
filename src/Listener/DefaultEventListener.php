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
    ) {}

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->execute($args->getEntity(), 'prePersist');
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->ExtraDataService->discoveryDevice($entity);
        $this->ExtraDataService->discoveryUser($entity);
        $this->execute($entity, 'prePersist');
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->execute($args->getEntity(), 'postPersist');
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->execute($args->getEntity(), 'postPersist');
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $this->execute($args->getEntity(), 'preRemove');
    }

    private function execute($entity, $method)
    {
        $class = get_class($entity);
        $serviceName = str_replace('Entity', 'Service', $class) . 'Service';
        $this->ExtraDataService->persist($entity);
        if ($this->container->has($serviceName)) {
            $service = $this->container->get($serviceName);
            if (method_exists($service, $method)) {
                $newEntity = $service->$method($entity);

                if ('prePersist' === $method && $newEntity && $newEntity != $entity) {
                    $this->manager->detach($entity);
                    return $newEntity;
                }


                if ('postPersist' === $method && $newEntity)
                    $this->manager->refresh($newEntity);
            }
        }
        return $entity;
    }
}
