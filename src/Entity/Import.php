<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ControleOnline\Repository\ImportRepository;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ControleOnline\Controller\ImportExampleCsvController;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use ControleOnline\Controller\ImportUploadController;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(
            uriTemplate: '/imports/upload',
            controller: ImportUploadController::class,
            deserialize: false,
            security: 'is_granted(\'ROLE_CLIENT\')'
        ),
        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/imports/example/{type}',
            controller: ImportExampleCsvController::class,
            read: false
        ),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['import:write']]
        ),
    ],
    normalizationContext: ['groups' => ['import:read']],
    denormalizationContext: ['groups' => ['import:write']]
)]

#[Entity(repositoryClass: ImportRepository::class)]
#[Table(name: 'imports')]
class Import
{

    #[Groups(['import:read'])]
    #[Column(name: 'id', type: 'integer')]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private int $id = 0;

    #[Groups(['import:read', 'import:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['importType' => 'exact'])]
    #[Column(name: 'import_type', type: 'string', length: 20)]
    private string $importType = 'table';

    #[Groups(['import:read', 'import:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['status' => 'exact'])]
    #[JoinColumn(name: 'status_id', referencedColumnName: 'id')]
    #[ManyToOne(targetEntity: Status::class)]
    private ?Status $status = null;

    #[Groups(['import:read', 'import:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['file' => 'exact'])]
    #[JoinColumn(name: 'file_id', referencedColumnName: 'id')]
    #[ManyToOne(targetEntity: File::class)]
    private ?File $file = null;

    #[Groups(['import:read', 'import:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
    #[JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ManyToOne(targetEntity: People::class)]
    private ?People $people = null;

    #[Groups(['import:read', 'import:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['fileFormat' => 'exact'])]
    #[Column(name: 'file_format', type: 'string', length: 10)]
    private string $fileFormat = 'csv';

    #[Groups(['import:read', 'import:write'])]
    #[Column(name: 'feedback', type: 'text', nullable: true)]
    private ?string $feedback = null;

    #[Groups(['import:read'])]
    #[Column(name: 'upload_date', type: 'datetime')]
    private \DateTimeInterface $uploadDate;

    public function __construct()
    {
        $this->uploadDate = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getImportType(): string
    {
        return $this->importType;
    }

    public function setImportType(string $importType): self
    {
        $this->importType = $importType;
        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;
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

    public function getFileFormat(): string
    {
        return $this->fileFormat;
    }

    public function setFileFormat(string $fileFormat): self
    {
        $this->fileFormat = $fileFormat;
        return $this;
    }

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(?string $feedback): self
    {
        $this->feedback = $feedback;
        return $this;
    }

    public function getUploadDate(): \DateTimeInterface
    {
        return $this->uploadDate;
    }

    public function setUploadDate(\DateTimeInterface $uploadDate): self
    {
        $this->uploadDate = $uploadDate;
        return $this;
    }
}
