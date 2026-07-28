<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ControleOnline\Repository\FlowchartRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_SUPER\')'),
        new GetCollection(security: 'is_granted(\'ROLE_SUPER\')'),
        new Post(security: 'is_granted(\'ROLE_SUPER\')'),
        new Put(
            security: 'is_granted(\'ROLE_SUPER\')',
            denormalizationContext: ['groups' => ['flowchart:write']]
        ),
        new Patch(
            security: 'is_granted(\'ROLE_SUPER\')',
            denormalizationContext: ['groups' => ['flowchart:write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_SUPER\')'),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['flowchart:read']],
    denormalizationContext: ['groups' => ['flowchart:write']]
)]
#[ApiFilter(OrderFilter::class, properties: ['sortOrder' => 'ASC', 'title' => 'ASC', 'id' => 'ASC'])]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'flowKey' => 'exact',
    'appType' => 'exact',
    'enabled' => 'exact',
    'title' => 'partial',
])]
#[ORM\Table(name: 'flowchart')]
#[ORM\Index(name: 'flowchart_app_type_idx', columns: ['app_type'])]
#[ORM\UniqueConstraint(name: 'flowchart_app_key_unique', columns: ['app_type', 'flow_key'])]
#[ORM\Entity(repositoryClass: FlowchartRepository::class)]
class Flowchart
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['flowchart:read'])]
    private int $id = 0;

    #[ORM\Column(name: 'flow_key', type: 'string', length: 100, nullable: false)]
    #[Groups(['flowchart:read', 'flowchart:write'])]
    private string $flowKey = '';

    #[ORM\Column(name: 'app_type', type: 'string', length: 30, nullable: false, options: ['default' => 'ADMIN'])]
    #[Groups(['flowchart:read', 'flowchart:write'])]
    private string $appType = 'ADMIN';

    #[ORM\Column(name: 'title', type: 'string', length: 120, nullable: false)]
    #[Groups(['flowchart:read', 'flowchart:write'])]
    private string $title = '';

    #[ORM\Column(name: 'summary', type: 'string', length: 255, nullable: true)]
    #[Groups(['flowchart:read', 'flowchart:write'])]
    private ?string $summary = null;

    #[ORM\Column(name: 'mermaid', type: 'text', nullable: false)]
    #[Groups(['flowchart:read', 'flowchart:write'])]
    private string $mermaid = '';

    #[ORM\Column(name: 'checkpoints', type: 'json', nullable: false)]
    #[Groups(['flowchart:read', 'flowchart:write'])]
    private array $checkpoints = [];

    #[ORM\Column(name: 'sort_order', type: 'integer', nullable: false, options: ['default' => 0])]
    #[Groups(['flowchart:read', 'flowchart:write'])]
    private int $sortOrder = 0;

    #[ORM\Column(name: 'enabled', type: 'boolean', nullable: false, options: ['default' => true])]
    #[Groups(['flowchart:read', 'flowchart:write'])]
    private bool $enabled = true;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFlowKey(): string
    {
        return $this->flowKey;
    }

    public function setFlowKey(string $flowKey): self
    {
        $this->flowKey = trim($flowKey);

        return $this;
    }

    public function getAppType(): string
    {
        return $this->appType;
    }

    public function setAppType(string $appType): self
    {
        $this->appType = strtoupper(trim($appType));

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): self
    {
        $summary = $summary === null ? null : trim($summary);
        $this->summary = $summary === '' ? null : $summary;

        return $this;
    }

    public function getMermaid(): string
    {
        return $this->mermaid;
    }

    public function setMermaid(string $mermaid): self
    {
        $this->mermaid = $mermaid;

        return $this;
    }

    public function getCheckpoints(): array
    {
        return $this->checkpoints;
    }

    public function setCheckpoints(array $checkpoints): self
    {
        $this->checkpoints = array_values($checkpoints);

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }
}
