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
        $appDomain = $this->getConfiguredAppDomain();

        $service = new CronJobService(
            $this->createStub(CronJobRepository::class),
            $this->createStub(PeopleRoleService::class),
        );

        $normalized = $service->normalizeConfiguredJobs([
            'websocket_start' => [
                'enabled' => '1',
                'cronExpression' => '*/5 * * * *',
                'command' => 'websocket:start',
                'arguments' => '--domain=' . $appDomain . ', -p | 8080',
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
            ['--domain=' . $appDomain, '-p', '8080'],
            $normalized['websocket_start']['arguments']
        );
        self::assertSame(10, $normalized['websocket_start']['sortOrder']);
        self::assertTrue($normalized['websocket_start']['isValid']);
        self::assertFalse($normalized['invalid_job']['isValid']);
    }

    public function testReturnsConfiguredJobsFromMainCompany(): void
    {
        $appDomain = $this->getConfiguredAppDomain();

        $mainCompany = $this->createStub(People::class);

        $cronJob = $this->createCronJob(
            'Manutencao',
            'Executa as rotinas de manutencao da empresa principal.',
            true,
            '* * * * *',
            'app:maintenance:run',
            ['--domain=' . $appDomain],
            42
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

        self::assertArrayHasKey(42, $jobs);
        self::assertSame('Manutencao', $jobs[42]['title']);
        self::assertSame('app:maintenance:run', $jobs[42]['command']);
        self::assertTrue($jobs[42]['enabled']);
        self::assertSame(['--domain=' . $appDomain], $jobs[42]['arguments']);
    }

    private function getConfiguredAppDomain(): string
    {
        $domain = trim((string) (
            $_ENV['APP_DOMAIN']
                ?? $_SERVER['APP_DOMAIN']
                ?? getenv('APP_DOMAIN')
                ?? ''
        ));

        self::assertNotSame('', $domain, 'APP_DOMAIN must be configured for cron job tests.');

        return $domain;
    }

    private function createCronJob(
        string $title,
        string $description,
        bool $enabled,
        string $cronExpression,
        string $command,
        array $arguments,
        int $id = 0
    ): CronJob {
        $cronJob = new CronJob();
        $cronJob
            ->setTitle($title)
            ->setDescription($description)
            ->setEnabled($enabled)
            ->setCronExpression($cronExpression)
            ->setCommand($command)
            ->setArguments($arguments);

        if ($id > 0) {
            $reflectionProperty = new \ReflectionProperty(CronJob::class, 'id');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($cronJob, $id);
        }

        return $cronJob;
    }
}
