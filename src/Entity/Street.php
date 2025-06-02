<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ControleOnline\Entity\District;
use ControleOnline\Entity\Cep;
use ControleOnline\Entity\Address;
use ControleOnline\Repository\StreetRepository;
use ControleOnline\Listener\LogListener;

#[ORM\Table(name: 'street')]
#[ORM\Index(name: 'district_id', columns: ['district_id'])]
#[ORM\Index(name: 'cep', columns: ['cep_id'])]
#[ORM\Index(name: 'street', columns: ['street'])]
#[ORM\UniqueConstraint(name: 'street_2', columns: ['street', 'district_id'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: StreetRepository::class)]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['street:read']],
    denormalizationContext: ['groups' => ['street:write']],
    operations: [
        new GetCollection(security: "is_granted('ROLE_CLIENT')"),
        new Get(security: "is_granted('ROLE_CLIENT')")
    ]
)]
class Street
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['people:read', 'order_details:read', 'order:write',  'address:read'])]
    private $id;

    #[ORM\Column(name: 'street', type: 'string', length: 255, nullable: false)]
    #[Groups(['people:read', 'order_details:read', 'order:write',  'address:read'])]
    private $street;

    #[ORM\ManyToOne(targetEntity: District::class, inversedBy: 'street')]
    #[ORM\JoinColumn(name: 'district_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['people:read', 'order_details:read', 'order:write',  'address:read'])]
    private $district;

    #[ORM\ManyToOne(targetEntity: Cep::class, inversedBy: 'street')]
    #[ORM\JoinColumn(name: 'cep_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['people:read', 'order_details:read', 'order:write',  'address:read'])]
    private $cep;

    #[ORM\OneToMany(targetEntity: Address::class, mappedBy: 'street')]
    private $address;

    #[ORM\Column(name: 'confirmed', type: 'boolean', nullable: true)]
    private $confirmed;

    public function __construct()
    {
        $this->address = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setStreet($street)
    {
        $this->street = $street;
        return $this;
    }

    public function getStreet()
    {
        return strtoupper($this->street);
    }

    public function setDistrict(District $district = null)
    {
        $this->district = $district;
        return $this;
    }

    public function getDistrict()
    {
        return $this->district;
    }

    public function setCep(Cep $cep = null)
    {
        $this->cep = $cep;
        return $this;
    }

    public function getCep()
    {
        return $this->cep;
    }

    public function addAddress(Address $address)
    {
        $this->address[] = $address;
        return $this;
    }

    public function removeAddress(Address $address)
    {
        $this->address->removeElement($address);
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setConfirmed($confirmed)
    {
        $this->confirmed = $confirmed;
        return $this;
    }

    public function getConfirmed()
    {
        return $this->confirmed;
    }
}
