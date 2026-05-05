<?php

namespace ControleOnline\Service;

use ApiPlatform\Doctrine\Common\State\LinksHandlerLocatorTrait;
use ApiPlatform\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Doctrine\Orm\Extension\FilterEagerLoadingExtension;
use ApiPlatform\Doctrine\Orm\Extension\OrderExtension;
use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\State\LinksHandlerTrait;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Util\StateOptionsTrait;
use ControleOnline\Attribute\CollectionSummary;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Container\ContainerInterface;
use ReflectionClass;

class CollectionSummaryService
{
    use LinksHandlerLocatorTrait;
    use LinksHandlerTrait;
    use StateOptionsTrait;

    private iterable $collectionExtensions;
    private array $summaryFieldsCache = [];

    public function __construct(
        ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        ManagerRegistry $managerRegistry,
        iterable $collectionExtensions = [],
        ?ContainerInterface $handleLinksLocator = null,
        ?ContainerInterface $summaryResolverLocator = null,
        ?RequestStack $requestStack = null
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->handleLinksLocator = $handleLinksLocator;
        $this->managerRegistry = $managerRegistry;
        $this->collectionExtensions = $collectionExtensions;
        $this->summaryResolverLocator = $summaryResolverLocator;
        $this->requestStack = $requestStack;
    }

    public function buildSummary(Operation $operation, array $uriVariables = [], array $context = []): ?array
    {
        $resourceClass = $this->getStateOptionsClass($operation, $operation->getClass(), Options::class);
        if (!$resourceClass) {
            return null;
        }

        $summaryFields = $this->filterEnabledSummaryFields(
            $this->getSummaryFields($resourceClass),
            $operation,
            $context
        );
        if ([] === $summaryFields) {
            return null;
        }

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (!$manager) {
            return null;
        }

        $classMetadata = $manager->getClassMetadata($resourceClass);
        $identifierFields = $classMetadata->getIdentifierFieldNames();
        if (1 !== count($identifierFields)) {
            return null;
        }

        $repository = $manager->getRepository($resourceClass);
        if (!method_exists($repository, 'createQueryBuilder')) {
            return null;
        }

        $rootAlias = 'o';
        $identifier = $identifierFields[0];
        $queryBuilder = $repository->createQueryBuilder($rootAlias);
        $queryNameGenerator = new QueryNameGenerator();

        if ($handleLinks = $this->getLinksHandler($operation)) {
            $handleLinks($queryBuilder, $uriVariables, $queryNameGenerator, ['entityClass' => $resourceClass, 'operation' => $operation] + $context);
        } else {
            $this->handleLinks($queryBuilder, $uriVariables, $queryNameGenerator, $context, $resourceClass, $operation);
        }

        foreach ($this->collectionExtensions as $extension) {
            if ($this->shouldSkipExtension($extension)) {
                continue;
            }

            $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
        }

        $filteredIdsQueryBuilder = clone $queryBuilder;
        $filteredIdsQueryBuilder->resetDQLPart('select');
        $filteredIdsQueryBuilder->resetDQLPart('orderBy');
        $filteredIdsQueryBuilder->select(sprintf('DISTINCT %s.%s', $rootAlias, $identifier));

        $summaryAlias = 'summary_root';
        $summaryQueryBuilder = $repository->createQueryBuilder($summaryAlias);
        $summaryQueryBuilder->andWhere(
            $summaryQueryBuilder->expr()->in(
                sprintf('%s.%s', $summaryAlias, $identifier),
                $filteredIdsQueryBuilder->getDQL()
            )
        );

        foreach ($filteredIdsQueryBuilder->getParameters() as $parameter) {
            $this->copyParameter($summaryQueryBuilder, $parameter);
        }

        $aggregateFields = array_values(array_filter(
            $summaryFields,
            static fn(array $field) => null === ($field['resolver'] ?? null)
        ));
        $resolverFields = array_values(array_filter(
            $summaryFields,
            static fn(array $field) => null !== ($field['resolver'] ?? null)
        ));

        $summary = [];

        if ([] !== $aggregateFields) {
            $selects = [];

            foreach ($aggregateFields as $field) {
                foreach ($field['operations'] as $operationName) {
                    $selects[] = $this->buildSelectExpression(
                        $summaryAlias,
                        $field['property'],
                        $operationName,
                        $this->getSelectAlias($field['name'], $operationName)
                    );
                }
            }

            $summaryQueryBuilder->select(implode(', ', $selects));

            $result = $summaryQueryBuilder->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY) ?: [];
            $summary = $this->normalizeSummary($result, $aggregateFields);
        }

        foreach ($resolverFields as $field) {
            $summary[$field['name']] = $this->resolveCustomSummaryField(
                $field,
                $operation,
                $resourceClass,
                $filteredIdsQueryBuilder,
                $uriVariables,
                $context
            );
        }

        return [] !== $summary ? $summary : null;
    }

    private function getSummaryFields(string $resourceClass): array
    {
        if (isset($this->summaryFieldsCache[$resourceClass])) {
            return $this->summaryFieldsCache[$resourceClass];
        }

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (!$manager) {
            return $this->summaryFieldsCache[$resourceClass] = [];
        }

        $classMetadata = $manager->getClassMetadata($resourceClass);
        $reflectionClass = new ReflectionClass($resourceClass);
        $summaryFields = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $attributes = $property->getAttributes(CollectionSummary::class);
            if ([] === $attributes) {
                continue;
            }

            /** @var CollectionSummary $attribute */
            $attribute = $attributes[0]->newInstance();
            $resolver = $attribute->getResolver();

            if (null === $resolver && !$classMetadata->hasField($property->getName())) {
                continue;
            }

            $summaryFields[] = [
                'property' => $property->getName(),
                'name' => $attribute->getName() ?: $property->getName(),
                'operations' => $attribute->getOperations(),
                'doctrineType' => $classMetadata->hasField($property->getName())
                    ? (string) $classMetadata->getTypeOfField($property->getName())
                    : null,
                'parameter' => $attribute->getParameter(),
                'parameterValues' => $attribute->getParameterValues(),
                'groups' => $attribute->getGroups(),
                'resolver' => $resolver,
            ];
        }

        return $this->summaryFieldsCache[$resourceClass] = $summaryFields;
    }

    private function filterEnabledSummaryFields(array $summaryFields, Operation $operation, array $context): array
    {
        return array_values(array_filter(
            $summaryFields,
            fn(array $field) => $this->isSummaryFieldEnabled($field, $operation, $context)
        ));
    }

    private function isSummaryFieldEnabled(array $field, Operation $operation, array $context): bool
    {
        $parameter = $field['parameter'] ?? null;
        if ($parameter) {
            $parameterValues = $field['parameterValues'] ?? [];
            $requestValue = $this->getRequestParameterValue($parameter, $context);

            if (null === $requestValue) {
                return false;
            }

            if ([] !== $parameterValues) {
                $normalizedValues = is_array($requestValue) ? $requestValue : [$requestValue];
                $normalizedValues = array_map(static fn($value) => trim((string) $value), $normalizedValues);

                if ([] === array_intersect($parameterValues, $normalizedValues)) {
                    return false;
                }
            }
        }

        $requiredGroups = $field['groups'] ?? [];
        if ([] === $requiredGroups) {
            return true;
        }

        $contextGroups = $context['groups']
            ?? $operation->getNormalizationContext()['groups']
            ?? [];
        $contextGroups = is_array($contextGroups) ? $contextGroups : [$contextGroups];

        return [] !== array_intersect($requiredGroups, $contextGroups);
    }

    private function getRequestParameterValue(string $parameter, array $context): mixed
    {
        $filters = $context['filters'] ?? [];
        if (array_key_exists($parameter, $filters)) {
            return $filters[$parameter];
        }

        return $this->requestStack?->getCurrentRequest()?->query->all()[$parameter] ?? null;
    }

    private function shouldSkipExtension(object $extension): bool
    {
        return $extension instanceof PaginationExtension
            || $extension instanceof EagerLoadingExtension
            || $extension instanceof FilterEagerLoadingExtension
            || $extension instanceof OrderExtension;
    }

    private function buildSelectExpression(string $alias, string $property, string $operation, string $selectAlias): string
    {
        $field = sprintf('%s.%s', $alias, $property);

        return match ($operation) {
            'count' => sprintf('COUNT(%s) AS %s', $field, $selectAlias),
            default => sprintf('COALESCE(SUM(%s), 0) AS %s', $field, $selectAlias),
        };
    }

    private function normalizeSummary(array $result, array $summaryFields): array
    {
        $summary = [];

        foreach ($summaryFields as $field) {
            foreach ($field['operations'] as $operation) {
                $selectAlias = $this->getSelectAlias($field['name'], $operation);
                $summary[$operation][$field['name']] = $this->castAggregateValue(
                    $result[$selectAlias] ?? null,
                    $operation,
                    $field['doctrineType']
                );
            }
        }

        return $summary;
    }

    private function castAggregateValue(mixed $value, string $operation, string $doctrineType): mixed
    {
        if ('count' === $operation) {
            return (int) ($value ?? 0);
        }

        if (null === $value) {
            return 0;
        }

        return match ($doctrineType) {
            'integer', 'smallint' => (int) $value,
            'float', 'decimal' => (float) $value,
            default => is_numeric($value) ? (float) $value : $value,
        };
    }

    private function getSelectAlias(string $fieldName, string $operation): string
    {
        return sprintf('summary_%s_%s', preg_replace('/[^a-z0-9_]/i', '_', $fieldName), $operation);
    }

    private function resolveCustomSummaryField(
        array $field,
        Operation $operation,
        string $resourceClass,
        \Doctrine\ORM\QueryBuilder $filteredIdsQueryBuilder,
        array $uriVariables,
        array $context
    ): mixed {
        $resolver = $field['resolver'] ?? null;
        if (!$resolver || !$this->summaryResolverLocator?->has($resolver)) {
            return null;
        }

        $service = $this->summaryResolverLocator->get($resolver);
        if (!$service instanceof CollectionSummaryResolverInterface) {
            throw new \RuntimeException(sprintf(
                'Collection summary resolver "%s" must implement %s.',
                $resolver,
                CollectionSummaryResolverInterface::class
            ));
        }

        return $service->resolve($operation, $resourceClass, $field, $filteredIdsQueryBuilder, $uriVariables, $context);
    }

    private function copyParameter(\Doctrine\ORM\QueryBuilder $queryBuilder, \Doctrine\ORM\Query\Parameter $parameter): void
    {
        if ($parameter->typeWasSpecified()) {
            $queryBuilder->setParameter($parameter->getName(), $parameter->getValue(), $parameter->getType());

            return;
        }

        $queryBuilder->setParameter($parameter->getName(), $parameter->getValue());
    }
}
