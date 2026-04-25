<?php

namespace ControleOnline\Service;

use Psr\Log\LoggerInterface;

class LoggerService
{
    public function __construct(
        private SystemLogWriter $systemLogWriter,
    ) {}

    public function getLogger(string $name): LoggerInterface
    {
        $normalizedName = trim($name) !== '' ? trim($name) : 'application';

        return new DatabaseLogger(
            $this->systemLogWriter,
            $normalizedName
        );
    }
}
