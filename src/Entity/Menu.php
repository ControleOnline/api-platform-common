<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ControleOnline\Controller\GetActionByPeopleAction;
use ControleOnline\Controller\GetMenuByPeopleAction;
use ControleOnline\Listener\LogListener;
use ControleOnline\Repository\MenuRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['menu:write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(
            uriTemplate: '/menus-people',
            controller: GetMenuByPeopleAction::class
        ),
        new GetCollection(
            uriTemplate: '/actions/people',
            controller: GetActionByPeopleAction::class
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['menu:read']],
    denormalizationContext: ['groups' => ['menu:write']]
)]
#[ORM\Table(name: 'menu')]
#[ORM\Index(name: 'category_id', columns: ['category_id'])]
#[ORM\UniqueConstraint(name: 'route', columns: ['route'])]
#[ORM\EntityListeners([LogListener::class])]
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

    #[ORM\JoinColumn(name: 'route_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Routes::class)]
    #[Groups(['menu:read', 'menu:write'])]
    private $route;

    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[Groups(['menu:read', 'menu:write'])]
    private $category;

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
}