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
use ControleOnline\State\CronJobPersistProcessor;
use Cron\CronExpression;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_SUPER\')'),
        new GetCollection(security: 'is_granted(\'ROLE_SUPER\')'),
        new Post(
            security: 'is_granted(\'ROLE_SUPER\')',
            processor: CronJobPersistProcessor::class
        ),
        new Put(
            security: 'is_granted(\'ROLE_SUPER\')',
            processor: CronJobPersistProcessor::class
        ),
        new Delete(security: 'is_granted(\'ROLE_SUPER\')'),
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
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: People::class)]
    private ?People $people = null;

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

    public function getId(): int
    {
        return $this->id;
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
