<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ControleOnline\Listener\LogListener;
use ControleOnline\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\') and previous_object.canAccess(user))'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))',
            validationContext: ['groups' => ['notifications:write']],
            denormalizationContext: ['groups' => ['notifications:write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['notifications:read']],
    denormalizationContext: ['groups' => ['notifications:write']]
)]
#[ORM\Table(name: 'notification')]
#[ORM\Index(name: 'people_id', columns: ['people_id'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ApiResource(normalizationContext: ['groups' => ['notifications:read']])]
    private $id;

    #[ORM\Column(name: 'notification', type: 'text', length: 65535, nullable: false)]
    #[ApiResource(normalizationContext: ['groups' => ['notifications:read', 'notifications:write']])]
    private $notification;

    #[ORM\Column(name: 'route', type: 'string', length: 50, nullable: false)]
    #[ApiResource(normalizationContext: ['groups' => ['notifications:read', 'notifications:write']])]
    private $route;

    #[ORM\Column(name: 'route_id', type: 'integer', nullable: false)]
    #[ApiResource(normalizationContext: ['groups' => ['notifications:read', 'notifications:write']])]
    private $routeId;

    #[ORM\Column(name: 'read', type: 'boolean', nullable: false)]
    #[ApiResource(normalizationContext: ['groups' => ['notifications:read', 'notifications:write']])]
    private $read;

    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ApiResource(normalizationContext: ['groups' => ['notifications:read', 'notifications:write']])]
    private $people;

    public function getId()
    {
        return $this->id;
    }

    public function setNotification($notification): self
    {
        $this->notification = $notification;
        return $this;
    }

    public function getNotification()
    {
        return $this->notification;
    }

    public function setRoute($route): self
    {
        $this->route = $route;
        return $this;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function setRouteId($routeId): self
    {
        $this->routeId = $routeId;
        return $this;
    }

    public function getRouteId()
    {
        return $this->routeId;
    }

    public function setRead($read): self
    {
        $this->read = $read;
        return $this;
    }

    public function getRead()
    {
        return $this->read;
    }

    public function setPeople($people): self
    {
        $this->people = $people;
        return $this;
    }

    public function getPeople()
    {
        return $this->people;
    }
}