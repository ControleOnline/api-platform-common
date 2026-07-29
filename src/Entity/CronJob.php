<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ControleOnline\Controller\DiscoveryCronJobCommandsAction;
use ControleOnline\Repository\CronJobRepository;
use ControleOnline\State\CronJobProvider;
use ControleOnline\State\CronJobPersistProcessor;
use Cron\CronExpression;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            security: 'is_granted(\'ROLE_SUPER\')',
            provider: CronJobProvider::class
        ),
        new GetCollection(
            security: 'is_granted(\'ROLE_SUPER\')',
            provider: CronJobProvider::class
        ),
        new Post(
            security: 'is_granted(\'ROLE_SUPER\')',
            processor: CronJobPersistProcessor::class
        ),
        new Put(
            security: 'is_granted(\'ROLE_SUPER\')',
            processor: CronJobPersistProcessor::class
        ),
        new Delete(
            security: 'is_granted(\'ROLE_SUPER\')',
            processor: CronJobPersistProcessor::class
        ),
        new GetCollection(
            uriTemplate: '/cron_jobs/commands',
            controller: DiscoveryCronJobCommandsAction::class,
            read: false,
            security: 'is_granted(\'ROLE_SUPER\')'
        ),
    ],
    normalizationContext: ['groups' => ['cron_job:read']],
    denormalizationContext: ['groups' => ['cron_job:write']]
)]
#[ApiFilter(OrderFilter::class, properties: [
    'id' => 'ASC',
    'title' => 'ASC',
    'command' => 'ASC',
    'cronExpression' => 'ASC',
    'enabled' => 'ASC',
])]
#[ApiFilter(SearchFilter::class, properties: [
    'people' => 'exact',
    'title' => 'partial',
    'command' => 'partial',
    'enabled' => 'exact',
])]
#[ORM\Entity(repositoryClass: CronJobRepository::class)]
#[ORM\Table(name: 'cron_jobs')]
class CronJob
{
    #[Groups(['cron_job:read'])]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id = 0;

    #[Groups(['cron_job:read'])]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: People::class)]
    private ?People $people = null;

    #[Groups(['cron_job:read', 'cron_job:write'])]
    #[ORM\Column(name: 'database_id', type: 'integer', nullable: true)]
    private ?int $databaseId = null;

    #[Groups(['cron_job:read', 'cron_job:write'])]
    #[ORM\Column(name: 'server_id', type: 'integer', nullable: true)]
    private ?int $serverId = null;

    #[Groups(['cron_job:read', 'cron_job:write'])]
    #[ORM\Column(name: 'scope', type: 'string', length: 20, nullable: false, options: ['default' => 'tenant'])]
    private string $scope = 'tenant';

    #[Groups(['cron_job:read', 'cron_job:write'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    private string $title = '';

    #[Groups(['cron_job:read', 'cron_job:write'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: false)]
    private string $description = '';

    #[Groups(['cron_job:read', 'cron_job:write'])]
    #[ORM\Column(name: 'enabled', type: 'boolean', options: ['default' => true])]
    private bool $enabled = true;

    #[Groups(['cron_job:read', 'cron_job:write'])]
    #[ORM\Column(name: 'cron_expression', type: 'string', length: 120, nullable: false)]
    private string $cronExpression = '';

    #[Groups(['cron_job:read', 'cron_job:write'])]
    #[ORM\Column(name: 'command', type: 'string', length: 255, nullable: false)]
    private string $command = '';

    #[Groups(['cron_job:read', 'cron_job:write'])]
    #[ORM\Column(name: 'arguments', type: 'json', nullable: false)]
    private array $arguments = [];

    #[Groups(['cron_job:read'])]
    #[ORM\Column(name: 'last_execution_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastExecutionAt = null;

    #[Groups(['cron_job:read'])]
    #[ORM\Column(name: 'last_status', type: 'string', length: 20, nullable: true)]
    private ?string $lastStatus = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = max(0, $id);

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

    public function getDatabaseId(): ?int
    {
        return $this->databaseId;
    }

    public function setDatabaseId(?int $databaseId): self
    {
        $this->databaseId = $databaseId !== null && $databaseId > 0 ? $databaseId : null;

        return $this;
    }

    public function getServerId(): ?int
    {
        return $this->serverId;
    }

    public function setServerId(?int $serverId): self
    {
        $this->serverId = $serverId !== null && $serverId > 0 ? $serverId : null;

        return $this;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope): self
    {
        $scope = strtolower(trim($scope));
        $this->scope = in_array($scope, ['master', 'tenant'], true) ? $scope : 'tenant';

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = trim($description);

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

    public function getCronExpression(): string
    {
        return $this->cronExpression;
    }

    public function setCronExpression(string $cronExpression): self
    {
        $this->cronExpression = trim($cronExpression);

        return $this;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setCommand(string $command): self
    {
        $this->command = trim($command);

        return $this;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments): self
    {
        $this->arguments = array_values(array_filter(
            array_map(
                static fn(mixed $argument): string => trim((string) $argument),
                $arguments
            ),
            static fn(string $argument): bool => $argument !== ''
        ));

        return $this;
    }

    public function getLastExecutionAt(): ?\DateTimeImmutable
    {
        return $this->lastExecutionAt;
    }

    public function setLastExecutionAt(mixed $lastExecutionAt): self
    {
        if ($lastExecutionAt instanceof \DateTimeImmutable) {
            $this->lastExecutionAt = $lastExecutionAt;

            return $this;
        }

        if ($lastExecutionAt instanceof \DateTimeInterface) {
            $this->lastExecutionAt = \DateTimeImmutable::createFromInterface($lastExecutionAt);

            return $this;
        }

        $lastExecutionAt = trim((string) $lastExecutionAt);
        if ($lastExecutionAt === '') {
            $this->lastExecutionAt = null;

            return $this;
        }

        try {
            $this->lastExecutionAt = new \DateTimeImmutable($lastExecutionAt);
        } catch (\Throwable) {
            $this->lastExecutionAt = null;
        }

        return $this;
    }

    public function getLastStatus(): ?string
    {
        return $this->lastStatus;
    }

    public function setLastStatus(?string $lastStatus): self
    {
        $lastStatus = trim((string) $lastStatus);
        $this->lastStatus = $lastStatus !== '' ? $lastStatus : null;

        return $this;
    }

    #[Groups(['cron_job:read'])]
    public function getIsValid(): bool
    {
        $cronExpression = trim($this->cronExpression);
        if ($cronExpression === '') {
            return false;
        }

        try {
            CronExpression::factory($cronExpression);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
