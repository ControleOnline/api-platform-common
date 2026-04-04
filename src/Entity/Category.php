<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ControleOnline\Filter\CustomOrFilter;

use ControleOnline\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'PUBLIC_ACCESS\')'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['category:write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(
            security: 'is_granted(\'PUBLIC_ACCESS\')',
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['category:read']],
    denormalizationContext: ['groups' => ['category:write']]
)]
#[ApiFilter(filterClass: ExistsFilter::class, properties: ['parent'])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['name'])]
#[ApiFilter(CustomOrFilter::class, properties: ['name', 'id', 'icon', 'color'])]
#[ORM\Table(name: 'category')]

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact'])]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['category:read', 'company_expense:read', 'invoice:read', 'invoice_details:read', 'logistic:read', 'menu:read', 'model:read', 'model_detail:read', 'product_category:read', 'task:read'])]
    private $id;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['name' => 'partial'])]
    #[ORM\Column(name: 'name', type: 'string', length: 100, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    #[Groups(['category:read', 'category:write', 'company_expense:read', 'invoice:read', 'invoice_details:read', 'logistic:read', 'menu:read', 'model:read', 'model_detail:read', 'product_category:read', 'queue:read', 'task:read'])]
    private $name;

    #[ApiFilter(filterClass: ExistsFilter::class, properties: ['categoryFiles'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['categoryFiles.file.fileType' => 'exact'])]
    #[ORM\OneToMany(targetEntity: CategoryFile::class, mappedBy: 'category')]
    #[Groups(['category:read'])]
    private $categoryFiles;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['context' => 'exact'])]
    #[ORM\Column(name: 'context', type: 'string', length: 100, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    #[Groups(['category:read', 'category:write', 'invoice:read', 'invoice_details:read', 'logistic:read', 'menu:read', 'model:read', 'model_detail:read', 'product_category:read', 'queue:read', 'task:read'])]
    private $context;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['parent' => 'exact'])]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[Groups(['category:read', 'category:write', 'invoice_details:read', 'logistic:read', 'menu:read', 'model:read', 'model_detail:read', 'queue:read', 'task:read'])]
    private $parent;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['company' => 'exact'])]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: People::class)]
    #[Assert\NotBlank]
    #[Groups(['category:read', 'category:write', 'invoice:read', 'invoice_details:read', 'logistic:read', 'menu:read', 'model:read', 'model_detail:read', 'product_category:read', 'queue:read'])]
    private $company;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['icon' => 'exact'])]
    #[ORM\Column(name: 'icon', type: 'string', length: 50, nullable: true)]
    #[Assert\Type(type: 'string')]
    #[Groups(['category:read', 'category:write', 'company_expense:read', 'invoice:read', 'invoice_details:read', 'logistic:read', 'menu:read', 'model:read', 'model_detail:read', 'product_category:read', 'queue:read', 'task:read'])]
    private $icon;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['color' => 'exact'])]
    #[ORM\Column(name: 'color', type: 'string', length: 50, nullable: true)]
    #[Assert\Type(type: 'string')]
    #[Groups(['category:read', 'category:write', 'company_expense:read', 'invoice:read', 'invoice_details:read', 'logistic:read', 'menu:read', 'model:read', 'model_detail:read', 'product_category:read', 'queue:read', 'task:read'])]
    private $color;

    public function __construct()
    {
        $this->categoryFiles = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setParent(Category $category = null)
    {
        $this->parent = $category;
        return $this;
    }

    public function getParent(): ?Category
    {
        return $this->parent;
    }

    public function setCompany(People $company)
    {
        $this->company = $company;
        return $this;
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function setIcon($icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setColor($color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getCategoryFiles(): Collection
    {
        return $this->categoryFiles;
    }
}
