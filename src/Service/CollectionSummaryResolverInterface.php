<?php

namespace ControleOnline\Service;

use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

interface CollectionSummaryResolverInterface
{
    public function resolve(
        Operation $operation,
        string $resourceClass,
        array $summaryField,
        QueryBuilder $filteredIdsQueryBuilder,
        array $uriVariables = [],
        array $context = []
    ): mixed;
}
