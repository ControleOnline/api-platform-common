<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\Device;
use ControleOnline\Entity\DeviceConfig;
use ControleOnline\Entity\People;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\DeviceService;
use ControleOnline\Service\PeopleService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DeviceServiceTest extends TestCase
{
    public function testSecurityFilterBlocksAccessWithoutCompaniesOrMatchingSelfDevice(): void
    {
        $peopleService = $this->createMock(PeopleService::class);
        $peopleService->method('getMyCompanies')->willReturn([]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(self::once())
            ->method('andWhere')
            ->with('1 = 0')
            ->willReturnSelf();
        $queryBuilder
            ->expects(self::never())
            ->method('setParameter');

        $service = $this->buildService(
            Request::create('http://localhost:8000/devices?device=web-31484', 'GET'),
            $peopleService
        );

        $service->securityFilter($queryBuilder, null, null, 'o');
    }

    public function testSecurityFilterAllowsClientOwnDeviceWithoutCompaniesWhenHeaderMatchesPayload(): void
    {
        $peopleService = $this->createMock(PeopleService::class);
        $peopleService->method('getMyCompanies')->willReturn([]);

        $request = Request::create(
            'http://localhost:8000/devices/1437',
            'PUT',
            [],
            [],
            [],
            [],
            json_encode([
                'device' => 'web-31484',
                'alias' => 'Caixa',
            ], JSON_THROW_ON_ERROR)
        );
        $request->headers->set('device', 'web-31484');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(self::once())
            ->method('andWhere')
            ->with('o.device = :selfDevice')
            ->willReturnSelf();
        $queryBuilder
            ->expects(self::once())
            ->method('setParameter')
            ->with('selfDevice', 'web-31484')
            ->willReturnSelf();

        $service = $this->buildService($request, $peopleService);

        $service->securityFilter($queryBuilder, null, null, 'o');
    }

    public function testFindDeviceConfigIgnoresProductTypeArrayFilter(): void
    {
        $device = $this->createStub(Device::class);
        $people = $this->createStub(People::class);
        $peopleService = $this->createStub(PeopleService::class);
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::never())
            ->method('findOneBy');
        $repository
            ->expects(self::once())
            ->method('findBy')
            ->with([
                'device' => $device,
                'people' => $people,
            ], ['id' => 'ASC'])
            ->willReturn([]);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $request = Request::create(
            'http://localhost:8000/product-showcases/catalog?type[]=service&type[]=product',
            'GET'
        );

        $service = $this->buildService($request, $peopleService, $manager);

        self::assertNull($service->findDeviceConfig($device, $people));
    }

    public function testFindDeviceConfigUsesExplicitDeviceTypeHeader(): void
    {
        $device = $this->createStub(Device::class);
        $people = $this->createStub(People::class);
        $deviceConfig = $this->createStub(DeviceConfig::class);
        $peopleService = $this->createStub(PeopleService::class);
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with([
                'device' => $device,
                'people' => $people,
                'type' => 'PDV',
            ])
            ->willReturn($deviceConfig);
        $repository
            ->expects(self::never())
            ->method('findBy');

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $request = Request::create(
            'http://localhost:8000/product-showcases/catalog?type[]=service&type[]=product',
            'GET'
        );
        $request->headers->set('device-type', 'PDV');

        $service = $this->buildService($request, $peopleService, $manager);

        self::assertSame(
            $deviceConfig,
            $service->findDeviceConfig($device, $people)
        );
    }

    private function buildService(
        Request $request,
        PeopleService $peopleService,
        ?EntityManagerInterface $manager = null,
    ): DeviceService {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new DeviceService(
            $manager ?? $this->createStub(EntityManagerInterface::class),
            $this->createStub(ConfigService::class),
            $peopleService,
            $requestStack,
        );
    }
}
