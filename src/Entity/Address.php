<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Address
 */
#[
    ApiResource(
        operations: [
            new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
            new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
            new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),

        ],
        formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
        normalizationContext: ['groups' => ['address:read']],
        denormalizationContext: ['groups' => ['address:write']]
    )
]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
#[ORM\Table(name: 'address')]
#[ORM\Index(name: 'user_id_2', columns: ['people_id', 'nickname'])]
#[ORM\Index(name: 'user_id', columns: ['people_id'])]
#[ORM\Index(name: 'cep_id', columns: ['street_id'])]
#[ORM\UniqueConstraint(name: 'user_id_3', columns: ['people_id', 'number', 'street_id', 'complement'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\AddressRepository::class)]
class Address
{
    /**
     * @var integer
     *
     * @Groups({"people:read","order_details:read","order:write", "address:read"})
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;
    /**
     * @var integer
     *
     * @Groups({"people:read","order_details:read","order:write", "address:read"})
     */
    #[ORM\Column(name: 'number', type: 'integer', nullable: true)]
    private $number;
    /**
     * @var string
     *
     * @Groups({"people:read","order_details:read","order:write", "address:read"})
     */
    #[ORM\Column(name: 'nickname', type: 'string', length: 50, nullable: false)]
    private $nickname;
    /**
     * @var string
     *
     * @Groups({"people:read","order_details:read","order:write", "address:read"})
     */
    #[ORM\Column(name: 'complement', type: 'string', length: 50, nullable: false)]
    private $complement;
    /**
     * @var \ControleOnline\Entity\People
     */
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\People::class, inversedBy: 'address')]
    private $people;
    /**
     * @var \ControleOnline\Entity\Street
     *
     * @Groups({"people:read","order_details:read","order:write", "address:read"})
     */
    #[ORM\JoinColumn(name: 'street_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\Street::class, inversedBy: 'address')]
    private $street;
    /**
     * @var float
     *
     * @Groups({"people:read"})
     */
    #[ORM\Column(name: 'latitude', type: 'float', nullable: false)]
    private $latitude;
    /**
     * @var float
     *
     * @Groups({"people:read"})
     */
    #[ORM\Column(name: 'longitude', type: 'float', nullable: false)]
    private $longitude;
    /**
     * @var string
     *
     * @Groups({"people:read"})
     */
    #[ORM\Column(name: 'locator', type: 'string', nullable: false)]
    private $locator;
    /**
     * @var Datetime
     *
     * @Groups({"people:read"})
     */
    #[ORM\Column(name: 'opening_time', type: 'time', nullable: false)]
    private $opening_time;
    /**
     * @var Datetime
     *
     * @Groups({"people:read"})
     */
    #[ORM\Column(name: 'closing_time', type: 'time', nullable: false)]
    private $closing_time;
    /**
     * @var string
     *
     * @Groups({"people:read"})
     */
    #[ORM\Column(name: 'search_for', type: 'string', nullable: false)]
    private $search_for;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->latitude = 0;
        $this->longitude = 0;
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
     * Set number
     *
     * @param integer $number
     * @return Address
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }
    /**
     * Get number
     *
     * @return integer
     */
    public function getNumber()
    {
        return $this->number;
    }
    /**
     * Set nickname
     *
     * @param string $nickname
     * @return Address
     */
    public function setNickname($nickname)
    {
        $this->nickname = $nickname;
        return $this;
    }
    /**
     * Get nickname
     *
     * @return string
     */
    public function getNickname()
    {
        return strtoupper($this->nickname);
    }
    /**
     * Set complement
     *
     * @param string $complement
     * @return Address
     */
    public function setComplement($complement)
    {
        $this->complement = $complement;
        return $this;
    }
    /**
     * Get complement
     *
     * @return string
     */
    public function getComplement()
    {
        return strtoupper($this->complement);
    }
    /**
     * Set people
     *
     * @param \ControleOnline\Entity\People $people
     * @return Address
     */
    public function setPeople(\ControleOnline\Entity\People $people = null)
    {
        $this->people = $people;
        return $this;
    }
    /**
     * Get people
     *
     * @return \ControleOnline\Entity\People
     */
    public function getPeople(): People
    {
        return $this->people;
    }
    /**
     * Set street
     *
     * @param \ControleOnline\Entity\Street $street
     * @return Address
     */
    public function setStreet(\ControleOnline\Entity\Street $street = null)
    {
        $this->street = $street;
        return $this;
    }
    /**
     * Get street
     *
     * @return \ControleOnline\Entity\Street
     */
    public function getStreet()
    {
        return $this->street;
    }
    /**
     * Set latitude
     *
     * @param string $latitude
     * @return Address
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude ?: 0;
        return $this;
    }
    /**
     * Get latitude
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }
    /**
     * Set longitude
     *
     * @param string $longitude
     * @return Address
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude ?: 0;
        return $this;
    }
    /**
     * Get longitude
     *
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Get the value of locator     
     * @return string
     */
    public function getLocator()
    {
        return $this->locator;
    }

    /**
     * Set the value of locator
     */
    public function setLocator($locator)
    {
        $this->locator = $locator;

        return $this;
    }

    /**
     * Get the value of opening_time
     */
    public function getOpeningTime()
    {
        return $this->opening_time;
    }

    /**
     * Set the value of opening_time
     */
    public function setOpeningTime($opening_time): self
    {
        $this->opening_time = $opening_time;

        return $this;
    }

    /**
     * Get the value of closing_time
     */
    public function getClosingTime()
    {
        return $this->closing_time;
    }

    /**
     * Set the value of closing_time
     */
    public function setClosingTime($closing_time): self
    {
        $this->closing_time = $closing_time;

        return $this;
    }

    /**
     * Get the value of search_for
     */
    public function getSearchFor()
    {
        return $this->search_for;
    }

    /**
     * Set the value of search_for
     */
    public function setSearchFor(string $search_for = null): self
    {
        $this->search_for = $search_for;

        return $this;
    }
}
