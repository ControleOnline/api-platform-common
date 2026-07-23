<?php

namespace ControleOnline\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

final class CollectionDoctrineQueryDebugExtension implements QueryCollectionExtensionInterface
{
    public const REQUEST_ATTRIBUTE = '_doctrine_query_debug';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly string $environment
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if ('dev' !== $this->environment) {
            return;
        }

        $this->capture($queryBuilder);
    }

    public function capture(QueryBuilder $queryBuilder): void
    {
        if ('dev' !== $this->environment) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $debugQuery = $this->buildCompleteQuery($queryBuilder);
        if (null === $debugQuery) {
            return;
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, [
            'filledQuery' => $debugQuery['filledQuery'],
            'parameters' => $request->query->all(),
            'query' => $debugQuery['query'],
        ]);
    }

    private function buildCompleteQuery(QueryBuilder $queryBuilder): ?array
    {
        try {
            $query = $queryBuilder->getQuery();
            $sql = $query->getSQL();

            return [
                'filledQuery' => $this->interpolateParameters($sql, $queryBuilder),
                'query' => $sql,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function interpolateParameters(string $sql, QueryBuilder $queryBuilder): string
    {
        foreach ($queryBuilder->getParameters() as $parameter) {
            if (!$parameter instanceof Parameter) {
                continue;
            }

            $quotedValue = $this->quoteValue($queryBuilder, $parameter->getValue());
            $name = (string) $parameter->getName();

            if (str_contains($sql, '?')) {
                $sql = preg_replace('/\?/', $quotedValue, $sql, 1) ?? $sql;

                continue;
            }

            $sql = preg_replace(
                sprintf('/:%s\b/', preg_quote($name, '/')),
                $quotedValue,
                $sql
            ) ?? $sql;
        }

        return $sql;
    }

    private function quoteValue(QueryBuilder $queryBuilder, mixed $value): string
    {
        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            return sprintf(
                '(%s)',
                implode(', ', array_map(fn(mixed $item) => $this->quoteValue($queryBuilder, $item), $value))
            );
        }

        if ($value instanceof \DateTimeInterface) {
            return $this->quoteString($queryBuilder, $value->format('Y-m-d H:i:s'));
        }

        if ($value instanceof \UnitEnum) {
            $value = $value instanceof \BackedEnum ? $value->value : $value->name;
        }

        if (null === $value) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $this->quoteString($queryBuilder, (string) $value);
    }

    private function quoteString(QueryBuilder $queryBuilder, string $value): string
    {
        return $queryBuilder->getEntityManager()->getConnection()->quote($value);
    }
}
