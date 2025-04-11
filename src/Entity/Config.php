<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ControleOnline\Controller\AddAppConfigAction;
use ControleOnline\Controller\DiscoveryMainConfigsAction;

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
#[ORM\Table(name: 'config')]
#[ORM\UniqueConstraint(name: 'people_id', columns: ['people_id', 'configKey'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\ConfigRepository::class)]
class Config
{
    /**
     * @var integer
     *
     * @Groups({"config:read"})
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;
    /**
     * @var \ControleOnline\Entity\People
     *
     * @Groups({"config:read","config:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\People::class)]

    private $people;
    /**
     * @var string
     *
     * @Groups({"config:read","config:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['configKey' => 'exact'])]
    #[ORM\Column(name: 'config_key', type: 'string', length: 255, nullable: false)]

    private $configKey;
    /**
     * @var string
     *
     * @Groups({"config:read","config:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['visibility' => 'exact'])]
    #[ORM\Column(name: 'visibility', type: 'string', length: 255, nullable: false)]

    private $visibility;
    /**
     * @var string
     *
     * @Groups({"config:read","config:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['configValue' => 'exact'])]
    #[ORM\Column(name: 'config_value', type: 'string', length: 255, nullable: false)]

    private $configValue;
    /**
     * @var \ControleOnline\Entity\Module
     *
     * @Groups({"config:read","config:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['module' => 'exact'])]
    #[ORM\JoinColumn(name: 'module_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\Module::class)]
    private $module;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Set people
     *
     * @param \ControleOnline\Entity\People $people
     * @return PeopleConfigKey
     */
    public function setPeople(People $people = null)
    {
        $this->people = $people;
        return $this;
    }
    /**
     * Get people
     *
     * @return \ControleOnline\Entity\People
     */
    public function getPeople()
    {
        return $this->people;
    }
    /**
     * Set configKey
     *
     * @param string configKey
     * @return PeopleConfigKey
     */
    public function setConfigKey($configKey)
    {
        $this->configKey = $configKey;
        return $this;
    }
    /**
     * Get configKey
     *
     * @return string
     */
    public function getConfigKey()
    {
        return $this->configKey;
    }
    /**
     * Set visibility
     *
     * @param string visibility
     * @return Config
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
        return $this;
    }
    /**
     * Get visibility
     *
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }
    /**
     * Set configValue
     *
     * @param string configValue
     * @return PeopleConfigKey
     */
    public function setConfigValue($configValue)
    {
        $this->configValue = $configValue;
        return $this;
    }
    /**
     * Get configValue
     *
     * @return string
     */
    public function getConfigValue()
    {
        return $this->configValue;
    }

    /**
     * Get the value of module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set the value of module
     */
    public function setModule($module): self
    {
        $this->module = $module;

        return $this;
    }
}
