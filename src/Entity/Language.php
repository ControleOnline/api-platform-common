<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ControleOnline\Filter\CustomOrFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['language:read']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(
            security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['language:read']],
    denormalizationContext: ['groups' => ['language:write']]
)]
#[ORM\Table(name: 'language')]
#[ORM\UniqueConstraint(name: 'language', columns: ['language'])]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\LanguageRepository::class)]
#[ORM\EntityListeners([LogListener::class])]

class Language
{
    /**
     *
     * @Groups({"translate:read", "language:read"})
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @Groups({"translate:read", "language:read"})
     */
    #[ORM\Column(type: 'string', length: 10, nullable: false)]
    private $language;

    /**
     * @Groups({"translate:read", "language:read"})
     */
    #[ORM\Column(type: 'boolean', nullable: false)]
    private $locked;

    #[ORM\OneToMany(targetEntity: \ControleOnline\Entity\People::class, mappedBy: 'language')]
    private $people;

    public function __construct()
    {
        $this->people = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function addPeople(People $people)
    {
        $this->people[] = $people;

        return $this;
    }

    public function removePeople(People $people)
    {
        $this->people->removeElement($people);
    }

    public function getPeople()
    {
        return $this->people;
    }

    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    public function getLocked()
    {
        return $this->locked;
    }
}
