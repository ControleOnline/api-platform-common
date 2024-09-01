<?php

namespace ControleOnline\Entity;

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

/**
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Table (name="category")
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\CategoryRepository")
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['translate_write']]
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['translate_read']],
    denormalizationContext: ['groups' => ['translate_write']]
)]
#[ApiFilter(filterClass: ExistsFilter::class, properties: ['parent'])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['name' => 'ASC'])]
#[ApiFilter(CustomOrFilter::class, properties: ['name', 'id', 'icon', 'color'])]

class Translate
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"translate_read","translate_write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact'])]

    private $id;


    /**
     * @var \ControleOnline\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id")
     * })
     * @Groups({"translate_read","translate_write"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]

    private $people;
    /**
     * @var string
     *
     * @ORM\Column(name="store", type="string", length=100, nullable=false)
     * @Groups({"translate_read","translate_write"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['store' => 'partial'])]

    private $store;


    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=100, nullable=false)
     * @Groups({"translate_read","translate_write"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['type' => 'partial'])]

    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="translate_key", type="string", length=100, nullable=false)
     * @Groups({"translate_read","translate_write"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['translate_key' => 'partial'])]

    private $key;
    /**
     * @var string
     *
     * @ORM\Column(name="translate", type="string", length=100, nullable=false)
     * @Groups({"translate_read","translate_write"})
     * @Assert\NotBlank
     */

    private $translate;


    /**
     * @var \ControleOnline\Entity\Language
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\Language")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="lang_id", referencedColumnName="id", nullable=true)
     * })
     * @Assert\NotBlank
     * @Groups({"translate_read","translate_write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['language' => 'exact'])]

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
