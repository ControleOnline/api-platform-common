<?php

namespace ControleOnline\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PeopleStates
 *
 * @ORM\Table(name="people_states", uniqueConstraints={@ORM\UniqueConstraint(name="state", columns={"state_id"})}, indexes={@ORM\Index(name="people_id", columns={"people_id"})})
 * @ORM\Entity
 *  @ORM\EntityListeners({App\Listener\LogListener::class})
 */
class PeopleStates
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
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id")
     * })
     */
    private $people;

    /**
     * @var \ControleOnline\Entity\State
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\State")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="state_id", referencedColumnName="id")
     * })
     */
    private $state;

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
     * Set state
     *
     * @param \ControleOnline\Entity\State $state
     * @return PeopleStates
     */
    public function setState(\ControleOnline\Entity\State $state = null)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return \ControleOnline\Entity\State
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set people
     *
     * @param \ControleOnline\Entity\People $people
     * @return PeopleStates
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
    public function getPeople()
    {
        return $this->people;
    }
}
