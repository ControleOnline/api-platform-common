<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;

use ControleOnline\Repository\ConnectionRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_HUMAN\')'),
        new Delete(security: 'is_granted(\'ROLE_HUMAN\')'),
        new GetCollection(security: 'is_granted(\'ROLE_HUMAN\')'),
        new Post(security: 'is_granted(\'ROLE_HUMAN\')'),
        new Put(
            security: 'is_granted(\'ROLE_HUMAN\')',
            denormalizationContext: ['groups' => ['connections:write']]
        ),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['connections:read']],
    denormalizationContext: ['groups' => ['connections:write']]
)]
#[Table(name: 'connections')]
#[Entity(repositoryClass: ConnectionRepository::class)]
class Connection
{
    /** Baileys / WHATSAPP_SERVER (default, backwards compatible). */
    public const CHANNEL_WHATSAPP = 'whatsapp';

    /** Meta WhatsApp Cloud API (PHP calls Graph directly). */
    public const CHANNEL_WHATSAPP_OFFICIAL = 'whatsapp_official';

    #[Groups(['connections:read'])]
    #[Column(name: 'id', type: 'integer', nullable: false)]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private int $id = 0;

    #[Groups(['connections:read', 'connections:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
    #[JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: false)]
    #[ManyToOne(targetEntity: People::class)]
    private ?People $people = null;

    #[Groups(['connections:read', 'connections:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['status' => 'exact'])]
    #[JoinColumn(name: 'status_id', referencedColumnName: 'id', nullable: false)]
    #[ManyToOne(targetEntity: Status::class)]
    private ?Status $status = null;

    #[Groups(['connections:read', 'connections:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['name' => 'exact'])]
    #[Column(name: 'name', type: 'string', length: 50, nullable: false)]
    private string $name;

    #[Groups(['connections:read', 'connections:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['type' => 'exact'])]
    #[Column(name: 'type', type: 'string', length: 50, nullable: true)]
    private string $type;

    #[Groups(['connections:read', 'connections:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['phone' => 'exact'])]
    #[JoinColumn(name: 'phone_id', referencedColumnName: 'id', nullable: true)]
    #[ManyToOne(targetEntity: Phone::class)]
    private ?Phone $phone = null;

    /**
     * Transport channel:
     * - whatsapp = unofficial (Baileys)
     * - whatsapp_official = Meta Cloud API
     */
    #[Groups(['connections:read', 'connections:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['channel' => 'exact'])]
    #[Column(
        name: 'channel',
        type: 'string',
        columnDefinition: "ENUM('whatsapp','whatsapp_official')",
        nullable: false
    )]
    private string $channel = self::CHANNEL_WHATSAPP;

    /**
     * Optional JSON for channel-specific credentials (official: access_token, phone_number_id, api_version).
     * Write-only in API groups — never expose token on read listings.
     */
    #[Groups(['connections:write'])]
    #[Column(name: 'channel_config', type: 'text', nullable: true)]
    private ?string $channelConfig = null;

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

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPhone(): ?Phone
    {
        return $this->phone;
    }

    public function setPhone(?Phone $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): self
    {
        if ($channel === self::CHANNEL_WHATSAPP_OFFICIAL) {
            $this->channel = self::CHANNEL_WHATSAPP_OFFICIAL;
        } else {
            $this->channel = self::CHANNEL_WHATSAPP;
        }
        return $this;
    }

    public function isOfficialWhatsApp(): bool
    {
        return $this->channel === self::CHANNEL_WHATSAPP_OFFICIAL;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getChannelConfig(): ?string
    {
        return $this->channelConfig;
    }

    public function setChannelConfig(?string $channelConfig): self
    {
        $this->channelConfig = $channelConfig;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getChannelConfigArray(): array
    {
        if (!$this->channelConfig) {
            return [];
        }
        $decoded = json_decode($this->channelConfig, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setChannelConfigArray(array $config): self
    {
        $this->channelConfig = $config === [] ? null : json_encode($config, JSON_UNESCAPED_UNICODE);
        return $this;
    }
}
