<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ControleOnline\Listener\LogListener;
use ControleOnline\Repository\CityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['city:read']],
    denormalizationContext: ['groups' => ['city:write']]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['city' => 'ASC'])]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'city' => 'partial',
        'state.uf' => 'exact'
    ]
)]
#[ORM\Table(name: 'city')]
#[ORM\Index(name: 'state_id', columns: ['state_id'])]
#[ORM\Index(name: 'seo', columns: ['seo'])]
#[ORM\UniqueConstraint(name: 'city', columns: ['city', 'state_id'])]
#[ORM\UniqueConstraint(name: 'cod_ibge', columns: ['cod_ibge'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: CityRepository::class)]
class City
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['city:read', 'logistic:read', 'order_details:read', 'order:write',  'people:read', 'address:read', 'delivery_region:read'])]
    private $id;

    #[ORM\Column(name: 'city', type: 'string', length: 80, nullable: false)]
    #[Groups(['city:read', 'logistic:read', 'order_details:read', 'order:write',  'people:read', 'address:read', 'delivery_region:read'])]
    private $city;

    #[ORM\Column(name: 'cod_ibge', type: 'integer', nullable: true)]
    #[Groups(['city:read', 'logistic:read', 'order_details:read', 'order:write',  'people:read', 'address:read', 'delivery_region:read'])]
    private $cod_ibge;

    #[ORM\Column(name: 'seo', type: 'boolean', nullable: false)]
    private $seo;

    #[ORM\JoinColumn(name: 'state_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: State::class, inversedBy: 'city')]
    #[Groups(['city:read', 'logistic:read', 'order_details:read', 'order:write',  'people:read', 'address:read', 'delivery_region:read'])]
    private $state;

    #[ORM\OneToMany(targetEntity: District::class, mappedBy: 'city')]
    private $district;

    public function __construct()
    {
        $this->district = new ArrayCollection();
        $this->seo = false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    public function getCity()
    {
        return strtoupper($this->city);
    }

    public function setState(State $state = null)
    {
        $this->state = $state;
        return $this;
    }

    public function getState()
    {
        return $this->state;
    }

    public function addDistrict(District $district)
    {
        $this->district[] = $district;
        return $this;
    }

    public function removeDistrict(District $district)
    {
        $this->district->removeElement($district);
    }

    public function getDistrict()
    {
        return $this->district;
    }

    public function setSeo($seo)
    {
        $this->seo = $seo;
        return $this;
    }

    public function getSeo()
    {
        return $this->seo;
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