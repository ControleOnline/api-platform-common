<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;

/**
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Table (name="config", uniqueConstraints={@ORM\UniqueConstraint (name="people_id", columns={"people_id","config_key"})})
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\ConfigRepository")
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')'),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['config:write']]
        ),
        new GetCollection(
            security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
            uriTemplate: '/configs/app-config',
            controller: \App\Controller\GetAppConfigAction::class
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['config:read']],
    denormalizationContext: ['groups' => ['config:write']]
)]
class Config
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"config:read"}) 
     */
    private $id;
    /**
     * @var \ControleOnline\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id")
     * })
     * @Groups({"config:read","config:write"}) 
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]

    private $people;
    /**
     * @var string
     *
     * @ORM\Column(name="config_key", type="string", length=255, nullable=false)
     * @Groups({"config:read","config:write"}) 
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['config_key' => 'exact'])]

    private $config_key;
    /**
     * @var string
     *
     * @ORM\Column(name="visibility", type="string", length=255, nullable=false)
     * @Groups({"config:read","config:write"}) 

     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['visibility' => 'exact'])]

    private $visibility;
    /**
     * @var string
     *
     * @ORM\Column(name="config_value", type="string", length=255, nullable=false)
     * @Groups({"config:read","config:write"}) 
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['config_value' => 'exact'])]

    private $config_value;
    /**
     * @var \ControleOnline\Entity\Module
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\Module")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="module_id", referencedColumnName="id")
     * })
     * @Groups({"config:read","config:write"}) 
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['module' => 'exact'])]
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
     * Set config_key
     *
     * @param string config_key
     * @return PeopleConfigKey
     */
    public function setConfigKey($config_key)
    {
        $this->config_key = $config_key;
        return $this;
    }
    /**
     * Get config_key
     *
     * @return string
     */
    public function getConfigKey()
    {
        return $this->config_key;
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
     * Set config_value
     *
     * @param string config_value
     * @return PeopleConfigKey
     */
    public function setConfigValue($config_value)
    {
        $this->config_value = $config_value;
        return $this;
    }
    /**
     * Get config_value
     *
     * @return string
     */
    public function getConfigValue()
    {
        return $this->config_value;
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
