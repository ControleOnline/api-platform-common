<?php

declare(strict_types=1);

namespace ControleOnline\Migration;

use Doctrine\Migrations\AbstractMigration;

abstract class TenantAwareMigration extends AbstractMigration
{
    protected function resolveExecutionDomain(): string
    {
        $domain = trim((string) (
            $_ENV['APP_DOMAIN']
                ?? $_SERVER['APP_DOMAIN']
                ?? getenv('APP_DOMAIN')
                ?? ''
        ));

        if ($domain === '') {
            throw new \RuntimeException('APP_DOMAIN is not configured.');
        }

        return $domain;
    }

    protected function getMainCompanyId(): int
    {
        $domain = $this->resolveExecutionDomain();
        $mainCompanyId = (int) $this->connection->fetchOne(
            'SELECT people_id
             FROM people_domain
             WHERE domain = :domain
             LIMIT 1',
            [
                'domain' => $domain,
            ]
        );

        if ($mainCompanyId <= 0) {
            throw new \RuntimeException(
                sprintf('Main company for domain "%s" was not found.', $domain)
            );
        }

        return $mainCompanyId;
    }

}
