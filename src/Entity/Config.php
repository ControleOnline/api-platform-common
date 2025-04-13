<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ControleOnline\Controller\AddAppConfigAction;
use ControleOnline\Controller\DiscoveryMainConfigsAction;
use ControleOnline\Listener\LogListener;
use ControleOnline\Repository\ConfigRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')'),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/configs/add-configs',
            controller: AddAppConfigAction::class
        ),
        new Post(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/configs/discovery-configs',
            controller: DiscoveryMainConfigsAction::class
        ),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['config:write']]
        ),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['config:read']],
    denormalizationContext: ['groups' => ['config:write']]
)]
#[Table(name: 'config')]
#[UniqueConstraint(name: 'people_id', columns: ['people_id', 'configKey'])]
#[EntityListeners([LogListener::class])]
#[Entity(repositoryClass: ConfigRepository::class)]
class Config
{
    #[Groups(['config:read'])]
    #[Column(name: 'id', type: 'integer', nullable: false)]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[Groups(['config:read', 'config:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
    #[JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ManyToOne(targetEntity: People::class)]
    private ?People $people = null;

    #[Groups(['config:read', 'config:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['configKey' => 'exact'])]
    #[Column(name: 'config_key', type: 'string', length: 255, nullable: false)]
    private string $configKey;

    #[Groups(['config:read', 'config:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['visibility' => 'exact'])]
    #[Column(name: 'visibility', type: 'string', length: 255, nullable: false)]
    private string $visibility;

    #[Groups(['config:read', 'config:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['configValue' => 'exact'])]
    #[Column(name: 'config_value', type: 'string', length: 255, nullable: false)]
    private string $configValue;

    #[Groups(['config:read', 'config:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['module' => 'exact'])]
    #[JoinColumn(name: 'module_id', referencedColumnName: 'id')]
    #[ManyToOne(targetEntity: Module::class)]
    private ?Module $module = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setPeople(?People $people): self
    {
        $this->people = $people;
        return $this;
    }

    public function getPeople(): ?People
    {
        return $this->people;
    }

    public function setConfigKey(string $configKey): self
    {
        $this->configKey = $configKey;
        return $this;
    }

    public function getConfigKey(): string
    {
        return $this->configKey;
    }

    public function setVisibility(string $visibility): self
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setConfigValue(string $configValue): self
    {
        $this->configValue = $configValue;
        return $this;
    }

    public function getConfigValue(): string
    {
        return $this->configValue;
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): self
    {
        $this->module = $module;
        return $this;
    }
}