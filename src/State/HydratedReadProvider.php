<?php

namespace ControleOnline\State;

use ApiPlatform\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Doctrine\Orm\Extension\FilterEagerLoadingExtension;
use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\RequestParser;
use ControleOnline\Doctrine\Extension\CollectionDoctrineQueryDebugExtension;
use ControleOnline\Entity\Order;
use ControleOnline\Service\CollectionSummaryService;
use ControleOnline\Service\OrderService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class HydratedReadProvider implements ProviderInterface
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly CollectionSummaryService $collectionSummaryService,
        private readonly RequestStack $requestStack,
        private readonly OrderService $orderService,
        private readonly CollectionDoctrineQueryDebugExtension $queryDebugExtension,
        private readonly iterable $collectionExtensions = [],
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $resourceClass = $operation->getClass();
        if (!is_string($resourceClass) || '' === $resourceClass) {
            return null;
        }

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (!$manager) {
            return null;
        }

        $metadata = $manager->getClassMetadata($resourceClass);
        $identifierFields = $metadata->getIdentifierFieldNames();
        if (1 !== count($identifierFields)) {
            return null;
        }

        $identifierField = $identifierFields[0];
        $repository = $manager->getRepository($resourceClass);
        if (!method_exists($repository, 'createQueryBuilder')) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();
        $context = $this->hydrateFiltersFromRequest($context, $request);
        $context['operation'] = $operation;
        $context['request'] = $request;

        $queryNameGenerator = new QueryNameGenerator();
        $rootAlias = 'resource';
        $queryBuilder = $repository->createQueryBuilder($rootAlias);

        if ($operation instanceof CollectionOperationInterface) {
            $this->applyExtensions(
                $queryBuilder,
                $queryNameGenerator,
                $resourceClass,
                $operation,
                $uriVariables,
                $context,
                true
            );

            $page = max(1, (int) ($request?->query->get('page', 1) ?: 1));
            $itemsPerPage = max(1, (int) ($request?->query->get('itemsPerPage', 50) ?: 50));

            $queryBuilder
                ->select(sprintf('DISTINCT %s', $rootAlias))
                ->setFirstResult(($page - 1) * $itemsPerPage)
                ->setMaxResults($itemsPerPage);

            $this->queryDebugExtension->capture($queryBuilder);

            $paginator = new Paginator($queryBuilder->getQuery(), false);
            $summary = $this->collectionSummaryService->buildSummary($operation, $uriVariables, $context);

            if (is_array($summary) && [] !== $summary) {
                return new CollectionSummaryResult($paginator, $summary);
            }

            return $paginator;
        }

        $identifierValue = $this->resolveIdentifierValue(
            $uriVariables[$identifierField] ?? $uriVariables['id'] ?? null
        );
        if (null === $identifierValue) {
            return null;
        }

        $this->applyExtensions(
            $queryBuilder,
            $queryNameGenerator,
            $resourceClass,
            $operation,
            $uriVariables,
            $context,
            false
        );

        $queryBuilder
            ->select(sprintf('DISTINCT %s', $rootAlias))
            ->andWhere(sprintf('%s.%s = :identifier', $rootAlias, $identifierField))
            ->setParameter('identifier', $identifierValue)
            ->setMaxResults(1);

        $item = $queryBuilder->getQuery()->getOneOrNullResult();

        if ($item instanceof Order) {
            // Mantem o mesmo preprocessamento usado pelo controller atual do detalhe de pedido.
            $this->orderService->normalizeOrderProductGroupLinks($item);
        }

        return $item;
    }

    private function applyExtensions(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation,
        array $uriVariables,
        array $context,
        bool $collection
    ): void {
        foreach ($this->collectionExtensions as $extension) {
            if ($this->shouldSkipExtension($extension)) {
                continue;
            }

            if ($collection && $extension instanceof QueryCollectionExtensionInterface) {
                $extension->applyToCollection(
                    $queryBuilder,
                    $queryNameGenerator,
                    $resourceClass,
                    $operation,
                    $context
                );

                continue;
            }

            if (!$collection && $extension instanceof QueryItemExtensionInterface) {
                $extension->applyToItem(
                    $queryBuilder,
                    $queryNameGenerator,
                    $resourceClass,
                    $uriVariables,
                    $operation,
                    $context
                );
            }
        }
    }

    private function hydrateFiltersFromRequest(array $context, ?Request $request): array
    {
        if (!$request) {
            return $context;
        }

        $requestFilters = $request->attributes->get('_api_filters');
        if (!is_array($requestFilters)) {
            $queryString = RequestParser::getQueryString($request);
            $requestFilters = $queryString ? RequestParser::parseRequestParams($queryString) : [];
        }

        if ([] === $requestFilters) {
            return $context;
        }

        if (!isset($context['filters']) || !is_array($context['filters']) || [] === $context['filters']) {
            $context['filters'] = $requestFilters;

            return $context;
        }

        $context['filters'] = array_replace_recursive($requestFilters, $context['filters']);

        return $context;
    }

    private function resolveIdentifierValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_array($value)) {
            foreach (['id', '@id', 'value'] as $key) {
                if (!array_key_exists($key, $value)) {
                    continue;
                }

                $resolvedValue = $this->resolveIdentifierValue($value[$key]);
                if (null !== $resolvedValue) {
                    return $resolvedValue;
                }
            }

            return null;
        }

        if (is_string($value)) {
            $digits = preg_replace('/\D+/', '', $value);
            if ($digits !== '') {
                return (int) $digits;
            }
        }

        return null;
    }

    private function shouldSkipExtension(object $extension): bool
    {
        return $extension instanceof PaginationExtension
            || $extension instanceof EagerLoadingExtension
            || $extension instanceof FilterEagerLoadingExtension;
    }
}
