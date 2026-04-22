<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ControleOnline\Controller\FrontendDebugLogAction;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'log')]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/logs/frontend-debug',
            controller: FrontendDebugLogAction::class,
            deserialize: false,
            security: "is_granted('ROLE_CLIENT')",
        ),
        new GetCollection(
            security: "is_granted('ROLE_CLIENT')",
        ),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['log:read']],
    security: "is_granted('ROLE_CLIENT')",
    order: ['createdAt' => 'DESC', 'id' => 'DESC']
)]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'type' => 'exact',
    'action' => 'exact',
    'class' => 'partial',
    'row' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: [
    'createdAt',
    'id',
])]
#[ApiFilter(DateFilter::class, properties: [
    'createdAt',
])]
class Log
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    #[Groups(['log:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'entity'])]
    #[Groups(['log:read'])]
    private string $type = 'entity';

    #[ORM\Column(type: 'string')]
    #[Groups(['log:read'])]
    private string $action;

    #[ORM\Column(name: 'class', type: 'string', nullable: true)]
    #[Groups(['log:read'])]
    private ?string $class = null;

    #[ORM\Column(name: 'row', type: 'integer', nullable: true)]
    #[Groups(['log:read'])]
    private ?int $row = null;

    #[ORM\Column(type: 'text')]
    private string $object;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', insertable: false, updatable: false)]
    #[Groups(['log:read'])]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(?string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function getObject(): string
    {
        return $this->object;
    }

    public function setObject(string $object): self
    {
        $this->object = $object;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getRow(): ?int
    {
        return $this->row;
    }

    public function setRow(?int $row): self
    {
        $this->row = $row;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[Groups(['log:read'])]
    public function getPayload(): array
    {
        $decoded = json_decode($this->object, true);

        return is_array($decoded) ? $decoded : [];
    }

    #[Groups(['log:read'])]
    public function getLevel(): string
    {
        return $this->action;
    }

    #[Groups(['log:read'])]
    public function getChannel(): ?string
    {
        $payload = $this->getPayload();
        $channel = $payload['channel'] ?? null;

        return is_string($channel) && trim($channel) !== '' ? trim($channel) : null;
    }

    #[Groups(['log:read'])]
    public function getMessage(): ?string
    {
        $payload = $this->getPayload();
        $message = $payload['message'] ?? null;

        return is_string($message) && trim($message) !== '' ? trim($message) : null;
    }

    #[Groups(['log:read'])]
    public function getContextData(): array
    {
        $payload = $this->getPayload();
        $context = $payload['context'] ?? [];

        return is_array($context) ? $context : [];
    }

    #[Groups(['log:read'])]
    public function getUserDisplayName(): ?string
    {
        return $this->user?->getPeople()?->getAlias()
            ?: $this->user?->getPeople()?->getName()
            ?: $this->user?->getUsername();
    }

    #[Groups(['log:read'])]
    public function getEntityShortName(): string
    {
        if (!$this->class) {
            return 'Log';
        }

        $classParts = explode('\\', $this->class);

        return end($classParts) ?: $this->class;
    }
}
