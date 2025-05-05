<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ControleOnline\Repository\ExtraDataRepository;
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
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/extra_data/{id}', security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(uriTemplate: '/extra_data', security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            uriTemplate: '/extra_data/{id}',
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))',
            validationContext: ['groups' => ['extra_data:write']],
            denormalizationContext: ['groups' => ['extra_data:write']]
        ),
        new Delete(uriTemplate: '/extra_data/{id}', security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(uriTemplate: '/extra_data', securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['extra_data:read']],
    denormalizationContext: ['groups' => ['extra_data:write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: [
    'id' => 'exact',
    'extra_fields' => 'exact',
    'entity_id' => 'exact',
    'entity_name' => 'exact',
    // 'people' filter seems out of place as there's no 'people' property,
    // unless it relates to ExtraFields or another implicit context.
    // Keeping it based on the original code, but review if it's functional.
    'people' => 'exact'
])]
#[Table(name: 'extra_data')]
#[EntityListeners([LogListener::class])]
#[Entity(repositoryClass: ExtraDataRepository::class)]
class ExtraData
{
    #[Groups(['extrafields:read', 'extra_data:read'])]
    #[Column(name: 'id', type: 'integer', nullable: false)]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private int $id = 0;

    #[Groups(['extra_data:read'])]
    #[JoinColumn(name: 'extra_fields_id', referencedColumnName: 'id', nullable: true)] // Assuming nullable based on lack of 'nullable: false'
    #[ManyToOne(targetEntity: ExtraFields::class)]
    private ?ExtraFields $extra_fields = null;

    #[Groups(['extra_data:read'])]
    #[Column(name: 'entity_id', type: 'string', nullable: false)]
    private string $entity_id;

    #[Groups(['extra_data:read'])]
    #[Column(name: 'entity_name', type: 'string', nullable: false)]
    private string $entity_name;

    #[Groups(['extra_data:read'])]
    #[Column(name: 'data_value', type: 'string', nullable: false)]
    private string $value;

    public function __construct()
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getEntityId(): string
    {
        return $this->entity_id;
    }

    public function setEntityId(int | string $entity_id): self
    {
        $this->entity_id = $entity_id;
        return $this;
    }

    public function getEntityName(): string
    {
        return $this->entity_name;
    }

    public function setEntityName(string $entity_name): self
    {
        $this->entity_name = $entity_name;
        return $this;
    }

    public function getExtraFields(): ?ExtraFields
    {
        return $this->extra_fields;
    }

    public function setExtraFields(?ExtraFields $extra_fields): self
    {
        $this->extra_fields = $extra_fields;
        return $this;
    }
}