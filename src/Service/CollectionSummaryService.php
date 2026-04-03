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
        ?ContainerInterface $handleLinksLocator = null
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->handleLinksLocator = $handleLinksLocator;
        $this->managerRegistry = $managerRegistry;
        $this->collectionExtensions = $collectionExtensions;
    }

    public function buildSummary(Operation $operation, array $uriVariables = [], array $context = []): ?array
    {
        $resourceClass = $this->getStateOptionsClass($operation, $operation->getClass(), Options::class);
        if (!$resourceClass) {
            return null;
        }

        $summaryFields = $this->getSummaryFields($resourceClass);
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

        $selects = [sprintf('COUNT(%s.%s) AS totalItems', $summaryAlias, $identifier)];

        foreach ($summaryFields as $field) {
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

        return $this->normalizeSummary($result, $summaryFields);
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
            if ([] === $attributes || !$classMetadata->hasField($property->getName())) {
                continue;
            }

            /** @var CollectionSummary $attribute */
            $attribute = $attributes[0]->newInstance();
            $summaryFields[] = [
                'property' => $property->getName(),
                'name' => $attribute->getName() ?: $property->getName(),
                'operations' => $attribute->getOperations(),
                'doctrineType' => (string) $classMetadata->getTypeOfField($property->getName()),
            ];
        }

        return $this->summaryFieldsCache[$resourceClass] = $summaryFields;
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
        $summary = [
            'totalItems' => (int) ($result['totalItems'] ?? 0),
        ];

        foreach ($summaryFields as $field) {
            $fieldSummary = [];

            foreach ($field['operations'] as $operation) {
                $selectAlias = $this->getSelectAlias($field['name'], $operation);
                $fieldSummary[$operation] = $this->castAggregateValue(
                    $result[$selectAlias] ?? null,
                    $operation,
                    $field['doctrineType']
                );
            }

            $summary[$field['name']] = $fieldSummary;
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

    private function copyParameter(\Doctrine\ORM\QueryBuilder $queryBuilder, \Doctrine\ORM\Query\Parameter $parameter): void
    {
        if ($parameter->typeWasSpecified()) {
            $queryBuilder->setParameter($parameter->getName(), $parameter->getValue(), $parameter->getType());

            return;
        }

        $queryBuilder->setParameter($parameter->getName(), $parameter->getValue());
    }
}
