<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\Category;
use ControleOnline\Entity\Config;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Repository\CategoryRepository;
use ControleOnline\Service\CategoryTreeService;
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
            ->method('findTreeCandidates')
            ->with(21, 'products')
            ->willReturn([]);

        $result = $this->service($domainService, $configService, $repository)
            ->getCollection(21, 'bebida', true, 2, 10, [4, 5]);

        self::assertSame(0, $result['totalItems']);
        self::assertSame(2, $result['page']);
        self::assertSame(10, $result['itemsPerPage']);
    }

    public function testCollectionRejectsCompanyOutsidePublicShopScope(): void
    {
        [$domainService, $configService] = $this->createPublicScope([21]);
        $repository = $this->createMock(CategoryRepository::class);
        $repository->expects(self::never())->method('findTreeCandidates');

        $result = $this->service($domainService, $configService, $repository)
            ->getCollection(99, '', false, 1, 30);

        self::assertSame([], $result['items']);
        self::assertSame(0, $result['totalItems']);
    }

    public function testCollectionRejectsNonShopDomainEvenForDomainCompany(): void
    {
        [$domainService, $configService, $peopleDomain] = $this->createPublicScope([21]);
        $peopleDomain->setDomainType('ERP');
        $repository = $this->createMock(CategoryRepository::class);
        $repository->expects(self::never())->method('findTreeCandidates');

        $result = $this->service($domainService, $configService, $repository)
            ->getCollection(3, '', false, 1, 30);

        self::assertSame([], $result['items']);
        self::assertSame(0, $result['totalItems']);
    }

    public function testSerializationExposesGenericSortOrder(): void
    {
        [$domainService, $configService] = $this->createPublicScope([]);
        $repository = $this->createMock(CategoryRepository::class);
        $company = $this->createMock(People::class);
        $company->method('getId')->willReturn(3);
        $category = (new Category())
            ->setName('Pizzas')
            ->setContext('products')
            ->setCompany($company)
            ->setSortOrder(7);

        $payload = $this->service($domainService, $configService, $repository)
            ->serializeCategory($category);

        self::assertSame(7, $payload['sortOrder']);
    }

    public function testItemIsLimitedToProjectedCategoryAndItsAncestors(): void
    {
        [$domainService, $configService] = $this->createPublicScope([]);
        $repository = $this->createMock(CategoryRepository::class);
        $company = $this->createMock(People::class);
        $company->method('getId')->willReturn(3);
        $root = $this->category(1, 'Cardápio', $company);
        $child = $this->category(2, 'Pizzas', $company, $root);
        $unrelated = $this->category(3, 'Oculta na projeção', $company);
        $repository->method('findTreeCandidates')->willReturn([$unrelated, $child, $root]);
        $service = $this->service($domainService, $configService, $repository);

        self::assertSame($root, $service->getItem(1, 3, [2]));
        self::assertNull($service->getItem(3, 3, [2]));
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

    private function service(
        DomainService $domainService,
        ConfigService $configService,
        CategoryRepository $repository
    ): PublicShopCategoryService {
        return new PublicShopCategoryService(
            $domainService,
            $configService,
            $repository,
            new CategoryTreeService()
        );
    }

    private function category(
        int $id,
        string $name,
        People $company,
        ?Category $parent = null
    ): Category {
        $category = (new Category())
            ->setName($name)
            ->setContext('products')
            ->setCompany($company)
            ->setParent($parent);
        $this->setEntityId($category, $id);

        return $category;
    }
}
