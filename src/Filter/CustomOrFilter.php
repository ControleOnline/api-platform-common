<?php

namespace ControleOnline\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
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
        $joinedRelations = [];

        foreach ($this->properties as $configuredProperty => $propVal) {
            $relation = explode('.', $configuredProperty, 2);

            if (count($relation) > 1) {
                [$relationName, $relationField] = $relation;

                if (!array_key_exists($relationName, $joinedRelations)) {
                    $joinedRelations[$relationName] = $queryNameGenerator->generateJoinAlias($relationName);
                    $queryBuilder->leftJoin(
                        sprintf('%s.%s', $rootAlias, $relationName),
                        $joinedRelations[$relationName]
                    );
                }

                $orWhere->add(
                    $queryBuilder->expr()->like(
                        sprintf('%s.%s', $joinedRelations[$relationName], $relationField),
                        ':search'
                    )
                );

                continue;
            }

            $orWhere->add(
                $queryBuilder->expr()->like(
                    sprintf('%s.%s', $rootAlias, $configuredProperty),
                    ':search'
                )
            );
        }

        if (count($joinedRelations) > 0) {
            $queryBuilder->distinct();
        }

        if ($orWhere->count() === 0) {
            return;
        }

        $queryBuilder->andWhere($orWhere);
        $queryBuilder->setParameter('search', '%' . $value . '%');
    }
}
