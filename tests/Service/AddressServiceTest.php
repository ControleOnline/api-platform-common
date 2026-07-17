<?php

namespace ControleOnline\Common\Tests\Service;

use ControleOnline\Entity\Address;
use ControleOnline\Entity\Cep;
use ControleOnline\Entity\City;
use ControleOnline\Entity\Country;
use ControleOnline\Entity\District;
use ControleOnline\Entity\People;
use ControleOnline\Entity\State;
use ControleOnline\Entity\Street;
use ControleOnline\Service\AddressService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use LogicException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AddressServiceTest extends TestCase
{
    public function testDiscoveryAddressPreservesNegativeCoordinates(): void
    {
        $people = new People();
        $this->setEntityId($people, 7);

        $cep = new Cep();
        $cep->setCep(7062000);

        $country = new Country();
        $state = new State();
        $city = new City();
        $district = new District();
        $street = new Street();

        $addressRepository = $this->createMock(EntityRepository::class);
        $addressRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(
                self::callback(static function (array $criteria) use ($people): bool {
                    return ($criteria['people'] ?? null) === $people
                        && ($criteria['number'] ?? null) === 2753
                        && ($criteria['complement'] ?? null) === 'Casa';
                })
            )
            ->willReturn(null);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->method('getRepository')
            ->willReturnCallback(static function (string $entityClass) use ($addressRepository): EntityRepository {
                if ($entityClass === Address::class) {
                    return $addressRepository;
                }

                throw new LogicException(sprintf('Unexpected repository request for %s', $entityClass));
            });

        $persistedAddress = null;
        $manager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (Address $address) use (&$persistedAddress, $people, $street): bool {
                $persistedAddress = $address;

                return $address->getPeople() === $people
                    && $address->getStreet() === $street
                    && $address->getNumber() === 2753
                    && $address->getNickname() === 'ROTA 1'
                    && $address->getComplement() === 'CASA'
                    && $address->getLatitude() === -23.45123
                    && $address->getLongitude() === -46.5321;
            }));
        $manager
            ->expects(self::once())
            ->method('flush');

        $service = $this->getMockBuilder(AddressService::class)
            ->setConstructorArgs([$manager])
            ->onlyMethods([
                'discoveryCep',
                'getCountry',
                'discoveryState',
                'discoveryCity',
                'discoveryDistrict',
                'discoveryStreet',
            ])
            ->getMock();
        $service->method('discoveryCep')->willReturn($cep);
        $service->method('getCountry')->willReturn($country);
        $service->method('discoveryState')->willReturn($state);
        $service->method('discoveryCity')->willReturn($city);
        $service->method('discoveryDistrict')->willReturn($district);
        $service->method('discoveryStreet')->willReturn($street);

        $result = $service->discoveryAddress(
            '07062000',
            2753,
            'Avenida Torres Tibagy',
            'Gopoúva',
            'Guarulhos',
            'SP',
            'BR',
            $people,
            'Casa',
            -23.45123,
            -46.53210,
            'ROTA 1'
        );

        self::assertSame($persistedAddress, $result);
        self::assertSame(-23.45123, $result->getLatitude());
        self::assertSame(-46.5321, $result->getLongitude());
        self::assertSame(2753, $result->getNumber());
        self::assertSame('ROTA 1', $result->getNickname());
        self::assertSame('CASA', $result->getComplement());
        self::assertSame($people, $result->getPeople());
        self::assertSame($street, $result->getStreet());
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
