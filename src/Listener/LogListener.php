<?php

namespace ControleOnline\Listener;

use ControleOnline\Entity\User;
use ControleOnline\Entity\Log;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\UnitOfWork;

class LogListener
{
    private array $log = [];
    private ?User $user = null;
    private bool $enabled = true;

    public function enable(bool $state = true): void
    {
        $this->enabled = $state;
    }

    public function prePersist(PrePersistEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'prePersist', $event->getObjectManager());
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'postPersist', $event->getObjectManager());
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'preUpdate', $event->getObjectManager());
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'postUpdate', $event->getObjectManager());
    }

    public function preRemove(PreRemoveEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'preRemove', $event->getObjectManager());
    }

    public function postRemove(PostRemoveEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'postRemove', $event->getObjectManager());
    }

    public function postLoad(PostLoadEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'postLoad', $event->getObjectManager());
    }

    private function logEntity(?object $entity, string $action, EntityManagerInterface $em): void
    {
        if (!$this->enabled || !$entity) {
            return;
        }

        if ($entity instanceof User) {
            $this->user = $entity;
            return;
        }

        $className = $em->getClassMetadata(get_class($entity))->getName();
        $changes = $this->extractChanges($entity, $em);

        $this->log[] = [
            'action' => $action,
            'class'  => $className,
            'object' => $changes,
        ];
    }

    private function extractChanges(object $entity, EntityManagerInterface $em): array
    {
        $uow = $em->getUnitOfWork();
        if ($uow->isInIdentityMap($entity)) {
            $changes = $uow->getEntityChangeSet($entity);
            if (!empty($changes)) {
                return $changes;
            }
        }

        $data = [];
        foreach (get_class_methods($entity) as $method) {
            if (str_starts_with($method, 'get')) {
                $value = $entity->$method();
                if (!is_object($value)) {
                    $data[lcfirst(substr($method, 3))] = $value;
                }
            }
        }

        return $data;
    }

    public function persistLogs(EntityManagerInterface $em): void
    {
        if (!$this->enabled || empty($this->log)) {
            return;
        }

        foreach ($this->log as $logData) {
            $log = new Log();
            $log->setObject(json_encode($logData['object']));
            $log->setUser($this->user);
            $log->setAction($logData['action']);
            $log->setClass($logData['class']);
            $em->persist($log);
        }

        $this->log = [];
    }
}
