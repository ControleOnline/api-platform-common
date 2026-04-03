<?php

namespace ControleOnline\Attribute;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class CollectionSummary
{
    private const SUPPORTED_OPERATIONS = ['sum', 'count'];

    public function __construct(
        private string|array $operations,
        private ?string $name = null
    ) {
        $operations = $this->normalizeOperations();
        $invalidOperations = array_diff($operations, self::SUPPORTED_OPERATIONS);

        if ([] === $operations) {
            throw new InvalidArgumentException('CollectionSummary requires at least one operation.');
        }

        if ([] !== $invalidOperations) {
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
