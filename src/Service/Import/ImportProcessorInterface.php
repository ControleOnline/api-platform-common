<?php

namespace ControleOnline\Service\Import;

use ControleOnline\Entity\Import;

interface ImportProcessorInterface
{
    public function process(Import $import): void;

    public function getExampleCsv(): string;

    public function getType(): string;
}