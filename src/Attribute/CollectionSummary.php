<?php

namespace ControleOnline\Attribute;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class CollectionSummary
{
    private const SUPPORTED_OPERATIONS = ['sum', 'count'];

    public function __construct(
        private string|array|null $operations = null,
        private ?string $name = null,
        private ?string $parameter = null,
        private string|array|null $parameterValue = null,
        private string|array|null $groups = null,
        private ?string $resolver = null
    ) {
        $operations = $this->normalizeOperations();
        $invalidOperations = array_diff($operations, self::SUPPORTED_OPERATIONS);

        if ([] === $operations && null === $this->resolver) {
            throw new InvalidArgumentException('CollectionSummary requires at least one operation or a custom resolver.');
        }

        if (null === $this->resolver && [] !== $invalidOperations) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported CollectionSummary operations: %s. Supported operations are: %s.',
                implode(', ', $invalidOperations),
                implode(', ', self::SUPPORTED_OPERATIONS)
            ));
        }
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getOperations(): array
    {
        return $this->normalizeOperations();
    }

    public function getParameter(): ?string
    {
        return $this->parameter;
    }

    public function getParameterValues(): array
    {
        $values = is_array($this->parameterValue) ? $this->parameterValue : [$this->parameterValue];

        return array_values(array_unique(array_filter(
            array_map(static fn($value) => trim((string) $value), $values),
            static fn(string $value) => '' !== $value
        )));
    }

    public function getGroups(): array
    {
        $groups = is_array($this->groups) ? $this->groups : [$this->groups];

        return array_values(array_unique(array_filter(
            array_map(static fn($group) => trim((string) $group), $groups),
            static fn(string $group) => '' !== $group
        )));
    }

    public function getResolver(): ?string
    {
        return $this->resolver;
    }

    private function normalizeOperations(): array
    {
        $operations = is_array($this->operations) ? $this->operations : [$this->operations];
        $operations = array_map(
            static fn($operation) => strtolower((string) $operation),
            $operations
        );

        return array_values(array_unique(array_filter($operations, static fn($operation) => '' !== $operation)));
    }
}
