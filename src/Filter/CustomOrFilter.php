<?php

namespace ControleOnline\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
//use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
//use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
//use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;

class CustomOrFilter extends AbstractContextAwareFilter
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

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if ($property !== 'search') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $andWhere = '';

        foreach ($this->properties as $property => $propVal) {
            $andWhere .= sprintf('%s.%s LIKE :search', $alias, $property);

            next($this->properties);
            $nextKey = key($this->properties);

            if ($nextKey !== null) {
                $andWhere .= ' OR ';
            }
        }

        $queryBuilder->andWhere($andWhere)
            ->setParameter('search', '%' . $value . '%');
    }
}
