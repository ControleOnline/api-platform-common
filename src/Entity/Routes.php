<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ControleOnline\Listener\LogListener;
use ControleOnline\Repository\RouteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['route:write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(security: 'is_granted(\'ROLE_CLIENT\')'),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['route:read']],
    denormalizationContext: ['groups' => ['route:write']]
)]
#[ORM\Table(name: 'routes')]
#[ORM\Index(name: 'module_id', columns: ['module_id'])]
#[ORM\UniqueConstraint(name: 'route', columns: ['route'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: RouteRepository::class)]
class Routes
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['menu:read', 'route:read'])]
    private $id;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['route' => 'exact'])]
    #[ORM\Column(name: 'route', type: 'string', length: 50, nullable: false)]
    #[Groups(['menu:read', 'route:read', 'route:write'])]
    private $route;

    #[ORM\JoinColumn(name: 'module_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Module::class)]
    #[Groups(['menu:read', 'route:read', 'route:write'])]
    private $module;

    #[ORM\Column(name: 'color', type: 'string', length: 50, nullable: false, options: ['default' => "'\$primary'"])]
    #[Groups(['menu:read', 'route:read', 'route:write'])]
    private $color = '$primary';

    #[ORM\Column(name: 'icon', type: 'string', length: 50, nullable: false)]
    #[Groups(['menu:read', 'route:read', 'route:write'])]
    private $icon;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function setRoute($route): self
    {
        $this->route = $route;
        return $this;
    }

    public function getModule()
    {
        return $this->module;
    }

    public function setModule($module): self
    {
        $this->module = $module;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor($color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon($icon): self
    {
        $this->icon = $icon;
        return $this;
    }
}