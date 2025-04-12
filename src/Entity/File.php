<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use ControleOnline\Controller\GetFileDataAction;
use ControleOnline\Controller\FileUploadController;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ControleOnline\Controller\FileConvertController;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * File
 */
#[ApiResource(
    operations: [
        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            normalizationContext: ['groups' => ['file_item:read']],
        ),
        new Get(
            security: 'is_granted(\'PUBLIC_ACCESS\')',
            uriTemplate: '/files/{id}/download',
            controller: GetFileDataAction::class
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/files/upload',
            controller: FileUploadController::class,
            deserialize: false
        ),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))',
            validationContext: ['groups' => ['file:write']],
            denormalizationContext: ['groups' => ['file:write']]
        ),
        new Post(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))',
            uriTemplate: '/files/{id}/convert',
            controller: FileConvertController::class,
            deserialize: false
        ),
    ],
    normalizationContext: ['groups' => ['file:read']],
    denormalizationContext: ['groups' => ['file:write']]
)]
#[ORM\Table(name: 'files')]
#[ORM\UniqueConstraint(name: 'url', columns: ['url'])]
#[ORM\UniqueConstraint(name: 'path', columns: ['path'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\FileRepository::class)]
class File
{
    /**
     *
     * @Groups({"file:read","category:read","product_category:read","order_product:read","product_file:read","product:read","file_item:read","product:read","contract:read","model:read","people:read"})
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @Groups({"file:read","category:read","product_category:read","order_product:read","product_file:read","product:read","file_item:read","product:read","file:write","contract:read","model:read","people:read"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['fileType' => 'exact'])]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private $fileType;

    /**
     * @Groups({"file:read","category:read","product_category:read","order_product:read","product_file:read","product:read","file_item:read","product:read","file:write","contract:read","model:read","people:read"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['fileName' => 'exact'])]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private $fileName;

    /**
     * @Groups({"file:read","category:read","product_category:read","order_product:read","product_file:read","product:read","file_item:read","product:read","file:write","contract:read","model:read","people:read"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['context' => 'exact'])]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private $context;

    /**
     * @Groups({"file:read","category:read","product_category:read","order_product:read","product_file:read","product:read","file_item:read","product:read","file:write","contract:read","model:read","people:read"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['extension' => 'exact'])]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private $extension;

    /**
     * @Groups({"file_item:read","file:write"})
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private $content;

    /**
     * @var \ControleOnline\Entity\People
     *
     * @Groups({"file_item:read","file:write","file:read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\People::class)]
    private $people;

    public function __construct()
    {
        $this->people = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }



    /**
     * Get the value of fileType
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * Set the value of fileType
     */
    public function setFileType($fileType): self
    {
        $this->fileType = $fileType;

        return $this;
    }

    /**
     * Get the value of content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set the value of content
     */
    public function setContent($content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get the value of people
     */
    public function getPeople()
    {
        return $this->people;
    }

    /**
     * Set the value of people
     */
    public function setPeople($people): self
    {
        $this->people = $people;

        return $this;
    }

    /**
     * Get the value of extension
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Set the value of extension
     */
    public function setExtension($extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * Get the value of fileName
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set the value of fileName
     */
    public function setFileName($fileName): self
    {
        $this->fileName = $fileName;

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
}
