<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\Module;
use ControleOnline\Entity\People;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\PeopleService;
use ControleOnline\Service\TechnicalConfigAccessService;
use ControleOnline\Service\WalletService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
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

    private function createServiceForConfig(Config $config): ConfigService
    {
        return $this->createServiceForConfigs([$config]);
    }

    /**
     * @param Config[] $configs
     */
    private function createServiceForConfigs(array $configs): ConfigService
    {
        $repository = $this->createMock(ObjectRepository::class);
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
