<?php

namespace ControleOnline\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'menu_link_type')]
#[ORM\Index(name: 'menu_link_type_link_type_idx', columns: ['link_type'])]
#[ORM\UniqueConstraint(name: 'menu_link_type_unique', columns: ['menu_id', 'link_type'])]
#[ORM\Entity]
class MenuLinkType
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\JoinColumn(name: 'menu_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Menu::class, inversedBy: 'linkTypes')]
    private ?Menu $menu = null;

    #[ORM\Column(name: 'link_type', type: 'string', length: 30, nullable: false)]
    private string $linkType = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMenu(): ?Menu
    {
        return $this->menu;
    }

    public function setMenu(?Menu $menu): self
    {
        $this->menu = $menu;

        return $this;
    }

    public function getLinkType(): string
    {
        return $this->linkType;
    }

    public function setLinkType(string $linkType): self
    {
        $this->linkType = trim(strtolower($linkType));

        return $this;
    }
}
