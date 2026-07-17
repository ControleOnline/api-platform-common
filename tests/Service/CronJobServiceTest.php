<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\CronJob;
use ControleOnline\Entity\People;
use ControleOnline\Repository\CronJobRepository;
use ControleOnline\Service\CronJobService;
use ControleOnline\Service\PeopleRoleService;
use PHPUnit\Framework\TestCase;

class CronJobServiceTest extends TestCase
{
    public function testNormalizesConfiguredJobsAndChecksValidity(): void
    {
        $service = new CronJobService(
            $this->createStub(CronJobRepository::class),
            $this->createStub(PeopleRoleService::class),
        );

        $normalized = $service->normalizeConfiguredJobs([
            'websocket_start' => [
                'enabled' => '1',
                'cronExpression' => '*/5 * * * *',
                'command' => 'websocket:start',
                'arguments' => '--domain=api.controleonline.com, -p | 8080',
                'background' => true,
                'sortOrder' => 10,
            ],
            'invalid_job' => [
                'enabled' => false,
                'cronExpression' => 'invalid cron',
                'command' => 'import:start',
            ],
        ]);

        self::assertTrue($normalized['websocket_start']['enabled']);
        self::assertSame('websocket:start', $normalized['websocket_start']['command']);
        self::assertSame(
            ['--domain=api.controleonline.com', '-p', '8080'],
            $normalized['websocket_start']['arguments']
        );
        self::assertSame(10, $normalized['websocket_start']['sortOrder']);
        self::assertTrue($normalized['websocket_start']['isValid']);
        self::assertFalse($normalized['invalid_job']['isValid']);
    }

    public function testReturnsConfiguredJobsFromMainCompany(): void
    {
        $mainCompany = $this->createStub(People::class);

        $cronJob = $this->createCronJob(
            'maintenance_run',
            'Manutencao',
            'Executa as rotinas de manutencao da empresa principal.',
            true,
            '* * * * *',
            'app:maintenance:run',
            ['--domain=api.controleonline.com'],
            true,
            50
        );
        $cronJob->setPeople($mainCompany);

        $cronJobRepository = $this->createMock(CronJobRepository::class);
        $cronJobRepository
            ->expects(self::once())
            ->method('findMainCompanyJobs')
            ->with($mainCompany)
            ->willReturn([$cronJob]);

        $peopleRoleService = $this->createStub(PeopleRoleService::class);
        $peopleRoleService->method('getMainCompany')->willReturn($mainCompany);

        $service = new CronJobService(
            $cronJobRepository,
            $peopleRoleService,
        );

        $jobs = $service->getConfiguredJobs();

        self::assertArrayHasKey('maintenance_run', $jobs);
        self::assertSame('Manutencao', $jobs['maintenance_run']['title']);
        self::assertSame('app:maintenance:run', $jobs['maintenance_run']['command']);
        self::assertTrue($jobs['maintenance_run']['enabled']);
        self::assertSame(['--domain=api.controleonline.com'], $jobs['maintenance_run']['arguments']);
        self::assertSame(50, $jobs['maintenance_run']['sortOrder']);
    }

    private function createCronJob(
        string $jobKey,
        string $title,
        string $description,
        bool $enabled,
        string $cronExpression,
        string $command,
        array $arguments,
        bool $background,
        int $sortOrder
    ): CronJob {
        $cronJob = new CronJob();
        $cronJob
            ->setJobKey($jobKey)
            ->setTitle($title)
            ->setDescription($description)
            ->setEnabled($enabled)
            ->setCronExpression($cronExpression)
            ->setCommand($command)
            ->setArguments($arguments)
            ->setBackground($background)
            ->setSortOrder($sortOrder);

        return $cronJob;
    }
}
