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
            denormalizationContext: ['groups' => ['translate:write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(
            security: 'is_granted(\'PUBLIC_ACCESS\')',
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['translate:read']],
    denormalizationContext: ['groups' => ['translate:write']]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['key' => 'ASC'])]
#[ORM\Table(name: 'translate')]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\TranslateRepository::class)]

class Translate
{
    /**
     * @var integer
     *
     * @Groups({"translate:read","translate:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact'])]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]

    private $id;


    /**
     * @var \ControleOnline\Entity\People
     *
     * @Groups({"translate:read","translate:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\People::class)]

    private $people;
    /**
     * @var string
     *
     * @Groups({"translate:read","translate:write"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['store' => 'exact'])]
    #[ORM\Column(name: 'store', type: 'string', length: 100, nullable: false)]

    private $store;


    /**
     * @var string
     *
     * @Groups({"translate:read","translate:write"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['type' => 'exact'])]
    #[ORM\Column(name: 'type', type: 'string', length: 100, nullable: false)]

    private $type;

    /**
     * @var string
     *
     * @Groups({"translate:read","translate:write"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['key' => 'exact'])]
    #[ORM\Column(name: 'translate_key', type: 'string', length: 100, nullable: false)]

    private $key;
    /**
     * @var string
     *
     * @Groups({"translate:read","translate:write"})
     * @Assert\NotBlank
     */
    #[ORM\Column(name: 'translate', type: 'string', length: 100, nullable: false)]
    private $translate;


    /**
     * @var \ControleOnline\Entity\Language
     *
     * @Groups({"translate:read","translate:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['language.language' => 'exact'])]
    #[ORM\JoinColumn(name: 'lang_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\Language::class)]

    private $language;


    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of people
     */
    public function getPeople()
    {
        return $this->people;
    }

    /**
     * Set the value of people
     */
    public function setPeople($people): self
    {
        $this->people = $people;

        return $this;
    }

    /**
     * Get the value of store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Set the value of store
     */
    public function setStore($store): self
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Get the value of type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the value of type
     */
    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the value of key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the value of key
     */
    public function setKey($key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get the value of translate
     */
    public function getTranslate()
    {
        return $this->translate;
    }

    /**
     * Set the value of translate
     */
    public function setTranslate($translate): self
    {
        $this->translate = $translate;

        return $this;
    }

    /**
     * Get the value of language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set the value of language
     */
    public function setLanguage($language): self
    {
        $this->language = $language;

        return $this;
    }
}
