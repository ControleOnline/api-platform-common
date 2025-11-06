<?php

namespace ControleOnline\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Log
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string')]
    private string $action;

    #[ORM\Column(type: 'string')]
    private string $class;

    #[ORM\Column(type: 'text')]
    private string $object;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    private ?User $user = null;



    /**
     * Get the value of id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the value of id
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of action
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Set the value of action
     */
    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get the value of class
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Set the value of class
     */
    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get the value of object
     */
    public function getObject(): string
    {
        return $this->object;
    }

    /**
     * Set the value of object
     */
    public function setObject(string $object): self
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Get the value of user
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set the value of user
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
