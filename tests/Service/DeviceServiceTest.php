<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Service\ConfigService;
use ControleOnline\Service\DeviceService;
use ControleOnline\Service\PeopleService;
use Doctrine\ORM\EntityManagerInterface;
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

    private function buildService(
        Request $request,
        PeopleService $peopleService,
    ): DeviceService {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new DeviceService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(ConfigService::class),
            $peopleService,
            $requestStack,
        );
    }
}
