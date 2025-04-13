<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ControleOnline\Listener\LogListener;
use ControleOnline\Repository\ModelRepository;
use Doctrine\ORM\Mapping as ORM;

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
#[ORM\Entity(repositoryClass: ModelRepository::class)]
class Model
{
    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ApiResource(normalizationContext: ['groups' => ['contract:read', 'model:read']])]
    private $id;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['category' => 'exact'])]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ApiResource(normalizationContext: ['groups' => ['contract:read', 'model:read', 'model:write']])]
    private $category;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ApiResource(normalizationContext: ['groups' => ['contract:read', 'model:read', 'model:write']])]
    private $people;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['signer' => 'exact'])]
    #[ORM\JoinColumn(name: 'signer_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ApiResource(normalizationContext: ['groups' => ['contract:read', 'model:read', 'model:write']])]
    private $signer;

    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: File::class)]
    #[ApiResource(normalizationContext: ['groups' => ['model_detail:read', 'model:read', 'model:write']])]
    private $file;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['context' => 'exact'])]
    #[ORM\Column(name: 'context', type: 'string')]
    #[ApiResource(normalizationContext: ['groups' => ['contract:read', 'model:read', 'model:write']])]
    private $context;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['model' => 'partial'])]
    #[ORM\Column(name: 'model', type: 'string')]
    #[ApiResource(normalizationContext: ['groups' => ['contract:read', 'model:read', 'model:write']])]
    private $model;

    public function getId()
    {
        return $this->id;
    }

    public function getPeople(): People
    {
        return $this->people;
    }

    public function setPeople(People $people): self
    {
        $this->people = $people;
        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setModel($model): self
    {
        $this->model = $model;
        return $this;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setContext($context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getSigner()
    {
        return $this->signer;
    }

    public function setSigner($signer): self
    {
        $this->signer = $signer;
        return $this;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function setFile(File $file): self
    {
        $this->file = $file;
        return $this;
    }
}