<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Street
 *
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Table (name="street", uniqueConstraints={@ORM\UniqueConstraint (name="street_2", columns={"street", "district_id"})}, indexes={@ORM\Index (name="district_id", columns={"district_id"}),@ORM\Index(name="cep", columns={"cep_id"}), @ORM\Index(name="street", columns={"street"})})
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\StreetRepository")
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['street:read']],
    denormalizationContext: ['groups' => ['street:write']]
)]
class Street
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"people:read","order_details:read","order:write", "address:read"})
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="street", type="string", length=255, nullable=false)
     * @Groups({"people:read","order_details:read","order:write", "address:read"})
     */
    private $street;
    /**
     * @var District
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\District", inversedBy="street")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="district_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"people:read","order_details:read","order:write", "address:read"})
     */
    private $district;
    /**
     * @var Cep
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\Cep", inversedBy="street")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="cep_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"people:read","order_details:read","order:write", "address:read"})
     */
    private $cep;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\Address", mappedBy="street")
     */
    private $address;
    /**
     * @var boolean
     *
     * @ORM\Column(name="confirmed", type="boolean", nullable=true)
     */
    private $confirmed;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->address = new ArrayCollection();
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
     * Set street
     *
     * @param string $street
     * @return Street
     */
    public function setStreet($street)
    {
        $this->street = $street;
        return $this;
    }
    /**
     * Get street
     *
     * @return string
     */
    public function getStreet()
    {
        return strtoupper($this->street);
    }
    /**
     * Set district
     *
     * @param District $district
     * @return District
     */
    public function setDistrict(District $district = null)
    {
        $this->district = $district;
        return $this;
    }
    /**
     * Get district
     *
     * @return District
     */
    public function getDistrict()
    {
        return $this->district;
    }
    /**
     * Set cep
     *
     * @param Cep $cep
     * @return Cep
     */
    public function setCep(Cep $cep = null)
    {
        $this->cep = $cep;
        return $this;
    }
    /**
     * Get cep
     *
     * @return Cep
     */
    public function getCep()
    {
        return $this->cep;
    }
    /**
     * Add address
     *
     * @param Address $address
     * @return Street
     */
    public function addAddress(Address $address)
    {
        $this->address[] = $address;
        return $this;
    }
    /**
     * Remove address
     *
     * @param Address $address
     */
    public function removeAddress(Address $address)
    {
        $this->address->removeElement($address);
    }
    /**
     * Get address
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAddress()
    {
        return $this->address;
    }
    /**
     * Set confirmed
     *
     * @param boolean $confirmed
     * @return Street
     */
    public function setConfirmed($confirmed)
    {
        $this->confirmed = $confirmed;
        return $this;
    }
    /**
     * Get confirmed
     *
     * @return boolean
     */
    public function getConfirmed()
    {
        return $this->confirmed;
    }
}
