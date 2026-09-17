<?php

namespace ControleOnline\Service\Imports;

class ImportProcessorResolver
{
    private array $processors = [];

    public function __construct(iterable $processors)
    {
        foreach ($processors as $processor) {
            $this->processors[$processor->getType()] = $processor;
        }
    }

    public function resolve(string $type): ImportProcessorInterface
    {
        if (!isset($this->processors[$type])) {
            throw new \RuntimeException(sprintf(
                'Processor not found for type: %s. Available: %s',
                $type,
                implode(', ', array_keys($this->processors)) ?: '(none)'
            ));
        }

        return $this->processors[$type];
    }
}
