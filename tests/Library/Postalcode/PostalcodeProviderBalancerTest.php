<?php

namespace ControleOnline\Tests\Library\Postalcode;

use ControleOnline\Library\Postalcode\Entity\Address;
use ControleOnline\Library\Postalcode\Exception\InvalidParameterException;
use ControleOnline\Library\Postalcode\PostalcodeProviderBalancer;
use PHPUnit\Framework\TestCase;

class PostalcodeProviderBalancerTest extends TestCase
{
    public function testInvalidCepThrowsInvalidParameter(): void
    {
        $balancer = new PostalcodeProviderBalancer();
        $this->expectException(InvalidParameterException::class);
        $balancer->search('123');
    }

    public function testInvalidCepWithLettersThrows(): void
    {
        $balancer = new PostalcodeProviderBalancer();
        $this->expectException(InvalidParameterException::class);
        $balancer->search('ABCDEFGH');
    }

    public function testNormalizesDigitsFromFormattedCep(): void
    {
        $balancer = new PostalcodeProviderBalancer();
        $this->expectException(InvalidParameterException::class);
        $balancer->search('12.345-67');
    }

    public function testProviderPriorityOrderPrefersViacepAndBrasilApi(): void
    {
        $balancer = new PostalcodeProviderBalancer();
        $ref = new \ReflectionClass($balancer);
        $prop = $ref->getProperty('providers');
        $prop->setAccessible(true);
        $keys = array_keys($prop->getValue($balancer));
        $this->assertSame(
            ['viacep', 'brasilapi', 'postmon', 'googlemaps'],
            $keys,
            'CEP provider order must prefer ViaCEP/BrasilAPI over Postmon'
        );
    }

    public function testSearchReturnsAddressShapeWhenProviderAvailable(): void
    {
        $balancer = new PostalcodeProviderBalancer();
        try {
            $address = $balancer->search('01310100');
            $this->assertInstanceOf(Address::class, $address);
            $arr = $address->toArray();
            $this->assertArrayHasKey('postalCode', $arr);
            $this->assertArrayHasKey('street', $arr);
            $this->assertArrayHasKey('city', $arr);
            $this->assertArrayHasKey('state', $arr);
            $this->assertArrayHasKey('country', $arr);
            $this->assertNotEmpty($balancer->getProviderCodeName());
        } catch (\Throwable $e) {
            $this->assertTrue(
                $e instanceof \ControleOnline\Library\Postalcode\Exception\ProviderRequestException
                || $e instanceof \ControleOnline\Library\Postalcode\Exception\PostalcodeNotFoundException,
                'Unexpected exception type: ' . get_class($e) . ' — ' . $e->getMessage()
            );
        }
    }
}
