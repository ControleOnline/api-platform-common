<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Service\MaintenanceRoutineService;
use ControleOnline\Service\PeopleRoleService;
use ControleOnline\Service\PeopleService;
use ControleOnline\Service\SystemLogConfigService;
use ControleOnline\Service\TechnicalConfigAccessService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class TechnicalConfigAccessServiceTest extends TestCase
{
    public function testAllowsManagingTechnicalConfigOnlyOnMainCompany(): void
    {
        $mainCompany = $this->createStub(People::class);
        $mainCompany->method('getId')->willReturn(10);

        $peopleRoleService = $this->createStub(PeopleRoleService::class);
        $peopleRoleService->method('getMainCompany')->willReturn($mainCompany);

        $peopleService = $this->createStub(PeopleService::class);
        $peopleService->method('getMyPeople')->willReturn(null);
        $peopleService->method('getMyCompanies')->willReturn([$mainCompany]);

        $service = new TechnicalConfigAccessService(
            $peopleRoleService,
            $peopleService
        );

        self::assertTrue($service->canAccessMainCompanyTechnicalSettings());
        $service->assertCanManageConfig(
            $mainCompany,
            TechnicalConfigAccessService::GOOGLE_OAUTH_CLIENT_ID_KEY
        );
        $service->assertCanManageConfig(
            $mainCompany,
            MaintenanceRoutineService::ROUTINES_CONFIG_KEY
        );
        $service->assertCanManageConfig(
            $mainCompany,
            SystemLogConfigService::POLICY_CONFIG_KEY
        );
    }

    public function testBlocksTechnicalConfigOutsideMainCompany(): void
    {
        $mainCompany = $this->createStub(People::class);
        $mainCompany->method('getId')->willReturn(10);

        $secondaryCompany = $this->createStub(People::class);
        $secondaryCompany->method('getId')->willReturn(20);

        $peopleRoleService = $this->createStub(PeopleRoleService::class);
        $peopleRoleService->method('getMainCompany')->willReturn($mainCompany);

        $peopleService = $this->createStub(PeopleService::class);
        $peopleService->method('getMyPeople')->willReturn(null);
        $peopleService->method('getMyCompanies')->willReturn([$secondaryCompany]);

        $service = new TechnicalConfigAccessService(
            $peopleRoleService,
            $peopleService
        );

        self::assertFalse($service->canAccessMainCompanyTechnicalSettings());

        $this->expectException(AccessDeniedException::class);
        $service->assertCanManageConfig(
            $secondaryCompany,
            TechnicalConfigAccessService::GOOGLE_OAUTH_CLIENT_ID_KEY
        );
    }
}
