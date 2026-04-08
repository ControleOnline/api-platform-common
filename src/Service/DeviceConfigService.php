<?php

namespace ControleOnline\Service;

use Doctrine\ORM\QueryBuilder;

class DeviceConfigService
{
    public function __construct(
        private PeopleService $peopleService
    ) {}

    public function securityFilter(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
    {
        $this->peopleService->checkCompany('people', $queryBuilder, $resourceClass, $applyTo, $rootAlias);
    }
}
