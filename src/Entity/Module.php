<?php

namespace ControleOnline\Entity;

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
 *
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Table (name="module", uniqueConstraints={@ORM\UniqueConstraint (name="UX_MODULE_NAME", columns={"name"})})
 * @ORM\Entity
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\ModuleRepository")
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
class Module
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"menu:read","module:read"}) 
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     * @Groups({"menu:read","module:read","module:write"})  
     */
    private $name;
    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=50, nullable=false, options={"default"="'$primary'"})
     * @Groups({"menu:read","module:read","module:write"})   
     */
    private $color = '$primary';
    /**
     * @var string
     *
     * @ORM\Column(name="icon", type="string", length=50, nullable=false)
     * @Groups({"menu:read","module:read","module:write"})   
     */
    private $icon;
    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true, options={"default"="NULL"})
     * @Groups({"menu:read","module:read","module:write"})   
     */
    private $description = NULL;
    /**
     * Get the value of id
     */
    public function getId(): int
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
