<?php

namespace ControleOnline\State;

class CollectionSummaryResult
{
    public function __construct(
        private mixed $collection,
        private array $summary
    ) {}

    public function getCollection(): mixed
    {
        return $this->collection;
    }

    public function getSummary(): array
    {
        return $this->summary;
    }
}
