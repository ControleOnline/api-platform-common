<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ControleOnline\Controller\GetActionByPeopleAction;
use ControleOnline\Controller\GetMenuByPeopleAction;
use ControleOnline\Controller\GetMenuConfigAction;
use ControleOnline\Controller\SaveMenuConfigAction;

use ControleOnline\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_SUPER\')'),
        new Put(
            security: 'is_granted(\'ROLE_SUPER\')',
            denormalizationContext: ['groups' => ['menu:write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_SUPER\')'),
        new GetCollection(security: 'is_granted(\'ROLE_SUPER\')'),
        new Post(security: 'is_granted(\'ROLE_SUPER\')'),
        new GetCollection(
            uriTemplate: '/menus-people',
            controller: GetMenuByPeopleAction::class,
            security: 'is_granted(\'ROLE_HUMAN\')'
        ),
        new GetCollection(
            uriTemplate: '/menu-config',
            controller: GetMenuConfigAction::class,
            security: 'is_granted(\'ROLE_SUPER\')'
        ),
        new Put(
            uriTemplate: '/menu-config/{id}',
            controller: SaveMenuConfigAction::class,
            security: 'is_granted(\'ROLE_SUPER\')'
        ),
        new Patch(
            uriTemplate: '/menu-config/{id}',
            controller: SaveMenuConfigAction::class,
            security: 'is_granted(\'ROLE_SUPER\')'
        ),
        new GetCollection(
            uriTemplate: '/actions/people',
            controller: GetActionByPeopleAction::class,
            security: 'is_granted(\'ROLE_HUMAN\')'
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['menu:read']],
    denormalizationContext: ['groups' => ['menu:write']]
)]
#[ORM\Table(name: 'menu')]
#[ORM\Index(name: 'category_id', columns: ['category_id'])]
#[ORM\Index(name: 'menu_app_type_idx', columns: ['app_type'])]
#[ORM\UniqueConstraint(name: 'menu_app_key_unique', columns: ['app_type', 'menu_key'])]

#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['menu:read'])]
    private $id;

    #[ORM\Column(name: 'menu', type: 'string', length: 50, nullable: false)]
    #[Groups(['menu:read', 'menu:write'])]
    private $menu;

    #[ORM\Column(name: 'menu_key', type: 'string', length: 100, nullable: false)]
    #[Groups(['menu:read', 'menu:write'])]
    private string $menuKey = '';

    #[ORM\Column(name: 'app_type', type: 'string', length: 30, nullable: false, options: ['default' => 'MANAGER'])]
    #[Groups(['menu:read', 'menu:write'])]
    private string $appType = 'MANAGER';

    #[ORM\Column(name: 'route_params', type: 'json', nullable: true)]
    #[Groups(['menu:read', 'menu:write'])]
    private ?array $routeParams = null;

    #[ORM\Column(name: 'sort_order', type: 'integer', nullable: false, options: ['default' => 0])]
    #[Groups(['menu:read', 'menu:write'])]
    private int $sortOrder = 0;

    #[ORM\Column(name: 'enabled', type: 'boolean', nullable: false, options: ['default' => true])]
    #[Groups(['menu:read', 'menu:write'])]
    private bool $enabled = true;

    #[ORM\JoinColumn(name: 'route_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Routes::class)]
    #[Groups(['menu:read', 'menu:write'])]
    private $route;

    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[Groups(['menu:read', 'menu:write'])]
    private $category;

    #[ORM\OneToMany(mappedBy: 'menu', targetEntity: MenuLinkType::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $linkTypes;

    public function __construct()
    {
        $this->linkTypes = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMenu(): string
    {
        return $this->menu;
    }

    public function setMenu($menu): self
    {
        $this->menu = $menu;
        return $this;
    }

    public function getMenuKey(): string
    {
        return $this->menuKey;
    }

    public function setMenuKey(string $menuKey): self
    {
        $this->menuKey = $menuKey;

        return $this;
    }

    public function getAppType(): string
    {
        return $this->appType;
    }

    public function setAppType(string $appType): self
    {
        $this->appType = strtoupper(trim($appType));

        return $this;
    }

    public function getRouteParams(): ?array
    {
        return $this->routeParams;
    }

    public function setRouteParams(?array $routeParams): self
    {
        $this->routeParams = $routeParams;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

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

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getLinkTypes(): Collection
    {
        return $this->linkTypes;
    }

    public function addLinkType(MenuLinkType $linkType): self
    {
        if (!$this->linkTypes->contains($linkType)) {
            $this->linkTypes->add($linkType);
            $linkType->setMenu($this);
        }

        return $this;
    }

    public function removeLinkType(MenuLinkType $linkType): self
    {
        if ($this->linkTypes->removeElement($linkType) && $linkType->getMenu() === $this) {
            $linkType->setMenu(null);
        }

        return $this;
    }
}
