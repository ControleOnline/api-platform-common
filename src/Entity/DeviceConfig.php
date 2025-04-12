<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ControleOnline\Controller\AddDeviceConfigAction;
use Doctrine\ORM\Mapping as ORM;
use stdClass;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['device_config:write']]
        ),
        new Post(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/device_configs/add-configs',
            controller: AddDeviceConfigAction::class
        ),        
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(
            security: 'is_granted(\'PUBLIC_ACCESS\')',
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['device_config:read']],
    denormalizationContext: ['groups' => ['device_config:write']]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['id' => 'ASC'])]
#[ORM\Table(name: 'device_configs')]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\DeviceConfigRepository::class)]

class DeviceConfig
{
    /**
     * @var integer
     *
     * @Groups({"device_config:read","device:read","device_config:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact'])]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]

    private $id;


    /**
     * @var \ControleOnline\Entity\People
     *
     * @Groups({"device_config:read","device:read","device_config:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\People::class)]

    private $people;
    /**
     * @var \ControleOnline\Entity\Device
     *
     * @Groups({"device_config:read","device:read","device_config:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['device' => 'exact'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['device.device' => 'exact'])]
    #[ORM\JoinColumn(name: 'device_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\Device::class)]
    private $device;


    /**
     * @var string
     *
     * @Groups({"device_config:read","device:read","device_config:write"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['configs' => 'exact'])]
    #[ORM\Column(name: 'configs', type: 'string', length: 100, nullable: false)]

    private $configs;
    public function __construct()
    {
        $this->configs = json_encode(new stdClass());
    }

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





    public function getConfigs($decode = false)
    {
        return $decode ? json_decode((is_array($this->configs) ? json_encode($this->configs) : $this->configs), true) : $this->configs;
    }


    public function addConfig($key, $value)
    {
        $configs = $this->getConfigs(true);
        $configs[$key] = $value;
        return $this->setConfigs($configs);
    }


    public function setConfigs($configs)
    {
        $this->configs = json_encode($configs);
        return $this;
    }

    /**
     * Get the value of device
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * Set the value of device
     */
    public function setDevice($device): self
    {
        $this->device = $device;

        return $this;
    }
}
