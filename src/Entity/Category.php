<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ControleOnline\Filter\CustomOrFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\Collection;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['category:write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(
            security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
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
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\CategoryRepository::class)]

class Category
{
    /**
     * @var integer
     *
     * @Groups({"product_category:read","logistic:read","invoice_details:read","category:read","task:read", "company_expense:read",
     * "model:read","model_detail:read",
     * "menu:read","invoice:read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact'])]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]

    private $id;
    /**
     * @var string
     *
     * @Groups({"product_category:read","menu:read","logistic:read","invoice_details:read","category:read","task:read", "category:write",
     * "model:read","model_detail:read",
     * "company_expense:read", "queue:read","invoice:read"})
     * @Assert\NotBlank
     * @Assert\Type(type={"string"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['name' => 'partial'])]
    #[ORM\Column(name: 'name', type: 'string', length: 100, nullable: false)]

    private $name;


    /**
     * @Groups({"category:read"})
     */
    #[ApiFilter(filterClass: ExistsFilter::class, properties: ['categoryFiles'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['categoryFiles.file.fileType' => 'exact'])]
    #[ORM\OneToMany(targetEntity: \CategoryFile::class, mappedBy: 'category')]

    private $categoryFiles;

    /**
     * @var string
     *
     * @Groups({"product_category:read","logistic:read","invoice_details:read","category:read","task:read", "category:write","menu:read",
     * "model:read","model_detail:read",
     * "queue:read","invoice:read"})
     * @Assert\NotBlank
     * @Assert\Type(type={"string"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['context' => 'exact'])]
    #[ORM\Column(name: 'context', type: 'string', length: 100, nullable: false)]

    private $context;
    /**
     * @var \ControleOnline\Entity\Category
     *
     * @Groups({"logistic:read","invoice_details:read","category:read","task:read", "category:write",
     * "model:read","model_detail:read",
     * "category:write","menu:read","queue:read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['parent' => 'exact'])]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\Category::class)]

    private $parent;
    /**
     * @var \ControleOnline\Entity\People
     *
     * @Groups({"product_category:read","logistic:read","invoice_details:read","category:read", "category:write","menu:read",
     * "model:read","model_detail:read",
     * "queue:read","invoice:read"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['company' => 'exact'])]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\People::class)]

    private $company;
    /**
     * @var string
     *
     * @Groups({"product_category:read","logistic:read","invoice_details:read","category:read","task:read", "category:write", "company_expense:read",
     * "model:read","model_detail:read",
     * "category:write","menu:read","queue:read","invoice:read"})  
     * @Assert\Type(type={"string"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['icon' => 'exact'])]
    #[ORM\Column(name: 'icon', type: 'string', length: 50, nullable: false)]

    private $icon;
    /**
     * @var string
     *
     * @Groups({"product_category:read","logistic:read","invoice_details:read","category:read","task:read", "category:write", "company_expense:read",
     * "model:read","model_detail:read",
     * "category:write","menu:read","queue:read","invoice:read"})  
     * @Assert\Type(type={"string"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['color' => 'exact'])]
    #[ORM\Column(name: 'color', type: 'string', length: 50, nullable: false)]

    private $color;


    public function __construct()
    {
        $this->categoryFiles = new \Doctrine\Common\Collections\ArrayCollection();
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
    /**
     * Get the value of icon
     */
    public function getIcon()
    {
        return $this->icon;
    }
    /**
     * Set the value of icon
     */
    public function setIcon($icon): self
    {
        $this->icon = $icon;
        return $this;
    }
    /**
     * Get the value of color
     */
    public function getColor()
    {
        return $this->color;
    }
    /**
     * Set the value of color
     */
    public function setColor($color): self
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return Collection|CategoryFile[]
     */
    public function getCategoryFiles(): Collection
    {
        return $this->categoryFiles;
    }
}
