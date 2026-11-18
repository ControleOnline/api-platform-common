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

use ControleOnline\Repository\RouteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_SUPER\')'),
        new Put(
            security: 'is_granted(\'ROLE_SUPER\')',
            denormalizationContext: ['groups' => ['route:write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_SUPER\')'),
        new GetCollection(security: 'is_granted(\'ROLE_SUPER\')'),
        new Post(security: 'is_granted(\'ROLE_SUPER\')'),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['route:read']],
    denormalizationContext: ['groups' => ['route:write']]
)]
#[ORM\Table(name: 'routes')]
#[ORM\Index(name: 'module_id', columns: ['module_id'])]
#[ORM\UniqueConstraint(name: 'route', columns: ['route'])]

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

    #[ORM\Column(name: 'text_color', type: 'string', length: 50, nullable: true)]
    #[Groups(['menu:read'])]
    private ?string $textColor = null;

    #[ORM\Column(name: 'icon_color', type: 'string', length: 50, nullable: true)]
    #[Groups(['menu:read'])]
    private ?string $iconColor = null;

    #[ORM\Column(name: 'icon_background_color', type: 'string', length: 50, nullable: true)]
    #[Groups(['menu:read'])]
    private ?string $iconBackgroundColor = null;

    #[ORM\Column(name: 'routeCardBackground', type: 'string', length: 50, nullable: true)]
    #[Groups(['menu:read'])]
    private ?string $routeCardBackground = null;

    #[ORM\Column(name: 'routeCardIcon', type: 'string', length: 50, nullable: true)]
    #[Groups(['menu:read'])]
    private ?string $routeCardIcon = null;

    #[ORM\Column(name: 'routeCardIconBackground', type: 'string', length: 50, nullable: true)]
    #[Groups(['menu:read'])]
    private ?string $routeCardIconBackground = null;

    #[ORM\Column(name: 'routeCardText', type: 'string', length: 50, nullable: true)]
    #[Groups(['menu:read'])]
    private ?string $routeCardText = null;

    #[ORM\Column(name: 'routeCardBorder', type: 'string', length: 50, nullable: true)]
    #[Groups(['menu:read'])]
    private ?string $routeCardBorder = null;

    #[ORM\Column(name: 'routeMenuBackground', type: 'string', length: 50, nullable: true)]
    #[Groups(['menu:read'])]
    private ?string $routeMenuBackground = null;

    #[ORM\Column(name: 'routeMenuIcon', type: 'string', length: 50, nullable: true)]
    #[Groups(['menu:read'])]
    private ?string $routeMenuIcon = null;

    #[ORM\Column(name: 'routeMenuText', type: 'string', length: 50, nullable: true)]
    #[Groups(['menu:read'])]
    private ?string $routeMenuText = null;

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

    public function getTextColor(): ?string { return $this->textColor; }

    public function setTextColor(?string $value): self
    {
        $this->textColor = $value;
        return $this;
    }

    public function getIconColor(): ?string { return $this->iconColor; }

    public function setIconColor(?string $value): self
    {
        $this->iconColor = $value;
        return $this;
    }

    public function getIconBackgroundColor(): ?string { return $this->iconBackgroundColor; }

    public function setIconBackgroundColor(?string $value): self
    {
        $this->iconBackgroundColor = $value;
        return $this;
    }

    public function getRouteCardBackground(): ?string { return $this->routeCardBackground; }

    public function setRouteCardBackground(?string $value): self
    {
        $this->routeCardBackground = $value;
        return $this;
    }

    public function getRouteCardIcon(): ?string { return $this->routeCardIcon; }

    public function setRouteCardIcon(?string $value): self
    {
        $this->routeCardIcon = $value;
        return $this;
    }

    public function getRouteCardIconBackground(): ?string { return $this->routeCardIconBackground; }

    public function setRouteCardIconBackground(?string $value): self
    {
        $this->routeCardIconBackground = $value;
        return $this;
    }

    public function getRouteCardText(): ?string { return $this->routeCardText; }

    public function setRouteCardText(?string $value): self
    {
        $this->routeCardText = $value;
        return $this;
    }

    public function getRouteCardBorder(): ?string { return $this->routeCardBorder; }

    public function setRouteCardBorder(?string $value): self
    {
        $this->routeCardBorder = $value;
        return $this;
    }

    public function getRouteMenuBackground(): ?string { return $this->routeMenuBackground; }

    public function setRouteMenuBackground(?string $value): self
    {
        $this->routeMenuBackground = $value;
        return $this;
    }

    public function getRouteMenuIcon(): ?string { return $this->routeMenuIcon; }

    public function setRouteMenuIcon(?string $value): self
    {
        $this->routeMenuIcon = $value;
        return $this;
    }

    public function getRouteMenuText(): ?string { return $this->routeMenuText; }

    public function setRouteMenuText(?string $value): self
    {
        $this->routeMenuText = $value;
        return $this;
    }
}
