<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\LogCleanupService;
use ControleOnline\Service\MaintenanceRoutineService;
use ControleOnline\Service\PeopleRoleService;
use PHPUnit\Framework\TestCase;

class MaintenanceRoutineServiceTest extends TestCase
{
    public function testNormalizesConfiguredRoutinesAndChecksValidity(): void
    {
        $service = new MaintenanceRoutineService(
            $this->createMock(ConfigService::class),
            $this->createMock(PeopleRoleService::class),
            $this->createMock(LogCleanupService::class),
        );

        $normalized = $service->normalizeConfiguredRoutines([
            MaintenanceRoutineService::ROUTINE_CLEANUP_LOGS => [
                'enabled' => false,
                'cronExpression' => 'invalid cron',
            ],
        ]);

        self::assertFalse(
            $normalized[MaintenanceRoutineService::ROUTINE_CLEANUP_LOGS]['enabled']
        );
        self::assertSame(
            'invalid cron',
            $normalized[MaintenanceRoutineService::ROUTINE_CLEANUP_LOGS]['cronExpression']
        );
        self::assertFalse(
            $normalized[MaintenanceRoutineService::ROUTINE_CLEANUP_LOGS]['isValid']
        );
    }

    public function testReturnsDueRoutineForCurrentMinute(): void
    {
        $mainCompany = $this->createMock(People::class);

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getConfig')->willReturn([
            MaintenanceRoutineService::ROUTINE_CLEANUP_LOGS => [
                'enabled' => true,
                'cronExpression' => '* * * * *',
            ],
        ]);

        $peopleRoleService = $this->createMock(PeopleRoleService::class);
        $peopleRoleService->method('getMainCompany')->willReturn($mainCompany);

        $service = new MaintenanceRoutineService(
            $configService,
            $peopleRoleService,
            $this->createMock(LogCleanupService::class),
        );

        $dueRoutines = $service->getDueRoutines(
            new \DateTimeImmutable('2026-04-25 12:00:00')
        );

        self::assertCount(1, $dueRoutines);
        self::assertSame(
            MaintenanceRoutineService::ROUTINE_CLEANUP_LOGS,
            $dueRoutines[0]['key']
        );
    }

    public function testRunsCleanupLogsRoutine(): void
    {
        $cleanupService = $this->createMock(LogCleanupService::class);
        $cleanupService->expects(self::once())
            ->method('cleanup')
            ->willReturn(['deletedTotal' => 3]);

        $service = new MaintenanceRoutineService(
            $this->createMock(ConfigService::class),
            $this->createMock(PeopleRoleService::class),
            $cleanupService,
        );

        $result = $service->runRoutine(
            MaintenanceRoutineService::ROUTINE_CLEANUP_LOGS
        );

        self::assertSame('success', $result['status']);
        self::assertSame(3, $result['summary']['deletedTotal']);
    }
}
