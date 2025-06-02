<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ControleOnline\Repository\FileRepository;
use ControleOnline\Listener\LogListener;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ControleOnline\Controller\GetFileDataAction;
use ControleOnline\Controller\FileUploadController;
use ControleOnline\Controller\FileConvertController;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Validator\Constraints\NotBlank;

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
#[Table(name: 'files')]
#[UniqueConstraint(name: 'url', columns: ['url'])]
#[UniqueConstraint(name: 'path', columns: ['path'])]
#[EntityListeners([LogListener::class])]
#[Entity(repositoryClass: FileRepository::class)]
class File
{
    #[Groups(['file:read', 'spool:read', 'order_details:read', 'order:write', 'category:read', 'product_category:read', 'order_product:read', 'product_file:read', 'product:read', 'spool_item:read', 'file_item:read', 'contract:read', 'model:read', 'people:read'])]
    #[Column(type: 'integer', nullable: false)]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private int $id = 0;

    #[Groups(['file:read', 'spool:read',  'order_details:read', 'order:write', 'category:read', 'product_category:read', 'order_product:read', 'product_file:read', 'product:read', 'spool_item:read', 'file_item:read', 'file:write', 'contract:read', 'model:read', 'people:read'])]
    #[NotBlank]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['fileType' => 'exact'])]
    #[Column(type: 'string', length: 255, nullable: false)]
    private string $fileType;

    #[Groups(['file:read', 'spool:read',  'order_details:read', 'order:write', 'category:read', 'product_category:read', 'order_product:read', 'product_file:read', 'product:read', 'spool_item:read', 'file_item:read', 'file:write', 'contract:read', 'model:read', 'people:read'])]
    #[NotBlank]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['fileName' => 'exact'])]
    #[Column(type: 'string', length: 255, nullable: false)]
    private string $fileName;

    #[Groups(['file:read', 'spool:read',  'order_details:read', 'order:write', 'category:read', 'product_category:read', 'order_product:read', 'product_file:read', 'product:read', 'spool_item:read', 'file_item:read', 'file:write', 'contract:read', 'model:read', 'people:read'])]
    #[NotBlank]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['context' => 'exact'])]
    #[Column(type: 'string', length: 255, nullable: false)]
    private string $context;

    #[Groups(['file:read', 'spool:read', 'order_details:read', 'order:write', 'category:read', 'product_category:read', 'order_product:read', 'product_file:read', 'product:read', 'spool_item:read', 'file_item:read', 'file:write', 'contract:read', 'model:read', 'people:read'])]
    #[NotBlank]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['extension' => 'exact'])]
    #[Column(type: 'string', length: 255, nullable: false)]
    private string $extension;

    #[Groups(['spool_item:read', 'file_item:read', 'file:write'])]
    #[Column(type: 'string', length: 255, nullable: false)]
    private string $content;

    #[Groups(['spool_item:read', 'file_item:read', 'file:write', 'file:read'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
    #[JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: true)]
    #[ManyToOne(targetEntity: People::class)]
    private ?People $people = null;

    public function __construct()
    {
        // Constructor remains empty after removing incorrect collection initialization
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): self
    {
        $this->fileType = $fileType;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getPeople(): ?People
    {
        return $this->people;
    }

    public function setPeople(?People $people): self
    {
        $this->people = $people;
        return $this;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;
        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function setContext(string $context): self
    {
        $this->context = $context;
        return $this;
    }
}
