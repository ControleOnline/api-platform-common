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

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user = null;

    public function getId(): ?int { return $this->id; }
    public function setAction(string $a): self { $this->action = $a; return $this; }
    public function setClass(string $c): self { $this->class = $c; return $this; }
    public function setObject(string $o): self { $this->object = $o; return $this; }
    public function setUser(?User $u): self { $this->user = $u; return $this; }
}
