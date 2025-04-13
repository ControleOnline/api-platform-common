<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ControleOnline\Repository\DeviceRepository;
use ControleOnline\Listener\LogListener;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['device:write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(
            security: 'is_granted(\'PUBLIC_ACCESS\')',
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['device:read']],
    denormalizationContext: ['groups' => ['device:write']]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['id' => 'ASC'])]
#[Table(name: 'device')]
#[EntityListeners([LogListener::class])]
#[Entity(repositoryClass: DeviceRepository::class)]
class Device
{
    #[Groups(['device:read', 'device:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact'])]
    #[Column(name: 'id', type: 'integer', nullable: false)]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[Groups(['device:read', 'device:write'])]
    #[NotBlank]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['device' => 'exact'])]
    #[Column(name: 'device', type: 'string', length: 100, nullable: false)]
    private string $device = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function getDevice(): string
    {
        return $this->device;
    }

    public function setDevice(string $device): self
    {
        $this->device = $device;
        return $this;
    }
}
