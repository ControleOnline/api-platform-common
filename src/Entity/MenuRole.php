<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups; 
use ControleOnline\Listener\LogListener;

use Doctrine\ORM\Mapping as ORM;
use ControleOnline\Entity\Role;
use ControleOnline\Entity\Menu;

/**
 * MenuRole
 */
#[ORM\Table(name: 'menu_role')]
#[ORM\Index(name: 'role_id', columns: ['role_id'])]
#[ORM\Index(name: 'IDX_9F267A24CCD7E912', columns: ['menu_id'])]
#[ORM\UniqueConstraint(name: 'menu_id', columns: ['menu_id', 'role_id'])]
#[ORM\Entity]

class MenuRole
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \Menu
     */
    #[ORM\JoinColumn(name: 'menu_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Menu::class)]
    private $menu;

    /**
     * @var \Role
     */
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Role::class)]
    private $role;



    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }



    /**
     * Get the value of menu
     */
    public function getMenu(): Menu
    {
        return $this->menu;
    }

    /**
     * Set the value of menu
     */
    public function setMenu(Menu $menu): self
    {
        $this->menu = $menu;

        return $this;
    }

    /**
     * Get the value of role
     */
    public function getRole(): Role
    {
        return $this->role;
    }

    /**
     * Set the value of role
     */
    public function setRole(Role $role): self
    {
        $this->role = $role;

        return $this;
    }
}
