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

class LogListener
{
    private array $log = [];
    private ?User $user = null;

    public function prePersist(PrePersistEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'prePersist', $event->getObjectManager());
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'postPersist', $event->getObjectManager());
        $this->persistLogs($event->getObjectManager());
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'preUpdate', $event->getObjectManager());
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'postUpdate', $event->getObjectManager());
        $this->persistLogs($event->getObjectManager());
    }

    public function preRemove(PreRemoveEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'preRemove', $event->getObjectManager());
    }

    public function postRemove(PostRemoveEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'postRemove', $event->getObjectManager());
        $this->persistLogs($event->getObjectManager());
    }

    private function logEntity(?object $entity, string $action, EntityManagerInterface $em): void
    {
        if (!$entity) {
            return;
        }

        if ($entity instanceof User) {
            $this->user = $entity;
            return;
        }

        $className = $em->getClassMetadata(get_class($entity))->getName();
        $changes = $this->extractChanges($entity, $em);

        if (!empty($changes)) {
            $this->log[] = [
                'action' => $action,
                'class'  => $className,
                'object' => $changes,
            ];
        }
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
        if (empty($this->log)) {
            return;
        }

        $conn = $em->getConnection();
        $config = $em->getConfiguration();
        $eventManager = $em->getEventManager();

        $newEm = new \Doctrine\ORM\EntityManager($conn, $config, $eventManager);

        foreach ($this->log as $logData) {
            $log = new Log();
            
            $log->setUser($this->user);
            $log->setObject(json_encode($logData['object']));
            $log->setAction($logData['action']);
            $log->setClass($logData['class']);

            $newEm->persist($log);
            $newEm->flush();
            $newEm->clear();
        }

        $this->log = [];
    }
}
