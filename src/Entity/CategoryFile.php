<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;


/**
 * CategoryFile
 */
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
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\CategoryFileRepository::class)]
class CategoryFile
{
    /**
     * @var int
     *
     * @Groups({"category:read","category_file:read"})
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var ControleOnline\Entity\File
     *
     * @Groups({"category:read","category_file:read","category_file:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['file' => 'exact'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['file.fileType' => 'exact'])]
    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\File::class)]

    private $file;

    /**
     * @var Category
     *
     * @Groups({"category_file:read","category_file:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['category' => 'exact'])]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Category::class)]

    private $category;

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of file
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * Set the value of file
     */
    public function setFile(File $file): self
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get the value of category
     */
    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * Set the value of category
     */
    public function setCategory(Category $category): self
    {
        $this->category = $category;

        return $this;
    }
}
