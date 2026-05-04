<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ControleOnline\Repository\TimezoneRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_HUMAN') or is_granted('ROLE_CLIENT')"
        ),
        new Get(
            security: "is_granted('ROLE_HUMAN') or is_granted('ROLE_CLIENT')"
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['timezone:read']],
    denormalizationContext: ['groups' => ['timezone:write']]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['name' => 'ASC'])]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'id' => 'exact',
        'name' => 'partial'
    ]
)]
#[ORM\Table(name: 'timezones')]
#[ORM\UniqueConstraint(name: 'uk_timezones_name', columns: ['name'])]
#[ORM\Entity(repositoryClass: TimezoneRepository::class)]
class Timezone
{
    #[ORM\Column(name: 'id', type: 'smallint', options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['timezone:read'])]
    private int $id = 0;

    #[ORM\Column(name: 'name', type: 'string', length: 64)]
    #[Groups(['timezone:read'])]
    private string $name = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
