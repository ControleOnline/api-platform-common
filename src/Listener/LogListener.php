<?php

namespace ControleOnline\Listener;

use ControleOnline\Entity\Log;
use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

class LogListener
{
    private array $log = [];
    private ?User $currentUser = null;

    public function __construct(private Security $security) {}

    public function prePersist(PrePersistEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'insert', $event->getObjectManager());
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $this->persistLogs($event->getObjectManager());
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'update', $event->getObjectManager());
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $this->persistLogs($event->getObjectManager());
    }

    public function preRemove(PreRemoveEventArgs $event): void
    {
        $this->logEntity($event->getObject(), 'delete', $event->getObjectManager());
    }

    public function postRemove(PostRemoveEventArgs $event): void
    {
        $this->persistLogs($event->getObjectManager());
    }

    private function logEntity(?object $entity, string $action, EntityManagerInterface $em): void
    {
        if (!$entity || $entity instanceof Log) {
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
            if (!empty($changes)) return $changes;
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
        if (empty($this->log)) return;

        $this->currentUser = $this->security->getUser();
        $conn = $em->getConnection();

        foreach ($this->log as $logData) {
            $conn->insert('log', [
                'action'   => $logData['action'],
                'class'    => $logData['class'],
                'object'   => json_encode($logData['object'], JSON_UNESCAPED_UNICODE),
                'user_id'  => $this->currentUser?->getId(),
            ]);
        }

        $this->log = [];
    }
}
