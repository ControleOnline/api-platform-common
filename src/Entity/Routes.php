<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;


use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Filter\OrderEntityFilter;
use ControleOnline\Entity\Order;
use ControleOnline\Repository\RouteRepository;
use stdClass;


/**
 * Routes
 */
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
    /**
     * @var int
     *
     * @Groups({"menu:read","route:read"})  
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var string
     *
     * @Groups({"menu:read","route:read","route:write"})  
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['route' => 'exact'])]
    #[ORM\Column(name: 'route', type: 'string', length: 50, nullable: false)]

    private $route;

    /**
     * @var \Module
     *
     * @Groups({"menu:read","route:read","route:write"}) 
     */
    #[ORM\JoinColumn(name: 'module_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Module::class)]
    private $module;
    /**
     * @var string
     *
     * @Groups({"menu:read","route:read","route:write"}) 
     */
    #[ORM\Column(name: 'color', type: 'string', length: 50, nullable: false, options: ['default' => "'\$primary'"])]
    private $color = '$primary';
    /**
     * @var string
     *
     * @Groups({"menu:read","route:read","route:write"}) 
     */
    #[ORM\Column(name: 'icon', type: 'string', length: 50, nullable: false)]
    private $icon;

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     */
    public function setId($id): self
    {
        $this->id = $id;

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
     * Get the value of module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set the value of module
     */
    public function setModule($module): self
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Get the value of color
     */
    public function getColor(): string
    {
        return $this->color;
    }
    /**
     * Set the value of color
     */
    public function setColor($color): self
    {
        $this->color = $color;
        return $this;
    }
    /**
     * Get the value of icon
     */
    public function getIcon(): string
    {
        return $this->icon;
    }
    /**
     * Set the value of icon
     */
    public function setIcon($icon): self
    {
        $this->icon = $icon;
        return $this;
    }
}
