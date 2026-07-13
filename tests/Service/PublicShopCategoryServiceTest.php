<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Repository\CategoryRepository;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\PublicShopCategoryService;
use PHPUnit\Framework\TestCase;

class PublicShopCategoryServiceTest extends TestCase
{
    public function testCollectionAllowsDomainCompanyAndConfiguredPublicFranchise(): void
    {
        [$domainService, $configService] = $this->createPublicScope([21]);
        $repository = $this->createMock(CategoryRepository::class);
        $repository->expects(self::once())
            ->method('findPublicShopCategories')
            ->with(21, 'bebida', true, 2, 10)
            ->willReturn([]);
        $repository->expects(self::once())
            ->method('countPublicShopCategories')
            ->with(21, 'bebida', true)
            ->willReturn(0);

        $result = (new PublicShopCategoryService(
            $domainService,
            $configService,
            $repository
        ))->getCollection(21, 'bebida', true, 2, 10);

        self::assertSame(0, $result['totalItems']);
        self::assertSame(2, $result['page']);
        self::assertSame(10, $result['itemsPerPage']);
    }

    public function testCollectionRejectsCompanyOutsidePublicShopScope(): void
    {
        [$domainService, $configService] = $this->createPublicScope([21]);
        $repository = $this->createMock(CategoryRepository::class);
        $repository->expects(self::never())->method('findPublicShopCategories');
        $repository->expects(self::never())->method('countPublicShopCategories');

        $result = (new PublicShopCategoryService(
            $domainService,
            $configService,
            $repository
        ))->getCollection(99, '', false, 1, 30);

        self::assertSame([], $result['items']);
        self::assertSame(0, $result['totalItems']);
    }

    public function testCollectionRejectsNonShopDomainEvenForDomainCompany(): void
    {
        [$domainService, $configService, $peopleDomain] = $this->createPublicScope([21]);
        $peopleDomain->setDomainType('ERP');
        $repository = $this->createMock(CategoryRepository::class);
        $repository->expects(self::never())->method('findPublicShopCategories');
        $repository->expects(self::never())->method('countPublicShopCategories');

        $result = (new PublicShopCategoryService(
            $domainService,
            $configService,
            $repository
        ))->getCollection(3, '', false, 1, 30);

        self::assertSame([], $result['items']);
        self::assertSame(0, $result['totalItems']);
    }

    /**
     * @return array{DomainService, ConfigService, PeopleDomain}
     */
    private function createPublicScope(array $visibleCompanyIds): array
    {
        $company = new People();
        $this->setEntityId($company, 3);
        $peopleDomain = new PeopleDomain();
        $peopleDomain->setPeople($company);
        $peopleDomain->setDomainType('SHOP');

        $domainService = $this->createMock(DomainService::class);
        $domainService->method('getPeopleDomain')->willReturn($peopleDomain);

        $config = new Config();
        $config->setConfigKey('shop-franchise-visible-company-ids');
        $config->setConfigValue(json_encode($visibleCompanyIds));
        $configService = $this->createMock(ConfigService::class);
        $configService->method('getCompanyConfigs')
            ->with($company, 'public')
            ->willReturn([$config]);

        return [$domainService, $configService, $peopleDomain];
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }
}
