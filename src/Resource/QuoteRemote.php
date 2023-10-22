<?php

namespace ControleOnline\Resource;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Validator\Constraints as Assert;
use ControleOnline\Validator\Constraints as MyAssert;
/**
 */
#[ApiResource(operations: [new Post(uriTemplate: '/quotes/remote', controller: ControleOnline\Controller\QuoteRemoteAction::class)], security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')')]
final class QuoteRemote extends ResourceEntity
{
    /**
     * @Assert\NotBlank
     */
    public $orderId;
    /**
     * @Assert\NotBlank
     * @Assert\Regex(
     *    pattern="/^[0-9]{8}$/",
     *    message="Postal code is not valid"
     * )
     */
    public $origin;
    /**
     * @Assert\NotBlank
     * @Assert\Regex(
     *    pattern="/^[0-9]{8}$/",
     *    message="Postal code is not valid"
     * )
     */
    public $destination;
}
