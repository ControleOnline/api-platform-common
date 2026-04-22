<?php

namespace ControleOnline\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class LogService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ContainerInterface $container,
        private RequestStack $requestStack,
    ) {}

    public function securityFilter(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $logType = strtolower(trim((string) $request?->query->get('type', 'entity')));
        $targetClass = trim((string) $request?->query->get('class', ''));
        $targetRow = (int) preg_replace('/\D+/', '', (string) $request?->query->get('row', ''));

        if ($logType === 'generic') {
            $queryBuilder->andWhere(sprintf('%s.type = :log_type', $rootAlias));
            $queryBuilder->andWhere(sprintf('%s.class IS NULL', $rootAlias));
            $queryBuilder->andWhere(sprintf('%s.row IS NULL', $rootAlias));
            $queryBuilder->setParameter('log_type', 'generic');

            return;
        }

        if (
            $targetClass === ''
            || $targetRow <= 0
            || !class_exists($targetClass)
            || !preg_match('/^ControleOnline\\\\Entity\\\\[A-Za-z0-9_\\\\]+$/', $targetClass)
        ) {
            $this->denyAll($queryBuilder, $rootAlias);
            return;
        }

        $queryBuilder->andWhere(sprintf('%s.type = :log_type', $rootAlias));
        $queryBuilder->andWhere(sprintf('%s.class = :log_target_class', $rootAlias));
        $queryBuilder->andWhere(sprintf('%s.row = :log_target_row', $rootAlias));
        $queryBuilder->setParameter('log_type', 'entity');
        $queryBuilder->setParameter('log_target_class', $targetClass);
        $queryBuilder->setParameter('log_target_row', $targetRow);

        $serviceName = str_replace('Entity', 'Service', $targetClass) . 'Service';
        if (!$this->container->has($serviceName)) {
            return;
        }

        $service = $this->container->get($serviceName);
        if (!method_exists($service, 'securityFilter')) {
            return;
        }

        $subQueryBuilder = $this->manager->createQueryBuilder()
            ->select('log_security_entity.id')
            ->from($targetClass, 'log_security_entity')
            ->andWhere('log_security_entity.id = :log_target_row');

        $service->securityFilter(
            $subQueryBuilder,
            $targetClass,
            'collection',
            'log_security_entity'
        );

        foreach ($subQueryBuilder->getParameters() as $parameter) {
            $queryBuilder->setParameter(
                $parameter->getName(),
                $parameter->getValue(),
                $parameter->getType()
            );
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->in(
                sprintf('%s.row', $rootAlias),
                $subQueryBuilder->getDQL()
            )
        );
    }

    private function denyAll(QueryBuilder $queryBuilder, ?string $rootAlias): void
    {
        $queryBuilder->andWhere(sprintf('%s.id IS NULL', $rootAlias));
    }
}
