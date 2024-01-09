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

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if ($property !== 'search') {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        foreach (array_keys($this->getProperties()) as $prop) { // we use array_keys() because getProperties() returns a map of property => strategy
            if (!$this->isPropertyEnabled($prop, $resourceClass) || !$this->isPropertyMapped($prop, $resourceClass)) {
                return;
            }
            $parameterName = $queryNameGenerator->generateParameterName($prop);
            $queryBuilder
                ->orWhere(sprintf('%s.%s LIKE :%s', $rootAlias, $prop, $parameterName))
                ->setParameter($parameterName, "%" . $value . "%");
        }
    }
}
