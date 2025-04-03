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
use ControleOnline\Controller\AddDeviceConfigAction;
use ControleOnline\Filter\CustomOrFilter;
use Doctrine\ORM\Mapping as ORM;
use stdClass;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Table (name="device")
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\DeviceRepository")
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_CLIENT\')',
            denormalizationContext: ['groups' => ['device:write']]
        ),
        new Post(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/devices/add-configs',
            controller: AddDeviceConfigAction::class
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(
            security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['device:read']],
    denormalizationContext: ['groups' => ['device:write']]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['id' => 'ASC'])]

class Device
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"device:read","device:write"})
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
     * @Groups({"device:read","device:write"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]

    private $people;
    /**
     * @var string
     *
     * @ORM\Column(name="device", type="string", length=100, nullable=false)
     * @Groups({"device:read","device:write"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['device' => 'exact'])]

    private $device;


    /**
     * @var string
     *
     * @ORM\Column(name="configs", type="string", length=100, nullable=false)
     * @Groups({"device:read","device:write"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['configs' => 'exact'])]

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


    public function getConfigs($decode = false)
    {
        return $decode ? json_decode((is_array($this->configs) ? json_encode($this->configs) : $this->configs), true) : $this->configs;
    }


    public function addConfigs($key, $value)
    {
        $configs = $this->getConfigs(true);
        $configs[$key] = $value;
        $this->configs = json_encode($configs);
        return $this;
    }


    public function setConfigs($configs)
    {
        $this->configs = json_encode($configs);
        return $this;
    }
}
