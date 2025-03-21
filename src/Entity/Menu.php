<?php

namespace ControleOnline\Entity;

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
 *
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Table (name="menu", uniqueConstraints={@ORM\UniqueConstraint (name="route", columns={"route"})}, indexes={ @ORM\Index(name="category_id", columns={"category_id"})})
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\MenuRepository")
 * @ORM\Entity
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
class Menu
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"menu:read"})  
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="menu", type="string", length=50, nullable=false)
     * @Groups({"menu:read","menu:write"}) 
     */
    private $menu;
    /**
     * @var \Route
     *
     * @ORM\ManyToOne(targetEntity="Routes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="route_id", referencedColumnName="id")
     * })
     * @Groups({"menu:read","menu:write"})  
     */
    private $route;


    /**
     * @var \ControleOnline\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * })
     * @Groups({"menu:read","menu:write"}) 
     */
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
