<?php

namespace ControleOnline\Entity;

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

/**
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Table (name="extra_fields")
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\ExtraFieldsRepository")
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))',
            validationContext: ['groups' => ['extra_fields_write']],
            denormalizationContext: ['groups' => ['extra_fields_write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['extra_fields_read']],
    denormalizationContext: ['groups' => ['extra_fields_write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['context' => 'exact', 'field_type' => 'exact'])]
class ExtraFields
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @Groups({"extra_fields_read", "extra_data_read"})
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="field_name", type="string", length=255, nullable=false)
     * @Groups({"extra_fields_read", "extra_fields_write", "extra_data_read"})
     */
    private $field_name;
    /**
     * @ORM\Column(name="field_type", type="string", length=255, nullable=false)
     * @Groups({"extra_fields_read", "extra_fields_write", "extra_data_read"})
     */
    private $field_type;
    /**
     * @ORM\Column(name="context", type="string", length=255, nullable=false)
     * @Groups({"extra_fields_read", "extra_fields_write", "extra_data_read"})
     */
    private $context;
    /**
     * @ORM\Column(name="required", type="boolean", nullable=true)
     * @Groups({"extra_fields_read", "extra_fields_write", "extra_data_read"})
     */
    private $required;
    /**
     * @ORM\Column(name="field_configs", type="string", nullable=true)
     * @Groups({"extra_fields_read", "extra_fields_write", "extra_data_read"})
     */
    private $field_configs;
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

    public function setFieldName($value): self
    {
        $this->field_name = $value;
        return $this;
    }
    public function getFieldName()
    {
        return $this->field_name;
    }
    public function setFieldType($value): self
    {
        $this->field_type = $value;
        return $this;
    }
    public function getFieldType()
    {
        return $this->field_type;
    }
    public function setContext($value): self
    {
        $this->context = $value;
        return $this;
    }
    public function getContext()
    {
        return $this->context;
    }
    public function setRequired($value): self
    {
        $this->required = $value;
        return $this;
    }
    public function getRequired()
    {
        return $this->required;
    }
    public function setFieldConfigs($value): self
    {
        $this->field_configs = $value;
        return $this;
    }
    public function getFieldConfigs()
    {
        return $this->field_configs;
    }
}
