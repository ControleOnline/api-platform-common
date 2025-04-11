<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * City
 */
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
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\CityRepository::class)]
class City
{
    /**
     * @var integer
     *
     * @Groups({"city:read","logistic:read","order_details:read","order:write", "people:read", "address:read", "delivery_region:read"})
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;
    /**
     * @var string
     *
     * @Groups({"city:read","logistic:read","order_details:read","order:write", "people:read", "address:read", "delivery_region:read"})
     */
    #[ORM\Column(name: 'city', type: 'string', length: 80, nullable: false)]
    private $city;
    /**
     * @var string
     *
     * @Groups({"city:read","logistic:read","order_details:read","order:write", "people:read", "address:read", "delivery_region:read"})
     */
    #[ORM\Column(name: 'cod_ibge', type: 'integer', nullable: true)]
    private $cod_ibge;
    /**
     * @var boolean
     */
    #[ORM\Column(name: 'seo', type: 'boolean', nullable: false)]
    private $seo;
    /**
     * @var \ControleOnline\Entity\State
     *
     * @Groups({"city:read","logistic:read","order_details:read","order:write", "people:read", "address:read", "delivery_region:read"})
     */
    #[ORM\JoinColumn(name: 'state_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\State::class, inversedBy: 'city')]
    private $state;
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: \ControleOnline\Entity\District::class, mappedBy: 'city')]
    private $district;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->district = new \Doctrine\Common\Collections\ArrayCollection();
        $this->seo = false;
    }
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Set city
     *
     * @param string $city
     * @return City
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }
    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return strtoupper($this->city);
    }
    /**
     * Set state
     *
     * @param State $state
     * @return City
     */
    public function setState(State $state = null)
    {
        $this->state = $state;
        return $this;
    }
    /**
     * Get state
     *
     * @return State
     */
    public function getState()
    {
        return $this->state;
    }
    /**
     * Add district
     *
     * @param District $district
     * @return City
     */
    public function addDistrict(District $district)
    {
        $this->district[] = $district;
        return $this;
    }
    /**
     * Remove district
     *
     * @param District $district
     */
    public function removeDistrict(District $district)
    {
        $this->district->removeElement($district);
    }
    /**
     * Get district
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDistrict()
    {
        return $this->district;
    }
    /**
     * Set seo
     *
     * @param boolean $seo
     * @return City
     */
    public function setSeo($seo)
    {
        $this->seo = $seo;
        return $this;
    }
    /**
     * Get seo
     *
     * @return boolean
     */
    public function getSeo()
    {
        return $this->seo;
    }
    /**
     * Set cod_ibge
     *
     * @param integer $cod_ibge
     * @return City
     */
    public function setIbge($cod_ibge)
    {
        $this->cod_ibge = $cod_ibge;
        return $this;
    }
    /**
     * Get cod_ibge
     *
     * @return integer
     */
    public function getIbge()
    {
        return $this->cod_ibge;
    }
}
