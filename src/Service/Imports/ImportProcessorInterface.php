<?php

namespace ControleOnline\Service\Imports;

use ControleOnline\Entity\Import;

interface ImportProcessorInterface
{
    public function process(Import $import): void;

    public function getExampleCsv(): array;

    public function getType(): string;
}
