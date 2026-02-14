<?php

namespace ControleOnline\Listener;

use ControleOnline\Event\EntityChangedEvent;
use ControleOnline\Service\ExtraDataService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DefaultEventListener
{

    private static array $oldEntitySnapshot = [];

    public function __construct(
        private EntityManagerInterface $manager,
        private ExtraDataService $extraDataService,
        private EventDispatcherInterface $dispatcher,
        private ContainerInterface $container,
    ) {}

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        self::$oldEntitySnapshot[get_class($entity)][$entity->getId()] = clone $entity;
        $this->execute($args->getObject(), 'preUpdate');
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $this->extraDataService->discoveryDevice($entity);
        $this->extraDataService->discoveryUser($entity);
        $this->execute($entity, 'prePersist');
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->execute($args->getObject(), 'postUpdate');
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->execute($args->getObject(), 'postPersist');
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $this->execute($args->getObject(), 'preRemove');
    }

    private function execute($entity, $method)
    {
        $class = get_class($entity);
        $serviceName = str_replace('Entity', 'Service', $class) . 'Service';
        $this->extraDataService->persist($entity);
        $oldEntity = null;
        if (isset(self::$oldEntitySnapshot[$class][$entity->getId()])) {
            $oldEntity = self::$oldEntitySnapshot[$class][$entity->getId()];
            unset(self::$oldEntitySnapshot[$class][$entity->getId()]);
            if (empty(self::$oldEntitySnapshot[$class])) {
                unset(self::$oldEntitySnapshot[$class]);
            }
        }
        $this->dispatcher->dispatch(new EntityChangedEvent($entity, $method, $oldEntity));
        if ($this->container->has($serviceName)) {
            $service = $this->container->get($serviceName);
            if (method_exists($service, $method)) {

                $newEntity = $service->$method($entity);

                if ('postPersist' === $method && $newEntity)
                    $this->manager->refresh($newEntity);
            }
        }
        return $entity;
    }
}
