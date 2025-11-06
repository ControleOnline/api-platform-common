<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ControleOnline\Repository\DeviceConfigRepository;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ControleOnline\Controller\AddDeviceConfigAction;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use stdClass;
use Symfony\Component\Validator\Constraints\NotBlank;

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
#[Table(name: 'device_configs')]

#[Entity(repositoryClass: DeviceConfigRepository::class)]
class DeviceConfig
{
    #[Groups(['device_config:read', 'device:read', 'device_config:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact'])]
    #[Column(name: 'id', type: 'integer', nullable: false)]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private int $id = 0;

    #[Groups(['device_config:read', 'device:read', 'device_config:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
    #[JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: false)]
    #[ManyToOne(targetEntity: People::class)]
    private People $people;

    #[Groups(['device_config:read', 'device:read', 'device_config:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['device' => 'exact'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['device.device' => 'exact'])]
    #[JoinColumn(name: 'device_id', referencedColumnName: 'id', nullable: false)]
    #[ManyToOne(targetEntity: Device::class)]
    private Device $device;

    #[Groups(['device_config:read', 'device:read', 'device_config:write'])]
    #[NotBlank]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['configs' => 'exact'])]
    #[Column(name: 'configs', type: 'string', length: 100, nullable: false)]
    private string $configs;

    public function __construct()
    {
        $this->configs = json_encode(new stdClass());
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPeople(): People
    {
        return $this->people;
    }

    public function setPeople(People $people): self
    {
        $this->people = $people;
        return $this;
    }

    public function getConfigs(bool $decode = false): string|array
    {
        // Ensure we're decoding a string, even if it was temporarily an array internally
        $configString = is_array($this->configs) ? json_encode($this->configs) : $this->configs;
        return $decode ? json_decode((string) $configString, true) : (string) $configString;
    }

    public function addConfig(string $key, mixed $value): self
    {
        $configs = $this->getConfigs(true);
        $configs[$key] = $value;
        return $this->setConfigs($configs);
    }

    public function setConfigs(string|array|object $configs): self
    {
        if (is_string($configs))
            $configs = json_decode($configs, true);

        $this->configs = json_encode($configs);
        return $this;
    }

    public function getDevice(): Device
    {
        return $this->device;
    }

    public function setDevice(Device $device): self
    {
        $this->device = $device;
        return $this;
    }
}
