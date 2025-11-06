<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ControleOnline\Repository\CountryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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
    normalizationContext: ['groups' => ['country:read']],
    denormalizationContext: ['groups' => ['country:write']]
)]
#[Table(name: 'country')]
#[UniqueConstraint(name: 'countryCode', columns: ['countryCode'])]

#[Entity(repositoryClass: CountryRepository::class)]
class Country
{
    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'id', type: 'integer', nullable: false)]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private int $id = 0;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'countryCode', type: 'string', length: 3, nullable: false)]
    private string $countrycode;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'countryName', type: 'string', length: 45, nullable: false)]
    private string $countryname;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'currencyCode', type: 'string', length: 3, nullable: true)]
    private ?string $currencycode = null;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'population', type: 'integer', nullable: true)]
    private ?int $population = null;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'fipsCode', type: 'string', length: 2, nullable: true)]
    private ?string $fipscode = null;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'isoNumeric', type: 'string', length: 4, nullable: true)]
    private ?string $isonumeric = null;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'north', type: 'string', length: 30, nullable: true)]
    private ?string $north = null;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'south', type: 'string', length: 30, nullable: true)]
    private ?string $south = null;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'east', type: 'string', length: 30, nullable: true)]
    private ?string $east = null;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'west', type: 'string', length: 30, nullable: true)]
    private ?string $west = null;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'capital', type: 'string', length: 30, nullable: true)]
    private ?string $capital = null;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'continentName', type: 'string', length: 15, nullable: true)]
    private ?string $continentname = null;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'continent', type: 'string', length: 2, nullable: true)]
    private ?string $continent = null;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'areaInSqKm', type: 'string', length: 20, nullable: true)]
    private ?string $areainsqkm = null;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'isoAlpha3', type: 'string', length: 3, nullable: true)]
    private ?string $isoalpha3 = null;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[Column(name: 'geonameId', type: 'integer', nullable: true)]
    private ?int $geonameid = null;

    #[Groups(['city:read', 'logistic:read', 'state:read', 'people:read', 'order_details:read', 'order:write',  'address:read'])]
    #[OneToMany(targetEntity: LanguageCountry::class, mappedBy: 'country')]
    private Collection $languageCountry;

    #[OneToMany(targetEntity: State::class, mappedBy: 'country')]
    private Collection $state;

    public function __construct()
    {
        $this->languageCountry = new ArrayCollection();
        $this->state = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setCountrycode(string $countrycode): self
    {
        $this->countrycode = $countrycode;
        return $this;
    }

    public function getCountrycode(): string
    {
        return $this->countrycode;
    }

    public function setCountryname(string $countryname): self
    {
        $this->countryname = $countryname;
        return $this;
    }

    public function getCountryname(): string
    {
        return $this->countryname;
    }

    public function setCurrencycode(?string $currencycode): self
    {
        $this->currencycode = $currencycode;
        return $this;
    }

    public function getCurrencycode(): ?string
    {
        return $this->currencycode;
    }

    public function setPopulation(?int $population): self
    {
        $this->population = $population;
        return $this;
    }

    public function getPopulation(): ?int
    {
        return $this->population;
    }

    public function setFipscode(?string $fipscode): self
    {
        $this->fipscode = $fipscode;
        return $this;
    }

    public function getFipscode(): ?string
    {
        return $this->fipscode;
    }

    public function setIsonumeric(?string $isonumeric): self
    {
        $this->isonumeric = $isonumeric;
        return $this;
    }

    public function getIsonumeric(): ?string
    {
        return $this->isonumeric;
    }

    public function setNorth(?string $north): self
    {
        $this->north = $north;
        return $this;
    }

    public function getNorth(): ?string
    {
        return $this->north;
    }

    public function setSouth(?string $south): self
    {
        $this->south = $south;
        return $this;
    }

    public function getSouth(): ?string
    {
        return $this->south;
    }

    public function setEast(?string $east): self
    {
        $this->east = $east;
        return $this;
    }

    public function getEast(): ?string
    {
        return $this->east;
    }

    public function setWest(?string $west): self
    {
        $this->west = $west;
        return $this;
    }

    public function getWest(): ?string
    {
        return $this->west;
    }

    public function setCapital(?string $capital): self
    {
        $this->capital = $capital;
        return $this;
    }

    public function getCapital(): ?string
    {
        return $this->capital;
    }

    public function setContinentname(?string $continentname): self
    {
        $this->continentname = $continentname;
        return $this;
    }

    public function getContinentname(): ?string
    {
        return $this->continentname;
    }

    public function setContinent(?string $continent): self
    {
        $this->continent = $continent;
        return $this;
    }

    public function getContinent(): ?string
    {
        return $this->continent;
    }

    public function setAreainsqkm(?string $areainsqkm): self
    {
        $this->areainsqkm = $areainsqkm;
        return $this;
    }

    public function getAreainsqkm(): ?string
    {
        return $this->areainsqkm;
    }

    public function setIsoalpha3(?string $isoalpha3): self
    {
        $this->isoalpha3 = $isoalpha3;
        return $this;
    }

    public function getIsoalpha3(): ?string
    {
        return $this->isoalpha3;
    }

    public function setGeonameid(?int $geonameid): self
    {
        $this->geonameid = $geonameid;
        return $this;
    }

    public function getGeonameid(): ?int
    {
        return $this->geonameid;
    }

    public function addLanguageCountry(LanguageCountry $languageCountry): self
    {
        if (!$this->languageCountry->contains($languageCountry)) {
            $this->languageCountry[] = $languageCountry;
        }
        return $this;
    }

    public function removeLanguageCountry(LanguageCountry $languageCountry): self
    {
        $this->languageCountry->removeElement($languageCountry);
        return $this;
    }

    public function getLanguageCountry(): Collection
    {
        return $this->languageCountry;
    }

    public function addState(State $state): self
    {
        if (!$this->state->contains($state)) {
            $this->state[] = $state;
        }
        return $this;
    }

    public function removeState(State $state): self
    {
        $this->state->removeElement($state);
        return $this;
    }

    public function getState(): Collection
    {
        return $this->state;
    }
}