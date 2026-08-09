<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\FileService;
use ControleOnline\Service\PdfService;
use ControleOnline\Service\PeopleService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class FileServiceSecurityFilterTest extends TestCase
{
    public function testSecurityFilterBlocksWhenUserHasNoCompanies(): void
    {
        $peopleService = $this->createMock(PeopleService::class);
        $peopleService->method('getMyCompanies')->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('getRootAliases')->willReturn(['o']);
        $qb->expects(self::once())->method('andWhere')->with('1 = 0')->willReturnSelf();

        $this->buildService($peopleService, new RequestStack())->securityFilter($qb, null, null, 'o');
    }

    public function testSecurityFilterScopesToUserCompanies(): void
    {
        $company = new People();
        $peopleService = $this->createMock(PeopleService::class);
        $peopleService->method('getMyCompanies')->willReturn([$company]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('getRootAliases')->willReturn(['o']);
        $qb->expects(self::once())
            ->method('andWhere')
            ->with('o.people IN(:fileSecurityCompanies)')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('fileSecurityCompanies', [$company])
            ->willReturnSelf();

        $this->buildService($peopleService, new RequestStack())->securityFilter($qb, null, null, 'o');
    }

    public function testSecurityFilterAppliesOptionalPeopleQueryParam(): void
    {
        $company = new People();
        $peopleService = $this->createMock(PeopleService::class);
        $peopleService->method('getMyCompanies')->willReturn([$company]);

        $request = new Request(['people' => '/people/42']);
        $stack = new RequestStack();
        $stack->push($request);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('getRootAliases')->willReturn(['o']);
        $qb->expects(self::exactly(2))->method('andWhere')->willReturnSelf();
        $qb->expects(self::exactly(2))->method('setParameter')->willReturnSelf();

        $this->buildService($peopleService, $stack)->securityFilter($qb, null, null, 'o');
    }

    private function buildService(PeopleService $peopleService, RequestStack $stack): FileService
    {
        return new FileService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(DomainService::class),
            $this->createMock(PdfService::class),
            $peopleService,
            $stack,
        );
    }
}
