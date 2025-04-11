<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
        ),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')'
        ),
        new Post(
            security: 'is_granted(\'ROLE_CLIENT\')'
        ),
        new Delete(
            security: 'is_granted(\'ROLE_CLIENT\')'
        ),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['model:read']],
    denormalizationContext: ['groups' => ['model:write']]
)]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\ModelRepository::class)]
class Model
{
    /**
     * @Groups({ "contract:read","model:read"})
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \ControleOnline\Entity\Category
     *
     * @Groups({"contract:read","model:read","model:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['category' => 'exact'])]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\Category::class)]

    private $category;
    /**
     * @var \ControleOnline\Entity\People
     *
     * @Groups({"contract:read","model:read","model:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\People::class)]

    private $people;


    /**
     * @var \ControleOnline\Entity\People
     *
     * @Groups({"contract:read","model:read","model:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['signer' => 'exact'])]
    #[ORM\JoinColumn(name: 'signer_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\People::class)]

    private $signer;

    /**
     * @var \ControleOnline\Entity\File
     *
     * @Groups({"model_detail:read","model:read","model:write"})
     */
    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\File::class)]
    private $file;

    /**
     * @Groups({"contract:read","model:read","model:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['context' => 'exact'])]
    #[ORM\Column(name: 'context', type: 'string')]
    private $context;

    /**
     * @Groups({"contract:read","model:read","model:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['model' => 'partial'])]
    #[ORM\Column(name: 'model', type: 'string')]
    private $model;


    public function getId()
    {
        return $this->id;
    }



    /**
     * Get the value of people
     */
    public function getPeople(): \ControleOnline\Entity\People
    {
        return $this->people;
    }

    /**
     * Set the value of people
     */
    public function setPeople(\ControleOnline\Entity\People $people): self
    {
        $this->people = $people;

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



    /**
     * Get the value of model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set the value of model
     */
    public function setModel($model): self
    {
        $this->model = $model;

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
     * Get the value of signer
     */
    public function getSigner()
    {
        return $this->signer;
    }

    /**
     * Set the value of signer
     */
    public function setSigner($signer): self
    {
        $this->signer = $signer;

        return $this;
    }

    /**
     * Get the value of file
     */
    public function getFile(): \ControleOnline\Entity\File
    {
        return $this->file;
    }

    /**
     * Set the value of file
     */
    public function setFile(\ControleOnline\Entity\File $file): self
    {
        $this->file = $file;

        return $this;
    }
}
