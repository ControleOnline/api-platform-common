<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ControleOnline\Repository\LanguageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ControleOnline\Listener\LogListener;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['language:read']] // Note: Usually Put uses a :write group, check if :read is intended
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'), // Consider adding validationContext/denormalizationContext if needed
        new GetCollection(
            security: 'is_granted(\'PUBLIC_ACCESS\')',
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['language:read']],
    denormalizationContext: ['groups' => ['language:write']]
)]
#[Table(name: 'language')]
#[UniqueConstraint(name: 'language', columns: ['language'])]
#[Entity(repositoryClass: LanguageRepository::class)]
#[EntityListeners([LogListener::class])]
class Language
{
    #[Groups(['translate:read', 'language:read'])]
    #[Column(type: 'integer', nullable: false)]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private int $id = 0;

    #[Groups(['translate:read', 'language:read'])]
    #[Column(type: 'string', length: 10, nullable: false)]
    private string $language;

    #[Groups(['translate:read', 'language:read'])]
    #[Column(type: 'boolean', nullable: false)]
    private bool $locked;

    #[OneToMany(targetEntity: People::class, mappedBy: 'language')]
    private Collection $people;

    public function __construct()
    {
        $this->people = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function addPeople(People $people): self
    {
        if (!$this->people->contains($people)) {
            $this->people[] = $people;
            // If People entity has a setLanguage method to maintain the bidirectional relationship:
            // $people->setLanguage($this);
        }
        return $this;
    }

    public function removePeople(People $people): self
    {
        if ($this->people->removeElement($people)) {
            // If People entity has a setLanguage method and it's the owning side or needs nulling:
            // if ($people->getLanguage() === $this) {
            //     $people->setLanguage(null);
            // }
        }
        return $this;
    }

    public function getPeople(): Collection
    {
        return $this->people;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLocked(bool $locked): self
    {
        $this->locked = $locked;
        return $this;
    }

    public function getLocked(): bool
    {
        return $this->locked;
    }
}