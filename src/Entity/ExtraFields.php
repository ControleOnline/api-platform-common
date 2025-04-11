<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

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
#[ApiFilter(filterClass: SearchFilter::class, properties: ['context' => 'exact', 'field_type' => 'exact'])]
#[ORM\Table(name: 'extra_fields')]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\ExtraFieldsRepository::class)]
class ExtraFields
{
    /**
     * @var integer
     *
     * @Groups({"extra_fields:read", "extra_data:read"})
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @Groups({"extra_fields:read", "extra_fields:write", "extra_data:read"})
     */
    #[ORM\Column(name: 'field_name', type: 'string', length: 255, nullable: false)]
    private $name;
    /**
     * @Groups({"extra_fields:read", "extra_fields:write", "extra_data:read"})
     */
    #[ORM\Column(name: 'field_type', type: 'string', length: 255, nullable: false)]
    private $type;
    /**
     * @Groups({"extra_fields:read", "extra_fields:write", "extra_data:read"})
     */
    #[ORM\Column(name: 'context', type: 'string', length: 255, nullable: false)]
    private $context;
    /**
     * @Groups({"extra_fields:read", "extra_fields:write", "extra_data:read"})
     */
    #[ORM\Column(name: 'required', type: 'boolean', nullable: true)]
    private $required;
    /**
     * @Groups({"extra_fields:read", "extra_fields:write", "extra_data:read"})
     */
    #[ORM\Column(name: 'field_configs', type: 'string', nullable: true)]
    private $configs;
 

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the value of type
     */
    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the value of context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set the value of context
     */
    public function setContext($context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get the value of required
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * Set the value of required
     */
    public function setRequired($required): self
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Get the value of configs
     */
    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     * Set the value of configs
     */
    public function setConfigs($configs): self
    {
        $this->configs = $configs;

        return $this;
    }
}
