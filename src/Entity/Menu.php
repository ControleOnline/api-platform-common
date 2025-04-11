<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ControleOnline\Controller\GetActionByPeopleAction;
use ControleOnline\Controller\GetMenuByPeopleAction;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * Menu
 */
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
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\MenuRepository::class)]
#[ORM\Entity]
class Menu
{
    /**
     * @var int
     *
     * @Groups({"menu:read"}) 
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;
    /**
     * @var string
     *
     * @Groups({"menu:read","menu:write"})
     */
    #[ORM\Column(name: 'menu', type: 'string', length: 50, nullable: false)]
    private $menu;
    /**
     * @var \Route
     *
     * @Groups({"menu:read","menu:write"}) 
     */
    #[ORM\JoinColumn(name: 'route_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Routes::class)]
    private $route;


    /**
     * @var \ControleOnline\Entity\Category
     *
     * @Groups({"menu:read","menu:write"})
     */
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\Category::class)]
    private $category;
    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Get the value of menu
     */
    public function getMenu(): string
    {
        return $this->menu;
    }
    /**
     * Set the value of menu
     */
    public function setMenu($menu): self
    {
        $this->menu = $menu;
        return $this;
    }
    /**
     * Get the value of route
     */
    public function getRoute()
    {
        return $this->route;
    }
    /**
     * Set the value of route
     */
    public function setRoute($route): self
    {
        $this->route = $route;
        return $this;
    }

    /**
     * Get the value of category
     */
    public function getCategory()
    {
        return $this->category;
    }
    /**
     * Set the value of category
     */
    public function setCategory($category): self
    {
        $this->category = $category;
        return $this;
    }
}
