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
use ControleOnline\Repository\CategoryFileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['category_file:write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['category_file:read']],
    denormalizationContext: ['groups' => ['category_file:write']]
)]
#[ORM\Table(name: 'category_file')]
#[ORM\Index(name: 'file_id', columns: ['file_id'])]
#[ORM\Index(name: 'IDX_CDFC73564584665B', columns: ['category_id'])]
#[ORM\UniqueConstraint(name: 'category_id', columns: ['category_id', 'file_id'])]
#[ORM\Entity(repositoryClass: CategoryFileRepository::class)]
class CategoryFile
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ApiResource(normalizationContext: ['groups' => ['category:read', 'category_file:read']])]
    private $id;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['file' => 'exact', 'file.fileType' => 'exact'])]
    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: File::class)]
    #[ApiResource(normalizationContext: ['groups' => ['category:read', 'category_file:read', 'category_file:write']])]
    private $file;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['category' => 'exact'])]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ApiResource(normalizationContext: ['groups' => ['category_file:read', 'category_file:write']])]
    private $category;

    public function getId()
    {
        return $this->id;
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

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;
        return $this;
    }
}