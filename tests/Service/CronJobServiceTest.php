<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\CronJob;
use ControleOnline\Service\CronJobService;
use ControleOnline\Service\DatabaseSwitchService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;

class CronJobServiceTest extends TestCase
{
    public function testNormalizesConfiguredJobsAndChecksValidity(): void
    {
        $appDomain = $this->getConfiguredAppDomain();

        $service = new CronJobService(
            $this->createStub(Connection::class),
            $this->createStub(DatabaseSwitchService::class),
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
        self::assertSame('tenant', $normalized['websocket_start']['scope']);
        self::assertTrue($normalized['websocket_start']['isValid']);
        self::assertFalse($normalized['invalid_job']['isValid']);
    }

    public function testReturnsConfiguredJobsFromCentralTable(): void
    {
        $appDomain = $this->getConfiguredAppDomain();
        $result = $this->createMock(Result::class);
        $result->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'id' => 42,
                    'scope' => 'tenant',
                    'title' => 'Manutencao',
                    'description' => 'Executa as rotinas de manutencao da empresa principal.',
                    'enabled' => 1,
                    'cronExpression' => '* * * * *',
                    'command' => 'app:maintenance:run',
                    'arguments' => json_encode(['--domain=' . $appDomain]),
                ],
            ]);

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(1);
        $connection->expects(self::once())
            ->method('executeQuery')
            ->willReturn($result);

        $service = new CronJobService(
            $connection,
            $this->createStub(DatabaseSwitchService::class),
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
