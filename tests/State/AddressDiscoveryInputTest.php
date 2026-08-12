<?php

namespace ControleOnline\Common\Tests\State;

use ApiPlatform\Metadata\ApiResource;
use ControleOnline\Entity\Address;
use ControleOnline\State\AddressDiscoveryInput;
use ControleOnline\State\AddressUpdateInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class AddressDiscoveryInputTest extends TestCase
{
    public function testTextualStreetIsAcceptedByThePostInput(): void
    {
        $normalizer = new ObjectNormalizer(
            new ClassMetadataFactory(new AttributeLoader())
        );

        $input = $normalizer->denormalize([
            'street' => 'Av. Porto Alegre',
            'city' => 'Primavera do Leste',
            'district' => 'Cidade Primavera 2',
            'state' => 'MT',
            'country' => 'Brasil',
            'people' => '/people/31467',
            'number' => '2125',
            'complement' => '',
            'nickname' => 'Primavera do Leste',
            'cep' => '78850000',
        ], AddressDiscoveryInput::class, null, ['groups' => ['address:write']]);

        self::assertInstanceOf(AddressDiscoveryInput::class, $input);
        self::assertSame('Av. Porto Alegre', $input->street);
        self::assertSame('/people/31467', $input->people);
        self::assertSame('2125', $input->number);
    }

    public function testAddressPostUsesTheDiscoveryInputWithoutChangingPut(): void
    {
        $resource = (new \ReflectionClass(Address::class))
            ->getAttributes(ApiResource::class)[0]
            ->newInstance();

        $post = null;
        $put = null;
        foreach ($resource->getOperations() as $operation) {
            if ($operation->getMethod() === 'POST') {
                $post = $operation;
            }
            if ($operation->getMethod() === 'PUT') {
                $put = $operation;
            }
        }

        self::assertNotNull($post);
        self::assertNotNull($put);
        self::assertSame(AddressDiscoveryInput::class, $post->getInput());
        self::assertSame(AddressUpdateInput::class, $put->getInput());
    }
}
