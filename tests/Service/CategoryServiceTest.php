<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Service\CategoryService;
use ControleOnline\Service\PeopleService;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class CategoryServiceTest extends TestCase
{
    public function testSecurityFilterDelegatesCompanyScopeToPeopleService(): void
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRootAliases'])
            ->getMock();
        $queryBuilder->method('getRootAliases')->willReturn(['category']);

        $peopleService = $this->createMock(PeopleService::class);
        $peopleService
            ->expects(self::once())
            ->method('checkCompany')
            ->with(
                'company',
                $queryBuilder,
                'category-resource',
                'collection',
                'category'
            );

        $service = new CategoryService($peopleService);
        $service->securityFilter(
            $queryBuilder,
            'category-resource',
            'collection',
            'category'
        );
    }

    public function testSecurityFilterBlocksWhenRootAliasCannotBeResolved(): void
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRootAliases', 'andWhere'])
            ->getMock();
        $queryBuilder->method('getRootAliases')->willReturn([]);
        $queryBuilder
            ->expects(self::once())
            ->method('andWhere')
            ->with('1 = 0')
            ->willReturnSelf();

        $peopleService = $this->createMock(PeopleService::class);
        $peopleService->expects(self::never())->method('checkCompany');

        (new CategoryService($peopleService))->securityFilter($queryBuilder);
    }
}
