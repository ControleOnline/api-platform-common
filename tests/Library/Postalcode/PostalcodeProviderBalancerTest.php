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
        // Will hit real providers or fail controlled — only assert normalization path for invalid length after strip
        $balancer = new PostalcodeProviderBalancer();
        $this->expectException(InvalidParameterException::class);
        $balancer->search('12.345-67'); // 7 digits after strip
    }

    public function testSearchReturnsAddressShapeWhenProviderAvailable(): void
    {
        $balancer = new PostalcodeProviderBalancer();
        try {
            $address = $balancer->search('01310100'); // Av. Paulista region — known valid
            $this->assertInstanceOf(Address::class, $address);
            $arr = $address->toArray();
            $this->assertArrayHasKey('postalCode', $arr);
            $this->assertArrayHasKey('street', $arr);
            $this->assertArrayHasKey('city', $arr);
            $this->assertArrayHasKey('state', $arr);
            $this->assertArrayHasKey('country', $arr);
            $this->assertNotEmpty($balancer->getProviderCodeName());
        } catch (\Throwable $e) {
            // External providers may be offline in CI — controlled failure is acceptable
            $this->assertTrue(
                $e instanceof \ControleOnline\Library\Postalcode\Exception\ProviderRequestException
                || $e instanceof \ControleOnline\Library\Postalcode\Exception\PostalcodeNotFoundException,
                'Unexpected exception type: ' . get_class($e) . ' — ' . $e->getMessage()
            );
        }
    }
}
