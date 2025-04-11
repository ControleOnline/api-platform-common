<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            security: 'is_granted(\'ROLE_ADMIN\') or (is_granted(\'ROLE_CLIENT\'))',
            validationContext: ['groups' => ['status:read']],
            denormalizationContext: ['groups' => ['status:write']]
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    security: 'is_granted(\'ROLE_CLIENT\')',
    normalizationContext: ['groups' => ['status:read']],
    denormalizationContext: ['groups' => ['status:write']]
)]
#[ORM\Table(name: 'status')]
#[ORM\Index(name: 'IDX_real_status', columns: ['real_status'])]
#[ORM\UniqueConstraint(name: 'status', columns: ['status', 'context'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: \ControleOnline\Repository\StatusRepository::class)]
class Status
{
    /**
     * @var integer
     *
     * @Groups({ "contract:read", "task:read","display_queue:read","display:read","order_product_queue:read","order:read","order_details:read","order:write", "invoice:read","invoice_details:read", "status:read","status:write", "order_detail_status:read", "logistic:read","queue:read", "queue_people_queue:read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact'])]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]

    private $id;
    /**
     * @var string
     *
     * @Groups({"contract:read", "task:read","display_queue:read","display:read","order_product_queue:read","order:read","order_details:read","order:write", "invoice:read","invoice_details:read", "status:read","status:write", "order_detail_status:read", "logistic:read","queue:read", "queue_people_queue:read"})
     * @Assert\NotBlank
     * @Assert\Type(type={"string"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['status' => 'exact'])]
    #[ORM\Column(name: 'status', type: 'string', nullable: false)]

    private $status;
    /**
     * @var string
     *
     * @Groups({"contract:read", "task:read","display_queue:read","display:read","order_product_queue:read","order:read","order_details:read","order:write", "invoice:read","invoice_details:read", "status:read","status:write", "order_detail_status:read", "logistic:read","queue:read", "queue_people_queue:read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['realStatus' => 'exact'])]
    #[ORM\Column(name: 'real_status', type: 'string', nullable: false)]

    private $realStatus;
    /**
     * @var string
     *
     * @Groups({"contract:read", "task:read","display_queue:read","display:read","order_product_queue:read","order:read","order_details:read","order:write", "invoice:read","invoice_details:read", "status:read","status:write", "order_detail_status:read", "logistic:read","queue:read", "queue_people_queue:read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['visibility' => 'exact'])]
    #[ORM\Column(name: 'visibility', type: 'string', nullable: false)]

    private $visibility = 1;
    /**
     * @var boolean
     *
     * @Groups({"contract:read", "task:read","display_queue:read","display:read","order_product_queue:read","order:read","order_details:read","order:write", "invoice:read","invoice_details:read", "status:read","status:write", "order_detail_status:read", "logistic:read","queue:read", "queue_people_queue:read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['notify' => 'exact'])]
    #[ORM\Column(name: 'notify', type: 'boolean', nullable: false)]

    private $notify = 1;
    /**
     * @var boolean
     *
     * @Groups({"contract:read", "task:read","display_queue:read","display:read","order_product_queue:read","order:read","order_details:read","order:write", "invoice:read","invoice_details:read", "status:read","status:write", "order_detail_status:read", "logistic:read","queue:read", "queue_people_queue:read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['system' => 'exact'])]
    #[ORM\Column(name: 'system', type: 'boolean', nullable: false)]

    private $system = 0;
    /**
     * @var string
     *
     * @Groups({"contract:read", "task:read","display_queue:read","display:read","order_product_queue:read","order:read","order_details:read","order:write", "invoice:read","invoice_details:read", "status:read","status:write", "order_detail_status:read", "logistic:read","queue:read", "queue_people_queue:read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['color' => 'exact'])]
    #[ORM\Column(name: 'color', type: 'string', nullable: false)]

    private $color;
    /**
     * @var string
     *
     * @Groups({"contract:read", "task:read","display_queue:read","display:read","order_product_queue:read","order:read","order_details:read","order:write", "invoice:read","invoice_details:read", "status:read","status:write", "order_detail_status:read", "logistic:read","queue:read", "queue_people_queue:read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['context' => 'exact'])]
    #[ORM\Column(name: 'context', type: 'string', nullable: false)]

    private $context;
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
     * Set status
     *
     * @param string $status
     * @return Status
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
    /**
     * Set realStatus
     *
     * @param string $real_status
     * @return Status
     */
    public function setRealStatus($real_status)
    {
        $this->realStatus = $real_status;
        return $this;
    }
    /**
     * Get realStatus
     *
     * @return string
     */
    public function getRealStatus()
    {
        return $this->realStatus;
    }
    /**
     * Set visibility
     *
     * @param string $visibility
     * @return Status
     */
    public function setVisibility(string $visibility): self
    {
        $this->visibility = $visibility;
        return $this;
    }
    /**
     * Get visibility
     *
     * @return string
     */
    public function getVisibility(): string
    {
        return $this->visibility;
    }
    /**
     * Set notify
     *
     * @param boolean $notify
     * @return Status
     */
    public function setNotify($notify)
    {
        $this->notify = $notify;
        return $this;
    }
    /**
     * Get notify
     *
     * @return boolean
     */
    public function getNotify()
    {
        return $this->notify;
    }
    /**
     * Set system
     *
     * @param boolean $system
     * @return Status
     */
    public function setSystem($system)
    {
        $this->system = $system;
        return $this;
    }
    /**
     * Get system
     *
     * @return boolean
     */
    public function getSystem()
    {
        return $this->system;
    }
    /**
     * Set color
     *
     * @param string $color
     * @return Status
     */
    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }
    /**
     * Get color
     *
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }
    /**
     * Set context
     *
     * @param string $context
     * @return Status
     */
    public function setContext(string $context): self
    {
        $this->context = $context;
        return $this;
    }
    /**
     * Get context
     *
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }
}
