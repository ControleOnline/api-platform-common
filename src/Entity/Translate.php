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
use ControleOnline\Controller\PersistTranslateController;

#[ORM\Table(name: 'translate')]

#[ORM\Entity(repositoryClass: TranslateRepository::class)]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['translate:read']],
    denormalizationContext: ['groups' => ['translate:write']],
    operations: [
        new GetCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Get(security: "is_granted('ROLE_HUMAN')"),
        new Post(
            controller: PersistTranslateController::class,
            deserialize: false,
            security: 'is_granted(\'ROLE_HUMAN\')'
        ),
        new Put(
            security: "is_granted('TRANSLATE_MANAGE', object)",
            denormalizationContext: ['groups' => ['translate:write']]
        ),
        new Delete(security: "is_granted('TRANSLATE_MANAGE', object)")
    ]
)]
#[ApiFilter(OrderFilter::class, properties: ['key' => 'ASC'])]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'people' => 'exact',
    'store' => 'exact',
    'type' => 'exact',
    'key' => 'exact',
    'revised' => 'exact',
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

    #[ORM\Column(name: 'translate', type: 'string', length: 255, nullable: false)]
    #[Groups(['translate:read', 'translate:write'])]
    #[Assert\NotBlank]
    private $translate;

    #[ORM\Column(name: 'revised', type: 'boolean', nullable: false, options: ['default' => false])]
    #[Groups(['translate:read', 'translate:write'])]
    private bool $revised = false;

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
        $this->markRevisedOnUpdate();
        return $this;
    }

    public function getStore()
    {
        return $this->store;
    }

    public function setStore($store): self
    {
        $this->store = $store;
        $this->markRevisedOnUpdate();
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type): self
    {
        $this->type = $type;
        $this->markRevisedOnUpdate();
        return $this;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setKey($key): self
    {
        $this->key = $key;
        $this->markRevisedOnUpdate();
        return $this;
    }

    public function getTranslate()
    {
        return $this->translate;
    }

    public function setTranslate($translate): self
    {
        $this->translate = $translate;
        $this->markRevisedOnUpdate();
        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language): self
    {
        $this->language = $language;
        $this->markRevisedOnUpdate();
        return $this;
    }

    public function isRevised(): bool
    {
        return $this->revised;
    }

    public function getRevised(): bool
    {
        return $this->isRevised();
    }

    public function setRevised($revised): self
    {
        if (is_string($revised)) {
            $revised = in_array(strtolower($revised), ['1', 'true', 'yes', 'sim'], true);
        }

        $this->revised = (bool) $revised;
        return $this;
    }

    private function markRevisedOnUpdate(): void
    {
        if ($this->id !== null) {
            $this->revised = true;
        }
    }
}
