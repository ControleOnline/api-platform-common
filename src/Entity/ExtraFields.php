<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ControleOnline\Repository\ExtraFieldsRepository;
use ControleOnline\Listener\LogListener;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))',
            validationContext: ['groups' => ['extra_fields:write']],
            denormalizationContext: ['groups' => ['extra_fields:write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['extra_fields:read']],
    denormalizationContext: ['groups' => ['extra_fields:write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['context' => 'exact', 'type' => 'exact'])] // Changed 'field_type' to 'type' based on property name
#[Table(name: 'extra_fields')]
#[EntityListeners([LogListener::class])]
#[Entity(repositoryClass: ExtraFieldsRepository::class)]
class ExtraFields
{
    #[Groups(['extra_fields:read', 'extra_data:read'])]
    #[Column(name: 'id', type: 'integer', nullable: false)]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private int $id = 0;

    #[Groups(['extra_fields:read', 'extra_fields:write', 'extra_data:read'])]
    #[Column(name: 'field_name', type: 'string', length: 255, nullable: false)]
    private string $name;

    #[Groups(['extra_fields:read', 'extra_fields:write', 'extra_data:read'])]
    #[Column(name: 'field_type', type: 'string', length: 255, nullable: false)]
    private string $type;

    #[Groups(['extra_fields:read', 'extra_fields:write', 'extra_data:read'])]
    #[Column(name: 'context', type: 'string', length: 255, nullable: false)]
    private string $context;

    #[Groups(['extra_fields:read', 'extra_fields:write', 'extra_data:read'])]
    #[Column(name: 'required', type: 'boolean', nullable: true)]
    private ?bool $required = null;

    #[Groups(['extra_fields:read', 'extra_fields:write', 'extra_data:read'])]
    #[Column(name: 'field_configs', type: 'string', nullable: true)] // Consider 'json' type if applicable
    private ?string $configs = null;

    public function getId(): int
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function setContext(string $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getRequired(): ?bool
    {
        return $this->required;
    }

    public function setRequired(?bool $required): self
    {
        $this->required = $required;
        return $this;
    }

    public function getConfigs(): ?string
    {
        return $this->configs;
    }

    public function setConfigs(?string $configs): self
    {
        $this->configs = $configs;
        return $this;
    }
}