<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
/**
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Table (name="people_carrier", uniqueConstraints={@ORM\UniqueConstraint (name="carrier_id", columns={"carrier_id", "company_id"})}, indexes={@ORM\Index (name="company_id", columns={"company_id"}), @ORM\Index(name="IDX_2C6E59348C03F15C", columns={"carrier_id"})})
 * @ORM\Entity (repositoryClass="App\Repository\PeopleCarrierRepository")
 */
#[ApiResource(operations: [new Put(uriTemplate: '/people_carriers/{id}/change-status', controller: \ControleOnline\Controller\ChangeCarrierStatusAction::class, security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'edit\', object)', requirements: ['id' => '^\\d+$']), new Post(), new GetCollection()], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']])]
class PeopleCarrier
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var \ControleOnline\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People", inversedBy="peopleCarrier")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     * })
     */
    private $company;
    /**
     * @var \ControleOnline\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People", inversedBy="peopleCompany")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="carrier_id", referencedColumnName="id")
     * })
     * @ORM\OrderBy({"alias" = "ASC"})
     */
    private $carrier;
    /**
     *
     * @ORM\Column(type="boolean",  nullable=false)
     */
    private $enable = 0;
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
     * Set company
     *
     * @param \ControleOnline\Entity\People $company
     * @return PeopleCarrier
     */
    public function setCompany(\ControleOnline\Entity\People $company = null)
    {
        $this->company = $company;
        return $this;
    }
    /**
     * Get company
     *
     * @return \ControleOnline\Entity\People
     */
    public function getCompany()
    {
        return $this->company;
    }
    /**
     * Set carrier
     *
     * @param \ControleOnline\Entity\People $carrier
     * @return PeopleCarrier
     */
    public function setCarrier(\ControleOnline\Entity\People $carrier = null)
    {
        $this->carrier = $carrier;
        return $this;
    }
    /**
     * Get carrier
     *
     * @return \ControleOnline\Entity\People
     */
    public function getCarrier()
    {
        return $this->carrier;
    }
    public function getEnabled()
    {
        return $this->enable;
    }
    public function setEnabled($enable)
    {
        $this->enable = $enable ?: 0;
        return $this;
    }
}
