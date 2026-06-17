<?php

namespace ControleOnline\Tests\Service;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ControleOnline\Attribute\CollectionSummary;
use ControleOnline\Service\CollectionSummaryResolverInterface;
use ControleOnline\Service\CollectionSummaryService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class CollectionSummaryServiceTest extends TestCase
{
    public function testSummaryFiltersUseCurrentRequestQueryValues(): void
    {
        [$firstSummary, $firstExtension] = $this->buildSummaryForRange(
            '2026-05-17 00:00:00',
            '2026-06-16 23:59:59'
        );
        [$secondSummary, $secondExtension] = $this->buildSummaryForRange(
            '2026-06-15 00:00:00',
            '2026-06-15 23:59:59'
        );

        self::assertSame('2026-05-17 00:00:00', $firstExtension->lastFilters['dueDate']['after'] ?? null);
        self::assertSame('2026-06-16 23:59:59', $firstExtension->lastFilters['dueDate']['before'] ?? null);
        self::assertSame('2026-06-15 00:00:00', $secondExtension->lastFilters['dueDate']['after'] ?? null);
        self::assertSame('2026-06-15 23:59:59', $secondExtension->lastFilters['dueDate']['before'] ?? null);

        self::assertSame('2026-05-17 00:00:00', $firstSummary['filteredDql']['after']);
        self::assertSame('2026-06-16 23:59:59', $firstSummary['filteredDql']['before']);
        self::assertSame('2026-06-15 00:00:00', $secondSummary['filteredDql']['after']);
        self::assertSame('2026-06-15 23:59:59', $secondSummary['filteredDql']['before']);
        self::assertNotSame($firstSummary['filteredDql'], $secondSummary['filteredDql']);
    }

    /**
     * @return array{0: array<string, mixed>, 1: SummaryDateRangeExtension}
     */
    private function buildSummaryForRange(string $after, string $before): array
    {
        $extension = new SummaryDateRangeExtension();
        $resolver = new SummaryQueryParametersResolver();
        $requestStack = new RequestStack();
        $request = Request::create('/invoices', 'GET', [
            'dueDate' => [
                'after' => $after,
                'before' => $before,
            ],
        ]);
        $requestStack->push($request);

        $service = $this->createService($extension, $resolver, $requestStack);
        $operation = new GetCollection(
            uriTemplate: '/invoices',
            class: SummaryCollectionFixture::class,
            stateOptions: new Options(
                SummaryCollectionFixture::class,
                static function (
                    QueryBuilder $queryBuilder,
                    array $uriVariables,
                    QueryNameGeneratorInterface $queryNameGenerator,
                    array $context
                ): void {
                    // No-op: the test only cares about filter propagation.
                }
            )
        );

        $summaryFields = $this->invokePrivate($service, 'getSummaryFields', [SummaryCollectionFixture::class]);
        self::assertNotEmpty($summaryFields);

        $summary = $service->buildSummary($operation, [], []);
        self::assertIsArray($summary);
        self::assertArrayHasKey('filteredDql', $summary);

        return [$summary, $extension];
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private function invokePrivate(object $object, string $method, array $arguments = []): mixed
    {
        $reflectionMethod = new \ReflectionMethod($object, $method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($object, $arguments);
    }

    private function createService(
        SummaryDateRangeExtension $extension,
        SummaryQueryParametersResolver $resolver,
        RequestStack $requestStack,
    ): CollectionSummaryService {
        $queryBuilderEntityManager = $this->createStub(EntityManagerInterface::class);
        $queryBuilderEntityManager
            ->method('getExpressionBuilder')
            ->willReturn(new Expr());
        $queryBuilderEntityManager
            ->method('createQueryBuilder')
            ->willReturnCallback(static function () use ($queryBuilderEntityManager): QueryBuilder {
                return new QueryBuilder($queryBuilderEntityManager);
            });

        $repository = new EntityRepository(
            $queryBuilderEntityManager,
            new ClassMetadata(SummaryCollectionFixture::class)
        );

        $classMetadata = $this->createStub(ClassMetadata::class);
        $classMetadata
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $classMetadata
            ->method('hasField')
            ->willReturn(false);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager
            ->method('getClassMetadata')
            ->willReturn($classMetadata);
        $manager
            ->method('getRepository')
            ->willReturn($repository);
        $manager
            ->method('getExpressionBuilder')
            ->willReturn(new Expr());

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry
            ->method('getManagerForClass')
            ->willReturn($manager);

        $resolverLocator = $this->createStub(ContainerInterface::class);
        $resolverLocator
            ->method('has')
            ->willReturn(true);
        $resolverLocator
            ->method('get')
            ->willReturn($resolver);

        return new CollectionSummaryService(
            $this->createStub(\ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface::class),
            $managerRegistry,
            [$extension],
            null,
            $resolverLocator,
            $requestStack
        );
    }
}

final class SummaryCollectionFixture
{
    #[CollectionSummary(name: 'filteredDql', resolver: SummaryQueryParametersResolver::class)]
    private mixed $summary = null;
}

final class SummaryDateRangeExtension implements QueryCollectionExtensionInterface
{
    public array $lastFilters = [];

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (SummaryCollectionFixture::class !== $resourceClass) {
            return;
        }

        $this->lastFilters = $context['filters'] ?? [];

        $dueDate = $this->lastFilters['dueDate'] ?? [];
        $after = $dueDate['after'] ?? null;
        $before = $dueDate['before'] ?? null;
        $rootAlias = $queryBuilder->getRootAliases()[0] ?? 'o';

        if (null !== $after) {
            $queryBuilder->andWhere(sprintf('%s.dueDate >= :summary_due_after', $rootAlias));
            $queryBuilder->setParameter('summary_due_after', $after);
        }

        if (null !== $before) {
            $queryBuilder->andWhere(sprintf('%s.dueDate <= :summary_due_before', $rootAlias));
            $queryBuilder->setParameter('summary_due_before', $before);
        }
    }
}

final class SummaryQueryParametersResolver implements CollectionSummaryResolverInterface
{
    public function resolve(
        Operation $operation,
        string $resourceClass,
        array $summaryField,
        QueryBuilder $filteredIdsQueryBuilder,
        array $uriVariables = [],
        array $context = []
    ): mixed {
        return [
            'after' => $filteredIdsQueryBuilder->getParameter('summary_due_after')?->getValue(),
            'before' => $filteredIdsQueryBuilder->getParameter('summary_due_before')?->getValue(),
            'dql' => $filteredIdsQueryBuilder->getDQL(),
        ];
    }
}
