<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ControleOnline\Repository\CepRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ControleOnline\Listener\LogListener;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ApiResource(
    operations: [new Get(security: 'is_granted(\'ROLE_CLIENT\')')],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['cep:read']],
    denormalizationContext: ['groups' => ['cep:write']]
)]
#[Table(name: 'cep')]
#[UniqueConstraint(name: 'CEP', columns: ['cep'])]
#[EntityListeners([LogListener::class])]
#[Entity(repositoryClass: CepRepository::class)]
class Cep
{
    #[Groups(['people:read', 'order_details:read', 'order:write', 'address:read'])]
    #[Column(name: 'id', type: 'integer', nullable: false)]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[Groups(['people:read', 'order_details:read', 'order:write', 'address:read'])]
    #[Column(name: 'cep', type: 'integer', nullable: false)]
    private int $cep;

    #[OneToMany(targetEntity: Street::class, mappedBy: 'cep')]
    private Collection $street;

    public function __construct()
    {
        $this->street = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setCep(int $cep): self
    {
        $this->cep = $cep;
        return $this;
    }

    public function getCep(): string
    {
        return str_pad((string) $this->cep, 8, "0", STR_PAD_LEFT);
    }

    public function addStreet(Street $street): self
    {
        if (!$this->street->contains($street)) {
            $this->street[] = $street;
        }
        return $this;
    }

    public function removeStreet(Street $street): self
    {
        $this->street->removeElement($street);
        return $this;
    }

    public function getStreet(): Collection
    {
        return $this->street;
    }
}