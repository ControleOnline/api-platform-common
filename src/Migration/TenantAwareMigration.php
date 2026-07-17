<?php

declare(strict_types=1);

namespace ControleOnline\Migration;

use Doctrine\Migrations\AbstractMigration;

abstract class TenantAwareMigration extends AbstractMigration
{
    private const FALLBACK_MAIN_DOMAIN = 'api.controleonline.com';

    protected function resolveExecutionDomain(): string
    {
        foreach ($this->getDomainCandidates() as $candidate) {
            $domain = $this->normalizeDomainCandidate($candidate);
            if ($domain !== null) {
                return $domain;
            }
        }

        return self::FALLBACK_MAIN_DOMAIN;
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

    /**
     * @return array<int, mixed>
     */
    private function getDomainCandidates(): array
    {
        return [
            $_ENV['APP_DOMAIN'] ?? null,
            $_SERVER['APP_DOMAIN'] ?? null,
            $_ENV['ADMIN_APP_DOMAIN'] ?? null,
            $_SERVER['ADMIN_APP_DOMAIN'] ?? null,
            $_ENV['PUBLIC_APP_DOMAIN'] ?? null,
            $_SERVER['PUBLIC_APP_DOMAIN'] ?? null,
            getenv('APP_DOMAIN') ?: null,
            getenv('ADMIN_APP_DOMAIN') ?: null,
            getenv('PUBLIC_APP_DOMAIN') ?: null,
            $_SERVER['HTTP_HOST'] ?? null,
        ];
    }

    private function normalizeDomainCandidate(mixed $candidate): ?string
    {
        if (!is_string($candidate)) {
            return null;
        }

        $candidate = trim($candidate);
        if ($candidate === '') {
            return null;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $candidate) || str_starts_with($candidate, '//')) {
            $host = parse_url($candidate, PHP_URL_HOST);
            if (!is_string($host) || trim($host) === '') {
                return null;
            }

            $port = parse_url($candidate, PHP_URL_PORT);
            if (is_int($port)) {
                return sprintf('%s:%d', $host, $port);
            }

            return $host;
        }

        $candidate = preg_replace('/[\\/?#].*$/', '', $candidate);
        $candidate = preg_replace('/[^a-zA-Z0-9.:_-]/', '', $candidate);

        return $candidate !== '' ? $candidate : null;
    }
}
