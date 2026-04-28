<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Service\CleanupLogsMaintenanceRoutine;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\MaintenanceRoutineHandlerInterface;
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
            [$this->createCleanupRoutineHandler()],
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
            [$this->createCleanupRoutineHandler()],
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
        $service = new MaintenanceRoutineService(
            $this->createMock(ConfigService::class),
            $this->createMock(PeopleRoleService::class),
            [$this->createCleanupRoutineHandler(['deletedTotal' => 3])],
        );

        $result = $service->runRoutine(
            MaintenanceRoutineService::ROUTINE_CLEANUP_LOGS
        );

        self::assertSame('success', $result['status']);
        self::assertSame(3, $result['summary']['deletedTotal']);
    }

    private function createCleanupRoutineHandler(
        array $summary = ['deletedTotal' => 0],
    ): MaintenanceRoutineHandlerInterface {
        $handler = $this->createMock(CleanupLogsMaintenanceRoutine::class);
        $handler->method('getDefinition')->willReturn([
            'key' => MaintenanceRoutineService::ROUTINE_CLEANUP_LOGS,
            'title' => 'Limpeza de logs',
            'description' => 'Remove logs expirados conforme a politica configurada.',
            'defaultEnabled' => true,
            'defaultCronExpression' => '* * * * *',
        ]);
        $handler->method('run')->willReturn([
            'key' => MaintenanceRoutineService::ROUTINE_CLEANUP_LOGS,
            'status' => 'success',
            'summary' => $summary,
        ]);

        return $handler;
    }
}
