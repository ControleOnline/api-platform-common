<?php

namespace ControleOnline\Entity;

use ControleOnline\Repository\DistrictRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ControleOnline\Listener\LogListener;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['district:read']],
    denormalizationContext: ['groups' => ['district:write']]
)]
#[Table(name: 'district')]
#[Index(name: 'city_id', columns: ['city_id'])]
#[EntityListeners([LogListener::class])]
#[Entity(repositoryClass: DistrictRepository::class)]
class District
{
    #[Groups(['people:read', 'order_details:read', 'order:write', 'address:read'])]
    #[Column(name: 'id', type: 'integer', nullable: false)]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[Groups(['people:read', 'order_details:read', 'order:write', 'address:read'])]
    #[Column(name: 'district', type: 'string', length: 255, nullable: false)]
    private string $district;

    #[Groups(['people:read', 'order_details:read', 'order:write', 'address:read'])]
    #[JoinColumn(name: 'city_id', referencedColumnName: 'id', nullable: false)]
    #[ManyToOne(targetEntity: City::class, inversedBy: 'district')]
    private City $city;

    #[OneToMany(targetEntity: Street::class, mappedBy: 'district')]
    private Collection $street;

    public function __construct()
    {
        $this->street = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setDistrict(string $district): self
    {
        $this->district = $district;
        return $this;
    }

    public function getDistrict(): string
    {
        return strtoupper($this->district);
    }

    public function setCity(City $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getCity(): City
    {
        return $this->city;
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