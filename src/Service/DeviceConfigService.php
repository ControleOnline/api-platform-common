<?php

namespace ControleOnline\Service;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

class DeviceConfigService
{
    private $request;

    public function __construct(
        private PeopleService $peopleService,
        RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function securityFilter(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
    {
        $companies = $this->peopleService->getMyCompanies();

        if (empty($companies)) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        $queryBuilder->andWhere(sprintf('%s.people IN(:companies)', $rootAlias));
        $queryBuilder->setParameter('companies', $companies);

        if ($people = $this->request?->query->get('people', null)) {
            $queryBuilder->andWhere(sprintf('%s.people IN(:people)', $rootAlias));
            $queryBuilder->setParameter('people', preg_replace("/[^0-9]/", "", $people));
        }
    }
}
