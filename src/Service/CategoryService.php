<?php

namespace ControleOnline\Service;

use Doctrine\ORM\QueryBuilder;

class CategoryService
{
    public function __construct(
        private PeopleService $peopleService,
    ) {
    }

    public function securityFilter(
        QueryBuilder $queryBuilder,
        $resourceClass = null,
        $applyTo = null,
        $rootAlias = null
    ): void {
        $rootAlias ??= $queryBuilder->getRootAliases()[0] ?? null;
        if (!$rootAlias) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $this->peopleService->checkCompany(
            'company',
            $queryBuilder,
            $resourceClass,
            $applyTo,
            $rootAlias
        );
    }
}
