<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * Module
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['module:write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['module:read']],
    denormalizationContext: ['groups' => ['module:write']]
)]
#[ORM\Table(name: 'module')]
#[ORM\UniqueConstraint(name: 'UX_MODULE_NAME', columns: ['name'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\ModuleRepository::class)]
class Module
{
    /**
     * @var int
     *
     * @Groups({"menu:read","module:read"})
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;
    /**
     * @var string
     *
     * @Groups({"menu:read","module:read","module:write"}) 
     */
    #[ORM\Column(name: 'name', type: 'string', length: 100, nullable: false)]
    private $name;
    /**
     * @var string
     *
     * @Groups({"menu:read","module:read","module:write"})  
     */
    #[ORM\Column(name: 'color', type: 'string', length: 50, nullable: false, options: ['default' => "'\$primary'"])]
    private $color = '$primary';
    /**
     * @var string
     *
     * @Groups({"menu:read","module:read","module:write"})  
     */
    #[ORM\Column(name: 'icon', type: 'string', length: 50, nullable: false)]
    private $icon;
    /**
     * @var string|null
     *
     * @Groups({"menu:read","module:read","module:write"})  
     */
    #[ORM\Column(name: 'description', type: 'string', length: 255, nullable: true, options: ['default' => 'NULL'])]
    private $description = NULL;
    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Get the value of name
     */
    public function getName(): string
    {
        return $this->name;
    }
    /**
     * Set the value of name
     */
    public function setName(string $name): self
    {
        $this->name = $name;
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
    public function setColor(string $color): self
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
    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }
    /**
     * Get the value of description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
    /**
     * Set the value of description
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
}
