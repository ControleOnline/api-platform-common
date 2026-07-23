<?php

namespace ControleOnline\Common\Tests\Doctrine\Extension;

use ControleOnline\Doctrine\Extension\CollectionDoctrineQueryDebugExtension;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class CollectionDoctrineQueryDebugExtensionTest extends TestCase
{
    public function testCapturesSqlDebugInDevelopmentEnvironment(): void
    {
        $request = Request::create(
            '/orders?provider=%2Fpeople%2F2&itemsPerPage=50',
            'GET'
        );

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $query = $this->createMock(Query::class);
        $query
            ->expects(self::once())
            ->method('getSQL')
            ->willReturn('SELECT o0_.id AS id_0 FROM orders o0_ WHERE o0_.provider_id = ?');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $queryBuilder
            ->expects(self::once())
            ->method('getParameters')
            ->willThrowException(new \RuntimeException('parameter interpolation failed'));

        $extension = new CollectionDoctrineQueryDebugExtension($requestStack, 'dev');
        $extension->capture($queryBuilder);

        $debug = $request->attributes->get(CollectionDoctrineQueryDebugExtension::REQUEST_ATTRIBUTE);

        self::assertSame([
            'filledQuery' => 'SELECT o0_.id AS id_0 FROM orders o0_ WHERE o0_.provider_id = ?',
            'parameters' => [
                'provider' => '/people/2',
                'itemsPerPage' => '50',
            ],
            'query' => 'SELECT o0_.id AS id_0 FROM orders o0_ WHERE o0_.provider_id = ?',
        ], $debug);
    }

    public function testDoesNotCaptureSqlDebugOutsideDevelopmentContexts(): void
    {
        $request = Request::create('/orders', 'GET');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(self::never())
            ->method('getQuery');

        $extension = new CollectionDoctrineQueryDebugExtension($requestStack, 'prod');
        $extension->capture($queryBuilder);

        self::assertFalse($request->attributes->has(CollectionDoctrineQueryDebugExtension::REQUEST_ATTRIBUTE));
    }
}
