<?php

namespace ControleOnline\Common\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\PeopleRoleService;
use ControleOnline\Service\SystemLogConfigService;
use PHPUnit\Framework\TestCase;

class SystemLogConfigServiceTest extends TestCase
{
    public function testResolvesEmailSettingsAndPolicyFromMainCompanyConfigs(): void
    {
        $mainCompany = new People();

        $configService = $this->createMock(ConfigService::class);
        $configService
            ->method('getConfig')
            ->willReturnCallback(function (People $people, string $key, bool $json): mixed {
                self::assertTrue($json);

                return match ($key) {
                    SystemLogConfigService::ERROR_EMAIL_ENABLED_KEY => true,
                    SystemLogConfigService::ERROR_EMAIL_RECIPIENTS_KEY => [
                        'ops@empresa.com.br',
                        'financeiro@empresa.com.br',
                    ],
                    SystemLogConfigService::POLICY_CONFIG_KEY => [
                        SystemLogConfigService::POLICY_GENERIC => [
                            'enabled' => false,
                            'retentionDays' => 15,
                        ],
                        SystemLogConfigService::POLICY_BACKEND_ERROR => [
                            'enabled' => true,
                            'retentionDays' => null,
                        ],
                    ],
                    default => null,
                };
            });

        $peopleRoleService = $this->createMock(PeopleRoleService::class);
        $peopleRoleService
            ->method('getMainCompany')
            ->willReturn($mainCompany);

        $service = new SystemLogConfigService($configService, $peopleRoleService);

        self::assertSame(
            [
                'enabled' => true,
                'recipients' => [
                    'ops@empresa.com.br',
                    'financeiro@empresa.com.br',
                ],
            ],
            $service->getErrorEmailSettings()
        );

        self::assertFalse($service->shouldPersist('generic'));
        self::assertSame(15, $service->resolveRetentionDays('generic'));
        self::assertTrue($service->shouldPersist('generic', 'backend-error'));
        self::assertNull($service->resolveRetentionDays('generic', 'backend-error'));
        self::assertSame(
            SystemLogConfigService::POLICY_BACKEND_ERROR,
            $service->resolvePolicyKey('generic', 'backend-error')
        );
    }
}
