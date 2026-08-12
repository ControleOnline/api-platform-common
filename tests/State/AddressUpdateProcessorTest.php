<?php

namespace ControleOnline\Common\Tests\State;

use ApiPlatform\Metadata\Operation;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\Cep;
use ControleOnline\Entity\City;
use ControleOnline\Entity\Country;
use ControleOnline\Entity\District;
use ControleOnline\Entity\State;
use ControleOnline\Entity\Street;
use ControleOnline\Service\AddressService;
use ControleOnline\State\AddressUpdateInput;
use ControleOnline\State\AddressUpdateProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class AddressUpdateProcessorTest extends TestCase
{
    public function testTextualPayloadUpdatesTheExistingAddressRelation(): void
    {
        $address = new Address();
        $street = $this->createStub(Street::class);
        $cep = $this->createStub(Cep::class);
        $country = $this->createStub(Country::class);
        $state = $this->createStub(State::class);
        $city = $this->createStub(City::class);
        $district = $this->createStub(District::class);

        $addressRepository = $this->createMock(EntityRepository::class);
        $addressRepository
            ->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($address);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->method('getRepository')
            ->with(Address::class)
            ->willReturn($addressRepository);
        $manager->expects(self::once())->method('persist')->with($address);
        $manager->expects(self::once())->method('flush');

        $addressService = $this->createMock(AddressService::class);
        $addressService->method('getCountry')->with('BR')->willReturn($country);
        $addressService->method('discoveryState')->with($country, 'SP')->willReturn($state);
        $addressService->method('discoveryCity')->with($state, 'Guarulhos')->willReturn($city);
        $addressService->method('discoveryDistrict')->with($city, 'Jardim Alianca')->willReturn($district);
        $addressService->method('discoveryCep')->with('07063080')->willReturn($cep);
        $addressService->method('discoveryStreet')->with($cep, $district, 'Rua Antonio Rabello')->willReturn($street);

        $input = new AddressUpdateInput();
        $input->cep = '07063-080';
        $input->street = 'Rua Antonio Rabello';
        $input->district = 'Jardim Alianca';
        $input->city = 'Guarulhos';
        $input->state = 'SP';
        $input->country = 'BR';
        $input->number = '424';
        $input->complement = 'Loja';
        $input->nickname = 'LOJA';
        $input->latitude = '-23.45';
        $input->longitude = '-46.53';

        $processor = new AddressUpdateProcessor($manager, $addressService);

        $result = $processor->process(
            $input,
            $this->createStub(Operation::class),
            ['id' => 42]
        );

        self::assertSame($address, $result);
        self::assertSame($street, $address->getStreet());
        self::assertSame(424, $address->getNumber());
        self::assertSame('LOJA', $address->getNickname());
        self::assertSame('LOJA', $address->getComplement());
        self::assertSame(-23.45, $address->getLatitude());
        self::assertSame(-46.53, $address->getLongitude());
    }
}
