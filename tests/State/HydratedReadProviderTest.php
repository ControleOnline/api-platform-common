<?php

namespace ControleOnline\Common\Tests\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ControleOnline\Entity\Order;
use ControleOnline\Service\CollectionSummaryService;
use ControleOnline\Service\OrderService;
use ControleOnline\State\CollectionSummaryResult;
use ControleOnline\State\HydratedReadProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class HydratedReadProviderTest extends TestCase
{
    public function testCollectionReplicatesFiltersAndWrapsSummary(): void
    {
        $request = Request::create('/orders?page=2&itemsPerPage=25', 'GET');
        $request->attributes->set('_api_filters', ['status' => 'confirmed']);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('DISTINCT resource')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setFirstResult')
            ->with(25)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setMaxResults')
            ->with(25)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($this->createStub(Query::class));

        $collectionExtension = $this->createMock(QueryCollectionExtensionInterface::class);
        $collectionExtension
            ->expects(self::once())
            ->method('applyToCollection')
            ->with(
                $queryBuilder,
                self::isInstanceOf(QueryNameGeneratorInterface::class),
                Order::class,
                self::isInstanceOf(GetCollection::class),
                self::callback(static function (array $context) use ($request): bool {
                    return ['status' => 'confirmed'] === ($context['filters'] ?? null)
                        && $request === ($context['request'] ?? null);
                })
            );

        $summaryService = $this->createMock(CollectionSummaryService::class);
        $summaryService
            ->expects(self::once())
            ->method('buildSummary')
            ->with(
                self::isInstanceOf(GetCollection::class),
                [],
                self::callback(static function (array $context) use ($request): bool {
                    return ['status' => 'confirmed'] === ($context['filters'] ?? null)
                        && $request === ($context['request'] ?? null);
                })
            )
            ->willReturn(['total' => 7]);

        $provider = $this->createProvider(
            resourceClass: Order::class,
            requestStack: $this->createRequestStack($request),
            orderService: $this->createStub(OrderService::class),
            collectionSummaryService: $summaryService,
            extensions: [$collectionExtension],
            queryBuilder: $queryBuilder,
        );

        $result = $provider->provide(new GetCollection(class: Order::class), [], []);

        self::assertInstanceOf(CollectionSummaryResult::class, $result);
        self::assertSame(['total' => 7], $result->getSummary());
        self::assertInstanceOf(Paginator::class, $result->getCollection());
    }

    public function testItemReplicatesFiltersAndNormalizesOrderLinks(): void
    {
        $request = Request::create('/orders/71760?page=2&itemsPerPage=25', 'GET');
        $request->attributes->set('_api_filters', ['status' => 'confirmed']);

        $order = new Order();

        $query = $this->createMock(Query::class);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($order);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('DISTINCT resource')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('resource.id = :identifier')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('identifier', 71760)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $itemExtension = $this->createMock(QueryItemExtensionInterface::class);
        $itemExtension
            ->expects(self::once())
            ->method('applyToItem')
            ->with(
                $queryBuilder,
                self::isInstanceOf(QueryNameGeneratorInterface::class),
                Order::class,
                ['id' => 71760],
                self::isInstanceOf(Get::class),
                self::callback(static function (array $context) use ($request): bool {
                    return ['status' => 'confirmed'] === ($context['filters'] ?? null)
                        && $request === ($context['request'] ?? null);
                })
            );

        $orderService = $this->createMock(OrderService::class);
        $orderService
            ->expects(self::once())
            ->method('normalizeOrderProductGroupLinks')
            ->with($order)
            ->willReturn(true);

        $provider = $this->createProvider(
            resourceClass: Order::class,
            requestStack: $this->createRequestStack($request),
            orderService: $orderService,
            collectionSummaryService: $this->createStub(CollectionSummaryService::class),
            extensions: [$itemExtension],
            queryBuilder: $queryBuilder,
        );

        $result = $provider->provide(new Get(class: Order::class), ['id' => 71760], []);

        self::assertSame($order, $result);
    }

    /**
     * @param array<int, object> $extensions
     */
    private function createProvider(
        string $resourceClass,
        RequestStack $requestStack,
        OrderService $orderService,
        CollectionSummaryService $collectionSummaryService,
        array $extensions,
        QueryBuilder $queryBuilder,
    ): HydratedReadProvider {
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->with('resource')
            ->willReturn($queryBuilder);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata
            ->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($resourceClass)
            ->willReturn($classMetadata);
        $manager
            ->expects(self::once())
            ->method('getRepository')
            ->with($resourceClass)
            ->willReturn($repository);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($resourceClass)
            ->willReturn($manager);

        return new HydratedReadProvider(
            $managerRegistry,
            $collectionSummaryService,
            $requestStack,
            $orderService,
            $extensions
        );
    }

    private function createRequestStack(Request $request): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }
}
