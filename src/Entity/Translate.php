<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Language;
use ControleOnline\Repository\TranslateRepository;


#[ORM\Table(name: 'translate')]

#[ORM\Entity(repositoryClass: TranslateRepository::class)]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['translate:read']],
    denormalizationContext: ['groups' => ['translate:write']],
    operations: [
        new GetCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CLIENT')"),
        new Post(securityPostDenormalize: "is_granted('ROLE_CLIENT')"),
        new Put(
            security: "is_granted('ROLE_CLIENT')",
            denormalizationContext: ['groups' => ['translate:write']]
        ),
        new Delete(security: "is_granted('ROLE_CLIENT')")
    ]
)]
#[ApiFilter(OrderFilter::class, properties: ['key' => 'ASC'])]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'people' => 'exact',
    'store' => 'exact',
    'type' => 'exact',
    'key' => 'exact',
    'language.language' => 'exact'
])]
class Translate
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['translate:read', 'translate:write'])]
    private $id;

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[Groups(['translate:read', 'translate:write'])]
    private $people;

    #[ORM\Column(name: 'store', type: 'string', length: 100, nullable: false)]
    #[Groups(['translate:read', 'translate:write'])]
    #[Assert\NotBlank]
    private $store;

    #[ORM\Column(name: 'type', type: 'string', length: 100, nullable: false)]
    #[Groups(['translate:read', 'translate:write'])]
    #[Assert\NotBlank]
    private $type;

    #[ORM\Column(name: 'translate_key', type: 'string', length: 100, nullable: false)]
    #[Groups(['translate:read', 'translate:write'])]
    #[Assert\NotBlank]
    private $key;

    #[ORM\Column(name: 'translate', type: 'string', length: 100, nullable: false)]
    #[Groups(['translate:read', 'translate:write'])]
    #[Assert\NotBlank]
    private $translate;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(name: 'lang_id', referencedColumnName: 'id')]
    #[Groups(['translate:read', 'translate:write'])]
    private $language;

    public function getId()
    {
        return $this->id;
    }

    public function getPeople()
    {
        return $this->people;
    }

    public function setPeople($people): self
    {
        $this->people = $people;
        return $this;
    }

    public function getStore()
    {
        return $this->store;
    }

    public function setStore($store): self
    {
        $this->store = $store;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setKey($key): self
    {
        $this->key = $key;
        return $this;
    }

    public function getTranslate()
    {
        return $this->translate;
    }

    public function setTranslate($translate): self
    {
        $this->translate = $translate;
        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language): self
    {
        $this->language = $language;
        return $this;
    }
}
