<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;

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
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' =>
    ['text/csv']],
    normalizationContext: ['groups' => ['extra_data:read']],
    denormalizationContext: ['groups' => ['extra_data:write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: [
    'id' => 'exact',
    'extra_fields' => 'exact',
    'entity_id' => 'exact', 'entity_name' => 'exact', 'people' => 'exact'
])]
#[ORM\Table(name: 'extra_data')]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\ExtraDataRepository::class)]

class ExtraData
{
    /**
     * @var integer
     *
     * @Groups({"extrafields:read", "extra_data:read"})
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;
    /**
     * @var \ControleOnline\Entity\ExtraFields
     *
     * @Groups({"extra_data:read"})
     */
    #[ORM\JoinColumn(name: 'extra_fields_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\ExtraFields::class)]
    private $extra_fields;

    /**
     * @Groups({"extra_data:read"})
     */
    #[ORM\Column(name: 'entity_id', type: 'string', nullable: false)]
    private $entity_id;

    /**
     * @Groups({"extra_data:read"})
     */
    #[ORM\Column(name: 'entity_name', type: 'string', nullable: false)]
    private $entity_name;

    /**
     * @Groups({"extra_data:read"})
     */
    #[ORM\Column(name: 'data_value', type: 'string', nullable: false)]
    private $value;
    /**
     * Constructor
     */
    public function __construct()
    {
    }
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }


    public function setValue($value): self
    {
        $this->value = $value;
        return $this;
    }
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the value of entity_id
     */
    public function getEntityId()
    {
        return $this->entity_id;
    }

    /**
     * Set the value of entity_id
     */
    public function setEntityId($entity_id): self
    {
        $this->entity_id = $entity_id;

        return $this;
    }

    /**
     * Get the value of entity_name
     */
    public function getEntityName()
    {
        return $this->entity_name;
    }

    /**
     * Set the value of entity_name
     */
    public function setEntityName($entity_name): self
    {
        $this->entity_name = $entity_name;

        return $this;
    }

    /**
     * Get the value of extra_fields
     */
    public function getExtraFields()
    {
        return $this->extra_fields;
    }

    /**
     * Set the value of extra_fields
     */
    public function setExtraFields($extra_fields): self
    {
        $this->extra_fields = $extra_fields;

        return $this;
    }
}
