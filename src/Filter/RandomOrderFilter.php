<?php

namespace ControleOnline\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

final class RandomOrderFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        // Verifica se o parâmetro "random" foi passado na URL
        if ($property !== 'random' || $value !== 'true') {
            return;
        }

        // Adiciona a ordenação aleatória à query
        $queryBuilder->orderBy('RAND()');
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'random' => [
                'property' => null,
                'type' => 'string',
                'required' => false,
                'description' => 'Pass "true" to sort results randomly.',
                'openapi' => [
                    'example' => 'true',
                ],
            ],
        ];
    }
}
