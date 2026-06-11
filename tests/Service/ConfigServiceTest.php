<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\Module;
use ControleOnline\Entity\PaymentType;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Wallet;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\PeopleService;
use ControleOnline\Service\TechnicalConfigAccessService;
use ControleOnline\Service\WalletService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class ConfigServiceTest extends TestCase
{
    public function testAddConfigReplacesListValues(): void
    {
        $config = new Config();
        $config->setConfigValue('["2211"]');

        $service = $this->createServiceForConfig($config);
        $service->addConfig(
            new People(),
            'shop-franchise-visible-company-ids',
            [],
            new Module(),
            'public'
        );

        self::assertSame('[]', $config->getConfigValue());
    }

    public function testAddConfigKeepsAssociativeArrayMerge(): void
    {
        $config = new Config();
        $config->setConfigValue('{"enabled":"1","mode":"old"}');

        $service = $this->createServiceForConfig($config);
        $service->addConfig(
            new People(),
            'device-options',
            ['mode' => 'new'],
            new Module(),
            'public'
        );

        self::assertSame(
            ['enabled' => '1', 'mode' => 'new'],
            json_decode($config->getConfigValue(), true)
        );
    }

    public function testAddConfigSynchronizesDuplicatedKeysAcrossModules(): void
    {
        $firstConfig = new Config();
        $firstConfig->setConfigValue('["2211"]');
        $secondConfig = new Config();
        $secondConfig->setConfigValue('["2211"]');

        $service = $this->createServiceForConfigs([$firstConfig, $secondConfig]);
        $service->addConfig(
            new People(),
            'shop-franchise-visible-company-ids',
            [],
            new Module(),
            'public'
        );

        self::assertSame('[]', $firstConfig->getConfigValue());
        self::assertSame('[]', $secondConfig->getConfigValue());
    }

    public function testGetConfigRequiresExplicitPeopleContext(): void
    {
        $service = new ConfigService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(WalletService::class),
            $this->createStub(PeopleService::class),
            $this->createStub(TechnicalConfigAccessService::class)
        );

        self::assertNull($service->getConfig(null, 'OAUTH_IFOOD_CLIENT_ID'));
    }

    public function testDiscoveryMainConfigsMakesGatewayWalletsPublic(): void
    {
        $company = new People();
        $storedConfigs = [];

        $configRepository = $this->createMock(EntityRepository::class);
        $configRepository
            ->method('findBy')
            ->willReturnCallback(function (array $criteria) use (&$storedConfigs) {
                return array_values(array_filter(
                    $storedConfigs,
                    static function (Config $config) use ($criteria): bool {
                        foreach ($criteria as $field => $expectedValue) {
                            if (
                                $field === 'people' &&
                                $config->getPeople() !== $expectedValue
                            ) {
                                return false;
                            }

                            if (
                                $field === 'configKey' &&
                                $config->getConfigKey() !== $expectedValue
                            ) {
                                return false;
                            }

                            if (
                                $field === 'visibility' &&
                                $config->getVisibility() !== $expectedValue
                            ) {
                                return false;
                            }
                        }

                        return true;
                    }
                ));
            });

        $moduleRepository = $this->createMock(EntityRepository::class);
        $moduleRepository
            ->method('find')
            ->with(8)
            ->willReturn(new Module());

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->method('getRepository')
            ->willReturnCallback(
                static fn(string $entityClass) => match ($entityClass) {
                    Config::class => $configRepository,
                    Module::class => $moduleRepository,
                }
            );
        $manager
            ->method('persist')
            ->willReturnCallback(function (Config $config) use (&$storedConfigs): void {
                if (!in_array($config, $storedConfigs, true)) {
                    $storedConfigs[] = $config;
                }
            });
        $manager->method('flush');

        $walletService = $this->createMock(WalletService::class);
        $walletService
            ->method('discoverWallet')
            ->willReturnCallback(function (People $people, string $walletName): Wallet {
                $wallet = $this->createMock(Wallet::class);
                $wallet
                    ->method('getId')
                    ->willReturn(match ($walletName) {
                        'Caixa' => 101,
                        'Reserva' => 102,
                        'Infine Pay' => 103,
                        'Cielo' => 104,
                        default => 999,
                    });

                return $wallet;
            });
        $walletService
            ->method('discoverPaymentType')
            ->willReturn(new PaymentType());

        $technicalConfigAccessService = $this->createMock(
            TechnicalConfigAccessService::class
        );
        $technicalConfigAccessService
            ->expects(self::exactly(4))
            ->method('assertCanManageConfig');

        $domainService = $this->createStub(DomainService::class);
        $domainService
            ->method('getDomain')
            ->willReturn('app.controleonline.com');

        $service = new ConfigService(
            $manager,
            $walletService,
            $this->createStub(PeopleService::class),
            $technicalConfigAccessService,
            $domainService
        );

        $configs = $service->discoveryMainConfigs($company);
        $configsByKey = [];

        foreach ($configs as $config) {
            $configsByKey[$config->getConfigKey()] = $config;
        }

        self::assertArrayHasKey('pos-cielo-wallet', $configsByKey);
        self::assertArrayHasKey('pos-infinite-pay-wallet', $configsByKey);
        self::assertSame('public', $configsByKey['pos-cielo-wallet']->getVisibility());
        self::assertSame(
            'public',
            $configsByKey['pos-infinite-pay-wallet']->getVisibility()
        );
    }

    private function createServiceForConfig(Config $config): ConfigService
    {
        return $this->createServiceForConfigs([$config]);
    }

    /**
     * @param Config[] $configs
     */
    private function createServiceForConfigs(array $configs): ConfigService
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->method('findBy')
            ->willReturn($configs);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->method('getRepository')
            ->with(Config::class)
            ->willReturn($repository);
        $manager
            ->expects(self::exactly(count($configs)))
            ->method('persist')
            ->with(self::isInstanceOf(Config::class));
        $manager
            ->expects(self::once())
            ->method('flush');

        $technicalConfigAccessService = $this->createMock(
            TechnicalConfigAccessService::class
        );
        $technicalConfigAccessService
            ->expects(self::once())
            ->method('assertCanManageConfig');

        return new ConfigService(
            $manager,
            $this->createStub(WalletService::class),
            $this->createStub(PeopleService::class),
            $technicalConfigAccessService
        );
    }
}
