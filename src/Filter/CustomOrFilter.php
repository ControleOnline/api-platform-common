<?php

namespace ControleOnline\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class CustomOrFilter extends AbstractFilter
{
    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => null,
                'type' => 'string',
                'required' => false,
                'openapi' => [
                    'description' => 'Search across multiple fields',
                ],
            ]
        ];
    }

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($property !== 'search' || $value === null || $value === '') {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $orWhere = $queryBuilder->expr()->orX();
        $searchParameter = sprintf(':%s', $queryNameGenerator->generateParameterName('search'));
        $joinedRelations = false;

        foreach ($this->properties ?? [] as $configuredProperty => $propertyConfig) {
            $resolvedProperty = $this->resolveConfiguredPropertyName(
                $configuredProperty,
                $propertyConfig,
            );

            if (
                $resolvedProperty === ''
                || !$this->isPropertyMapped($resolvedProperty, $resourceClass)
            ) {
                continue;
            }

            $alias = $rootAlias;
            $field = $resolvedProperty;
            $associations = [];

            if ($this->isPropertyNested($resolvedProperty, $resourceClass)) {
                [$alias, $field, $associations] = $this->addJoinsForNestedProperty(
                    $resolvedProperty,
                    $rootAlias,
                    $queryBuilder,
                    $queryNameGenerator,
                    $resourceClass,
                    Join::LEFT_JOIN,
                );
                $joinedRelations = true;
            }

            $metadata = $this->getNestedMetadata($resourceClass, $associations);
            if (!$metadata->hasField($field)) {
                continue;
            }

            $orWhere->add(
                $queryBuilder->expr()->like(
                    sprintf('%s.%s', $alias, $field),
                    $searchParameter
                ),
            );
        }

        if ($orWhere->count() === 0) {
            return;
        }

        $queryBuilder->andWhere($orWhere);
        $queryBuilder->setParameter(
            ltrim($searchParameter, ':'),
            '%' . $value . '%',
        );
    }

    private function resolveConfiguredPropertyName(
        mixed $configuredProperty,
        mixed $propertyConfig,
    ): string {
        if (is_string($configuredProperty) && $configuredProperty !== '') {
            return $configuredProperty;
        }

        return is_string($propertyConfig) ? $propertyConfig : '';
    }
}
