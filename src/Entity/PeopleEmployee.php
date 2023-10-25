<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
/**
 * PeopleEmployee
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="people_employee", uniqueConstraints={@ORM\UniqueConstraint (name="employee_id", columns={"employee_id", "company_id"})}, indexes={@ORM\Index (name="company_id", columns={"company_id"})})
 * @ORM\Entity
 */
#[ApiResource(operations: [new Get()], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'ROLE_CLIENT\')')]
#[ApiResource(uriTemplate: '/people/{id}/people_companies.{_format}', uriVariables: ['id' => new Link(fromClass: \ControleOnline\Entity\People::class, identifiers: ['id'], toProperty: 'employee')], status: 200, operations: [new GetCollection()])]
class PeopleEmployee
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
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People", inversedBy="peopleCompany")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_id", referencedColumnName="id", nullable=false)
     * })
     * @ORM\OrderBy({"alias" = "ASC"}) 
     * @Groups({"people:people_company:subresource"})
     */
    private $company;
    /**
     * @var \ControleOnline\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People", inversedBy="peopleEmployee")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="employee_id", referencedColumnName="id", nullable=false)
     * })
     * @ORM\OrderBy({"alias" = "ASC"})
     * @Groups({"client_read"})
     */
    private $employee;
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
     * @return PeopleEmployee
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
     * Set employee
     *
     * @param \ControleOnline\Entity\People $employee
     * @return PeopleEmployee
     */
    public function setEmployee(\ControleOnline\Entity\People $employee = null)
    {
        $this->employee = $employee;
        return $this;
    }
    /**
     * Get employee
     *
     * @return \ControleOnline\Entity\People
     */
    public function getEmployee()
    {
        return $this->employee;
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
