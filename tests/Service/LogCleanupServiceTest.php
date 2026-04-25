<?php

namespace ControleOnline\Common\Tests\Service;

use ControleOnline\Service\LogCleanupService;
use ControleOnline\Service\SystemLogConfigService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class LogCleanupServiceTest extends TestCase
{
    public function testDeletesExpiredLogsForConfiguredPolicies(): void
    {
        $configService = $this->createMock(SystemLogConfigService::class);
        $configService
            ->method('getLogPolicy')
            ->willReturn([
                SystemLogConfigService::POLICY_GENERIC => [
                    'enabled' => true,
                    'retentionDays' => 30,
                ],
                SystemLogConfigService::POLICY_BACKEND_ERROR => [
                    'enabled' => true,
                    'retentionDays' => 7,
                ],
                SystemLogConfigService::POLICY_ENTITY => [
                    'enabled' => true,
                    'retentionDays' => null,
                ],
            ]);
        $configService
            ->method('getSpecialGenericChannels')
            ->willReturn([
                'backend-error' => SystemLogConfigService::POLICY_BACKEND_ERROR,
                'frontend-debug' => SystemLogConfigService::POLICY_FRONTEND_DEBUG,
            ]);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql, array $params): int {
                self::assertStringContainsString('DELETE FROM log', $sql);
                self::assertArrayHasKey('cutoff', $params);

                if (str_contains($sql, '= :channel')) {
                    self::assertSame('backend-error', $params['channel']);

                    return 2;
                }

                self::assertSame('generic', $params['type']);
                self::assertSame('backend-error', $params['channel_1']);
                self::assertSame('frontend-debug', $params['channel_2']);

                return 5;
            });

        $service = new LogCleanupService($connection, $configService);
        $summary = $service->cleanup();

        self::assertSame(7, $summary['deletedTotal']);
        self::assertSame(5, $summary['deletedByPolicy'][SystemLogConfigService::POLICY_GENERIC]);
        self::assertSame(2, $summary['deletedByPolicy'][SystemLogConfigService::POLICY_BACKEND_ERROR]);
    }
}
