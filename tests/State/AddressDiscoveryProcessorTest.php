<?php

namespace ControleOnline\Common\Tests\State;

use ApiPlatform\Metadata\Operation;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\People;
use ControleOnline\State\AddressDiscoveryProcessor;
use ControleOnline\Service\AddressService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class AddressDiscoveryProcessorTest extends TestCase
{
    public function testProcessNormalizesCepAndForwardsFloatCoordinates(): void
    {
        $people = new People();
        $this->setEntityId($people, 7);

        $peopleRepository = $this->createMock(EntityRepository::class);
        $peopleRepository
            ->expects(self::once())
            ->method('find')
            ->with(7)
            ->willReturn($people);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->method('getRepository')
            ->with(People::class)
            ->willReturn($peopleRepository);

        $address = new Address();
        $addressService = $this->createMock(AddressService::class);
        $addressService
            ->expects(self::once())
            ->method('discoveryAddress')
            ->with(
                self::callback(static fn($postalCode): bool => $postalCode === '07062000'),
                self::callback(static fn($number): bool => $number === 2753),
                self::callback(static fn($street): bool => $street === 'Avenida Torres Tibagy'),
                self::callback(static fn($district): bool => $district === 'Gopoúva'),
                self::callback(static fn($city): bool => $city === 'Guarulhos'),
                self::callback(static fn($state): bool => $state === 'SP'),
                self::callback(static fn($country): bool => $country === 'BR'),
                self::callback(static fn($actualPeople): bool => $actualPeople === $people),
                self::callback(static fn($complement): bool => $complement === 'Casa'),
                self::callback(static fn($latitude): bool => abs((float) $latitude - (-23.45123)) < 0.000001),
                self::callback(static fn($longitude): bool => abs((float) $longitude - (-46.5321)) < 0.000001),
                self::callback(static fn($nickname): bool => $nickname === 'ROTA 1'),
            )
            ->willReturn($address);

        $processor = new AddressDiscoveryProcessor($addressService, $manager);
        $request = Request::create(
            '/addresses',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'people' => '/people/7',
                'cep' => '07062-000',
                'number' => 2753,
                'street' => 'Avenida Torres Tibagy',
                'district' => 'Gopoúva',
                'city' => 'Guarulhos',
                'state' => 'SP',
                'country' => 'BR',
                'complement' => 'Casa',
                'latitude' => '-23.45123',
                'longitude' => '-46.53210',
                'nickname' => 'ROTA 1',
            ], JSON_THROW_ON_ERROR)
        );

        $result = $processor->process(
            null,
            $this->createStub(Operation::class),
            [],
            ['request' => $request]
        );

        self::assertSame($address, $result);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
