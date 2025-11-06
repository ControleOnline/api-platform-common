<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

use ControleOnline\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['address:read']],
    denormalizationContext: ['groups' => ['address:write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
#[ORM\Table(name: 'address')]
#[ORM\Index(name: 'user_id_2', columns: ['people_id', 'nickname'])]
#[ORM\Index(name: 'user_id', columns: ['people_id'])]
#[ORM\Index(name: 'cep_id', columns: ['street_id'])]
#[ORM\UniqueConstraint(name: 'user_id_3', columns: ['people_id', 'number', 'street_id', 'complement'])]

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['people:read', 'order_details:read', 'order:write',  'address:read'])]
    private $id;

    #[ORM\Column(name: 'number', type: 'integer', nullable: true)]
    #[Groups(['people:read', 'order_details:read', 'order:write',  'address:read'])]
    private $number;

    #[ORM\Column(name: 'nickname', type: 'string', length: 50, nullable: false)]
    #[Groups(['people:read', 'order_details:read', 'order:write',  'address:read'])]
    private $nickname;

    #[ORM\Column(name: 'complement', type: 'string', length: 50, nullable: false)]
    #[Groups(['people:read', 'order_details:read', 'order:write',  'address:read'])]
    private $complement;

    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: People::class, inversedBy: 'address')]
    private $people;

    #[ORM\JoinColumn(name: 'street_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Street::class, inversedBy: 'address')]
    #[Groups(['people:read', 'order_details:read', 'order:write',  'address:read'])]
    private $street;

    #[ORM\Column(name: 'latitude', type: 'float', nullable: false)]
    #[Groups(['people:read'])]
    private $latitude;

    #[ORM\Column(name: 'longitude', type: 'float', nullable: false)]
    #[Groups(['people:read'])]
    private $longitude;

    #[ORM\Column(name: 'locator', type: 'string', nullable: false)]
    #[Groups(['people:read'])]
    private $locator;

    #[ORM\Column(name: 'opening_time', type: 'time', nullable: false)]
    #[Groups(['people:read'])]
    private $opening_time;

    #[ORM\Column(name: 'closing_time', type: 'time', nullable: false)]
    #[Groups(['people:read'])]
    private $closing_time;

    #[ORM\Column(name: 'search_for', type: 'string', nullable: false)]
    #[Groups(['people:read'])]
    private $search_for;

    public function __construct()
    {
        $this->latitude = 0;
        $this->longitude = 0;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function setNickname($nickname)
    {
        $this->nickname = $nickname;
        return $this;
    }

    public function getNickname()
    {
        return strtoupper($this->nickname);
    }

    public function setComplement($complement)
    {
        $this->complement = $complement;
        return $this;
    }

    public function getComplement()
    {
        return strtoupper($this->complement);
    }

    public function setPeople(?People $people = null)
    {
        $this->people = $people;
        return $this;
    }

    public function getPeople(): People
    {
        return $this->people;
    }

    public function setStreet($street)
    {
        $this->street = $street;
        return $this;
    }

    public function getStreet()
    {
        return $this->street;
    }

    public function setLatitude($latitude)
    {
        $this->latitude = $latitude ?: 0;
        return $this;
    }

    public function getLatitude()
    {
        return $this->latitude;
    }

    public function setLongitude($longitude)
    {
        $this->longitude = $longitude ?: 0;
        return $this;
    }

    public function getLongitude()
    {
        return $this->longitude;
    }

    public function getLocator()
    {
        return $this->locator;
    }

    public function setLocator($locator)
    {
        $this->locator = $locator;
        return $this;
    }

    public function getOpeningTime()
    {
        return $this->opening_time;
    }

    public function setOpeningTime($opening_time): self
    {
        $this->opening_time = $opening_time;
        return $this;
    }

    public function getClosingTime()
    {
        return $this->closing_time;
    }

    public function setClosingTime($closing_time): self
    {
        $this->closing_time = $closing_time;
        return $this;
    }

    public function getSearchFor()
    {
        return $this->search_for;
    }

    public function setSearchFor(string $search_for = null): self
    {
        $this->search_for = $search_for;
        return $this;
    }
}
