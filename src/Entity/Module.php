<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;

use ControleOnline\Repository\ModuleRepository;
use Doctrine\ORM\Mapping as ORM;

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

#[ORM\Entity(repositoryClass: ModuleRepository::class)]
class Module
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['menu:read', 'module:read'])]
    private $id;

    #[ORM\Column(name: 'name', type: 'string', length: 100, nullable: false)]
    #[Groups(['menu:read', 'module:read', 'module:write'])]
    private $name;

    #[ORM\Column(name: 'color', type: 'string', length: 50, nullable: false, options: ['default' => "'\$primary'"])]
    #[Groups(['menu:read', 'module:read', 'module:write'])]
    private $color = '$primary';

    #[ORM\Column(name: 'icon', type: 'string', length: 50, nullable: false)]
    #[Groups(['menu:read', 'module:read', 'module:write'])]
    private $icon;

    #[ORM\Column(name: 'description', type: 'string', length: 255, nullable: true, options: ['default' => 'NULL'])]
    #[Groups(['menu:read', 'module:read', 'module:write'])]
    private $description = null;

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
}