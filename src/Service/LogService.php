<?php

namespace ControleOnline\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class LogService
{
    private const ENTITY_CLASS_PATTERN = '/^ControleOnline\\\\Entity\\\\[A-Za-z0-9_\\\\]+$/';

    public function __construct(
        private EntityManagerInterface $manager,
        private ContainerInterface $container,
        private RequestStack $requestStack,
    ) {}

    public function securityFilter(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $logType = strtolower(trim((string) $request?->query->get('type', '')));
        $targetClass = trim((string) $request?->query->get('class', ''));
        $targetRow = (int) preg_replace('/\D+/', '', (string) $request?->query->get('row', ''));
        $normalizedTargetClass = $this->normalizeEntityClassName($targetClass);

        if ($normalizedTargetClass && $targetRow > 0) {
            $this->applySingleEntityScope(
                $queryBuilder,
                $rootAlias,
                $normalizedTargetClass,
                $targetRow
            );

            return;
        }

        if ($logType === 'entity') {
            $queryBuilder->andWhere(
                $this->buildEntityCollectionAccessExpression($queryBuilder, $rootAlias)
            );

            return;
        }

        if ($logType !== '' && $logType !== 'all') {
            $queryBuilder->andWhere(sprintf('%s.type = :log_type', $rootAlias));
            $queryBuilder->setParameter('log_type', $logType);

            return;
        }

        // A timeline global mistura tipos livres com logs de entidade autorizados.
        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                sprintf('%s.type <> :log_entity_type', $rootAlias),
                $this->buildEntityCollectionAccessExpression($queryBuilder, $rootAlias)
            )
        );
        $queryBuilder->setParameter('log_entity_type', 'entity');
    }

    private function applySingleEntityScope(
        QueryBuilder $queryBuilder,
        ?string $rootAlias,
        string $targetClass,
        int $targetRow
    ): void {
        $queryBuilder->andWhere(sprintf('%s.type = :log_type', $rootAlias));
        $queryBuilder->andWhere(sprintf('%s.class = :log_target_class', $rootAlias));
        $queryBuilder->andWhere(sprintf('%s.row = :log_target_row', $rootAlias));
        $queryBuilder->setParameter('log_type', 'entity');
        $queryBuilder->setParameter('log_target_class', $targetClass);
        $queryBuilder->setParameter('log_target_row', $targetRow);

        $service = $this->resolveEntitySecurityService($targetClass);
        if ($service === null) {
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

    private function buildEntityCollectionAccessExpression(
        QueryBuilder $queryBuilder,
        ?string $rootAlias
    ): string {
        $entityScope = $queryBuilder->expr()->orX();
        $unrestrictedClasses = [];

        foreach ($this->getDistinctEntityLogClasses() as $index => $className) {
            $securityService = $this->resolveEntitySecurityService($className);

            if ($securityService === null) {
                $unrestrictedClasses[] = $className;
                continue;
            }

            $classParameterName = sprintf('log_entity_class_%d', $index + 1);
            $subAlias = sprintf('log_security_entity_%d', $index + 1);
            $subQueryBuilder = $this->manager->createQueryBuilder()
                ->select(sprintf('%s.id', $subAlias))
                ->from($className, $subAlias);

            $securityService->securityFilter(
                $subQueryBuilder,
                $className,
                'collection',
                $subAlias
            );

            foreach ($subQueryBuilder->getParameters() as $parameter) {
                $queryBuilder->setParameter(
                    $parameter->getName(),
                    $parameter->getValue(),
                    $parameter->getType()
                );
            }

            $queryBuilder->setParameter($classParameterName, $className);
            $entityScope->add(
                $queryBuilder->expr()->andX(
                    sprintf('%s.class = :%s', $rootAlias, $classParameterName),
                    $queryBuilder->expr()->in(
                        sprintf('%s.row', $rootAlias),
                        $subQueryBuilder->getDQL()
                    )
                )
            );
        }

        if ($unrestrictedClasses) {
            $queryBuilder->setParameter(
                'log_unrestricted_entity_classes',
                array_values(array_unique($unrestrictedClasses))
            );
            $entityScope->add(
                $queryBuilder->expr()->in(
                    sprintf('%s.class', $rootAlias),
                    ':log_unrestricted_entity_classes'
                )
            );
        }

        if (!$entityScope->count()) {
            return '1 = 0';
        }

        return $queryBuilder->expr()->andX(
            sprintf('%s.type = :log_entity_type', $rootAlias),
            $entityScope
        );
    }

    private function getDistinctEntityLogClasses(): array
    {
        $classes = $this->manager->getConnection()->fetchFirstColumn(
            "SELECT DISTINCT class FROM log WHERE type = 'entity' AND class IS NOT NULL AND class <> ''"
        );

        return array_values(
            array_filter(
                array_unique(array_map('strval', $classes)),
                fn(string $className): bool => $this->normalizeEntityClassName($className) !== null
            )
        );
    }

    private function resolveEntitySecurityService(string $className): ?object
    {
        $serviceName = str_replace('Entity', 'Service', $className) . 'Service';
        if (!$this->container->has($serviceName)) {
            return null;
        }

        $service = $this->container->get($serviceName);
        if (!method_exists($service, 'securityFilter')) {
            return null;
        }

        return $service;
    }

    private function normalizeEntityClassName(string $className): ?string
    {
        $normalized = trim($className);
        if (
            $normalized === ''
            || !class_exists($normalized)
            || !preg_match(self::ENTITY_CLASS_PATTERN, $normalized)
        ) {
            return null;
        }

        return $normalized;
    }
}
