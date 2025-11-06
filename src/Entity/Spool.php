<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ControleOnline\Listener\LogListener;
use ControleOnline\Repository\SpoolRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(
            security: 'is_granted(\'ROLE_CLIENT\')',
            normalizationContext: ['groups' => ['spool_item:read']],
        ),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')'),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['spool:read']],
    denormalizationContext: ['groups' => ['spool:write']]
)]
#[ORM\Table(name: 'spool')]
#[ORM\Index(name: 'device_id_idx', columns: ['device_id'])]
#[ORM\Index(name: 'user_id_idx', columns: ['user_id'])]
#[ORM\Index(name: 'people_id_idx', columns: ['people_id'])]

#[ORM\Entity(repositoryClass: SpoolRepository::class)]

class Spool
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['spool_item:read', 'spool:read',])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact'])]

    private $id;


    #[ORM\ManyToOne(targetEntity: Device::class)]
    #[ORM\JoinColumn(name: 'device_id', referencedColumnName: 'id')]
    #[Groups(['spool_item:read', 'spool:read', 'spool:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['device' => 'exact'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['device.device' => 'exact'])]
    private $device;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[Groups(['spool_item:read', 'spool:read', 'spool:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['user' => 'exact'])]
    private $user;


    #[ORM\Column(name: 'register_date', type: 'datetime', nullable: false)]
    #[Groups(['spool_item:read', 'spool:read', 'spool:write'])]
    private $registerDate;

    #[ORM\JoinColumn(name: 'status_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Status::class)]
    #[Groups(['spool_item:read', 'spool:read', 'spool:write'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['status' => 'exact'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['status.status' => 'exact'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['status.realStatus' => 'exact'])]
    private $status;

    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: File::class)]
    #[Groups(['spool_item:read', 'spool:read', 'spool:write'])]
    private $file;

    public function __construct()
    {
        $this->registerDate = new DateTime('now');
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setRegisterDate($registerDate): self
    {
        $this->registerDate = $registerDate;
        return $this;
    }

    public function getRegisterDate()
    {
        return $this->registerDate;
    }

    public function setStatus(Status $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setFile(File $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * Get the value of device
     */
    public function getDevice(): Device
    {
        return $this->device;
    }

    /**
     * Set the value of device
     */
    public function setDevice(Device $device): self
    {
        $this->device = $device;

        return $this;
    }
}
