<?php

namespace ControleOnline\Listener;

use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

class LogListener
{
    /**
     * @var array<string, mixed>[]
     */
    private array $log = [];

    /**
     * @var object|null
     */
    private $user = null;

    public function prePersistHandler(PrePersistEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'prePersist', $event->getObjectManager());
    }

    public function postPersistHandler(PostPersistEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'postPersist', $event->getObjectManager());
    }

    public function preUpdateHandler(PreUpdateEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'preUpdate', $event->getObjectManager());
    }

    public function postUpdateHandler(PostUpdateEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'postUpdate', $event->getObjectManager());
    }

    public function preRemoveHandler(PreRemoveEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'preRemove', $event->getObjectManager());
    }

    public function postRemoveHandler(PostRemoveEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'postRemove', $event->getObjectManager());
    }

    public function preFlushHandler(PreFlushEventArgs $event): void
    {
        $this->logEntity(null, 'preFlush', $event->getObjectManager());
    }

    public function postLoadHandler(PostLoadEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'postLoad', $event->getObjectManager());
    }

    private function logEntity(?object $entity, string $action, EntityManagerInterface $em): void
    {

        return;
        if ($entity === null && $action !== 'preFlush') {
            return;
        }

        if ($entity && $entity instanceof User) {
            $this->user = $entity;
            return;
        }

        if ($entity) {
            $className = $em->getClassMetadata(get_class($entity))->getName();
            $this->log[] = [
                'action' => $action,
                'class' => $className,
                'object' => $this->getObject($entity, $em),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getObject(object $entity, EntityManagerInterface $em): array
    {
        $methods = preg_grep('/^get/', get_class_methods($entity));
        $array = [];

        foreach ($methods as $method) {
            $content = $entity->$method();
            $property = lcfirst(substr($method, 3));

            if (!is_object($content)) {
                $array[$property] = $content;
            } elseif (!$content instanceof \Doctrine\ORM\PersistentCollection) {
                $array[$property] = $this->findIdentifier($content, $em);
            } else {
                $array[$property] = [];
                foreach ($content as $item) {
                    $array[$property][] = $this->findIdentifier($item, $em);
                }
            }
        }

        return $array;
    }

    private function findIdentifier(object $entity, EntityManagerInterface $em): mixed
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $identifier = $meta->getSingleIdentifierFieldName();
        $getter = 'get' . ucfirst($identifier);

        if (!method_exists($entity, $getter)) {
            return null;
        }

        $value = $entity->$getter();
        if (is_object($value)) {
            return $this->findIdentifier($value, $em);
        }

        return $value;
    }

    public function flushLogs(EntityManagerInterface $em): void
    {
        foreach ($this->log as $logData) {
            $log = new \ControleOnline\Entity\Log();
            $log->setObject(json_encode($logData['object']));
            $log->setUser($this->user);
            $log->setAction($logData['action']);
            $log->setClass($logData['class']);

            $em->persist($log);
        }

        $this->log = [];
        $em->flush();
    }
}
