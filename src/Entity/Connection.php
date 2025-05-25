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
use ControleOnline\Listener\LogListener;
use ControleOnline\Repository\ConnectionsRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')'),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['connections:write']]
        ),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['connections:read']],
    denormalizationContext: ['groups' => ['connections:write']]
)]
#[Table(name: 'connections')]
#[EntityListeners([LogListener::class])]
#[Entity(repositoryClass: ConnectionsRepository::class)]
class Connections
{
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
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['phone' => 'exact'])]
    #[JoinColumn(name: 'phone_id', referencedColumnName: 'id', nullable: true)]
    #[ManyToOne(targetEntity: Phone::class)]
    private ?Phone $phone = null;

    #[Groups(['connections:read', 'connections:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['channel' => 'exact'])]
    #[Column(name: 'channel', type: 'string', columnDefinition: "ENUM('whatsapp')", nullable: false)]
    private string $channel;

    // Getters e Setters

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
        $this->channel = $channel;
        return $this;
    }
}