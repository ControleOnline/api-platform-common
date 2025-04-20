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
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ControleOnline\Repository\StatusRepository;
use ControleOnline\Listener\LogListener;

#[ORM\Table(name: 'status')]
#[ORM\Index(name: 'IDX_real_status', columns: ['real_status'])]
#[ORM\UniqueConstraint(name: 'status', columns: ['status', 'context'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: StatusRepository::class)]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['status:read']],
    denormalizationContext: ['groups' => ['status:write']],
    security: "is_granted('ROLE_CLIENT')",
    operations: [
        new GetCollection(security: "is_granted('ROLE_CLIENT')"),
        new Get(security: "is_granted('ROLE_CLIENT')"),
        new Post(security: "is_granted('ROLE_CLIENT')"),
        new Put(
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CLIENT')",
            validationContext: ['groups' => ['status:read']],
            denormalizationContext: ['groups' => ['status:write']]
        ),
        new Delete(security: "is_granted('ROLE_CLIENT')")
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'status' => 'exact',
    'realStatus' => 'exact',
    'visibility' => 'exact',
    'notify' => 'exact',
    'system' => 'exact',
    'color' => 'exact',
    'context' => 'exact'
])]
class Status
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups([
        'contract:read', 'task:read', 'display_queue:read', 'display:read', 'order_product_queue:read', 'order:read', 'order_details:read', 'order:write',
        'invoice:read', 'invoice_details:read', 'status:read', 'status:write', 'order_detail_status:read', 'logistic:read', 'queue:read', 'queue_people_queue:read'
    ])]
    private $id;

    #[ORM\Column(name: 'status', type: 'string', nullable: false)]
    #[Groups([
        'contract:read', 'task:read', 'display_queue:read', 'display:read', 'order_product_queue:read', 'order:read', 'order_details:read', 'order:write',
        'invoice:read', 'invoice_details:read', 'status:read', 'status:write', 'order_detail_status:read', 'logistic:read', 'queue:read', 'queue_people_queue:read'
    ])]
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    private $status;

    #[ORM\Column(name: 'real_status', type: 'string', nullable: false)]
    #[Groups([
        'contract:read', 'task:read', 'display_queue:read', 'display:read', 'order_product_queue:read', 'order:read', 'order_details:read', 'order:write',
        'invoice:read', 'invoice_details:read', 'status:read', 'status:write', 'order_detail_status:read', 'logistic:read', 'queue:read', 'queue_people_queue:read'
    ])]
    private $realStatus;

    #[ORM\Column(name: 'visibility', type: 'string', nullable: false)]
    #[Groups([
        'contract:read', 'task:read', 'display_queue:read', 'display:read', 'order_product_queue:read', 'order:read', 'order_details:read', 'order:write',
        'invoice:read', 'invoice_details:read', 'status:read', 'status:write', 'order_detail_status:read', 'logistic:read', 'queue:read', 'queue_people_queue:read'
    ])]
    private $visibility = 1;

    #[ORM\Column(name: 'notify', type: 'boolean', nullable: false)]
    #[Groups([
        'contract:read', 'task:read', 'display_queue:read', 'display:read', 'order_product_queue:read', 'order:read', 'order_details:read', 'order:write',
        'invoice:read', 'invoice_details:read', 'status:read', 'status:write', 'order_detail_status:read', 'logistic:read', 'queue:read', 'queue_people_queue:read'
    ])]
    private $notify = 1;

    #[ORM\Column(name: 'system', type: 'boolean', nullable: false)]
    #[Groups([
        'contract:read', 'task:read', 'display_queue:read', 'display:read', 'order_product_queue:read', 'order:read', 'order_details:read', 'order:write',
        'invoice:read', 'invoice_details:read', 'status:read', 'status:write', 'order_detail_status:read', 'logistic:read', 'queue:read', 'queue_people_queue:read'
    ])]
    private $system = 0;

    #[ORM\Column(name: 'color', type: 'string', nullable: false)]
    #[Groups([
        'contract:read', 'task:read', 'display_queue:read', 'display:read', 'order_product_queue:read', 'order:read', 'order_details:read', 'order:write',
        'invoice:read', 'invoice_details:read', 'status:read', 'status:write', 'order_detail_status:read', 'logistic:read', 'queue:read', 'queue_people_queue:read'
    ])]
    private $color = '';

    #[ORM\Column(name: 'context', type: 'string', nullable: false)]
    #[Groups([
        'contract:read', 'task:read', 'display_queue:read', 'display:read', 'order_product_queue:read', 'order:read', 'order_details:read', 'order:write',
        'invoice:read', 'invoice_details:read', 'status:read', 'status:write', 'order_detail_status:read', 'logistic:read', 'queue:read', 'queue_people_queue:read'
    ])]
    private $context;

    public function getId()
    {
        return $this->id;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setRealStatus($real_status)
    {
        $this->realStatus = $real_status;
        return $this;
    }

    public function getRealStatus()
    {
        return $this->realStatus;
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

    public function setNotify($notify)
    {
        $this->notify = $notify;
        return $this;
    }

    public function getNotify()
    {
        return $this->notify;
    }

    public function setSystem($system)
    {
        $this->system = $system;
        return $this;
    }

    public function getSystem()
    {
        return $this->system;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setContext(string $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getContext(): string
    {
        return $this->context;
    }
}