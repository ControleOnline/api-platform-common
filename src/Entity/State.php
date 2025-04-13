<?php
namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ControleOnline\Entity\City;
use ControleOnline\Entity\Country;
use ControleOnline\Repository\StateRepository;
use ControleOnline\Listener\LogListener;

#[ORM\Table(name: 'state')]
#[ORM\Index(name: 'country_id', columns: ['country_id'])]
#[ORM\UniqueConstraint(name: 'UF', columns: ['UF'])]
#[ORM\UniqueConstraint(name: 'cod_ibge', columns: ['cod_ibge'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: StateRepository::class)]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['state:read']],
    denormalizationContext: ['groups' => ['state:write']],
    operations: [
        new GetCollection(security: "is_granted('ROLE_CLIENT')"),
        new Get(security: "is_granted('ROLE_CLIENT')")
    ]
)]
#[ApiFilter(OrderFilter::class, properties: ['state' => 'ASC'])]
class State
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['city:read', 'logistic:read', 'state:read', 'order_details:read', 'people:read', 'address:read', 'delivery_region:read'])]
    private $id;

    #[ORM\Column(name: 'state', type: 'string', length: 50, nullable: false)]
    #[Groups(['city:read', 'logistic:read', 'state:read', 'order_details:read', 'people:read', 'address:read', 'delivery_region:read'])]
    private $state;

    #[ORM\Column(name: 'cod_ibge', type: 'integer', nullable: true)]
    #[Groups(['city:read', 'logistic:read', 'state:read', 'order_details:read', 'people:read', 'address:read', 'delivery_region:read'])]
    private $cod_ibge;

    #[ORM\Column(name: 'UF', type: 'string', length: 2, nullable: false)]
    #[Groups(['city:read', 'logistic:read', 'state:read', 'order_details:read', 'people:read', 'address:read', 'delivery_region:read'])]
    private $uf;

    #[ORM\ManyToOne(targetEntity: Country::class, inversedBy: 'state')]
    #[ORM\JoinColumn(name: 'country_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['city:read', 'logistic:read', 'state:read', 'order_details:read', 'people:read', 'address:read', 'delivery_region:read'])]
    private $country;

    #[ORM\OneToMany(targetEntity: City::class, mappedBy: 'state')]
    private $city;

    public function __construct()
    {
        $this->city = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    public function getState()
    {
        return strtoupper($this->state);
    }

    public function setUf($uf)
    {
        $this->uf = $uf;
        return $this;
    }

    public function getUf()
    {
        return strtoupper($this->uf);
    }

    public function setCountry(Country $country = null)
    {
        $this->country = $country;
        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function addCity(City $city)
    {
        $this->city[] = $city;
        return $this;
    }

    public function removeCity(City $city)
    {
        $this->city->removeElement($city);
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setIbge($cod_ibge)
    {
        $this->cod_ibge = $cod_ibge;
        return $this;
    }

    public function getIbge()
    {
        return $this->cod_ibge;
    }
}