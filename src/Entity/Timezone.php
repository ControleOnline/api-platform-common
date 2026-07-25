<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ControleOnline\Repository\TimezoneRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [

        /**
         * ✅ Endpoint público
         * GET /api/timezones
         * Permite filtrar enabled pelo BooleanFilter padrão da API
         */
        new GetCollection(
            uriTemplate: '/timezones',
            security: "is_granted('ROLE_HUMAN') or is_granted('ROLE_CLIENT')"
        ),

        /**
         * ✅ Endpoint administrativo
         * GET /api/admin/timezones
         * Retorna todos
         */
        new GetCollection(
            uriTemplate: '/admin/timezones',
            security: "is_granted('ROLE_ADMIN')"
        ),

        /**
         * ✅ GET individual (admin)
         */
        new Get(
            security: "is_granted('ROLE_ADMIN')"
        ),

        /**
         * ✅ Permite ativar/desativar
         */
        new Patch(
            security: "is_granted('ROLE_ADMIN')"
        )
    ],

    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['timezone:read']],
    denormalizationContext: ['groups' => ['timezone:write']]
)]

#[ApiFilter(OrderFilter::class, properties: ['name' => 'ASC'])]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'name' => 'partial'
])]
#[ApiFilter(BooleanFilter::class, properties: ['enabled'])]

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

    #[ORM\Column(name: 'utc_offset', type: 'string', length: 10)]
    #[Groups(['timezone:read'])]
    private string $utcOffset = 'UTC +00:00';

    #[ORM\Column(name: 'enabled', type: 'boolean', options: ['default' => 0])]
    #[Groups(['timezone:read', 'timezone:write'])]
    private bool $enabled = false;

    public function getId(): int
    {
        return $this->id;
    }

    #[Groups(['timezone:read'])]
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    #[Groups(['timezone:read'])]
    public function getUtcOffset(): string
    {
        return $this->utcOffset;
    }

    public function setUtcOffset(string $utcOffset): self
    {
        $this->utcOffset = $utcOffset;
        return $this;
    }

    /**
     * Exibição amigável
     * (UTC-03:00) America/Sao_Paulo
     */
    #[Groups(['timezone:read'])]
    public function getDisplayName(): string
    {
        return sprintf('(%s) %s', $this->utcOffset, $this->name);
    }

    #[Groups(['timezone:read'])]
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
