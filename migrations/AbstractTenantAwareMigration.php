<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use Doctrine\Migrations\AbstractMigration;

abstract class AbstractTenantAwareMigration extends AbstractMigration
{
    private const FALLBACK_MAIN_DOMAIN = 'api.controleonline.com';

    protected function resolveExecutionDomain(): string
    {
        $domain = trim((string) (
            $_ENV['APP_DOMAIN']
                ?? $_SERVER['APP_DOMAIN']
                ?? getenv('APP_DOMAIN')
                ?? ''
        ));

        return $domain !== '' ? $domain : self::FALLBACK_MAIN_DOMAIN;
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
