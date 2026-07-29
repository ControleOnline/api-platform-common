<?php

namespace ControleOnline\Service;

use Doctrine\DBAL\Connection;

class ServerService
{
    public function __construct(
        private Connection $connection,
        private DatabaseSwitchService $databaseSwitchService,
        private DomainService $domainService,
    ) {}

    public function findByDomain(?string $domain): ?array
    {
        $normalizedDomain = trim((string) $domain);
        if ($normalizedDomain === '') {
            return null;
        }

        $currentDomain = trim((string) $this->domainService->getMainDomain());

        $this->databaseSwitchService->switchBackToOriginalDatabase();

        try {
            $server = $this->connection->fetchAssociative(
                'SELECT
                    servers.`host`,
                    servers.`user` AS server_user,
                    servers.`port`,
                    servers.`driver`
                 FROM `tenancies`
                 INNER JOIN `servers`
                    ON servers.`id` = tenancies.`server_id`
                 WHERE tenancies.`app_host` = :app_host
                 LIMIT 1',
                [
                    'app_host' => $normalizedDomain,
                ]
            );
        } finally {
            if ($currentDomain !== '') {
                $this->databaseSwitchService->switchDatabaseByDomain($currentDomain);
            }
        }

        if (!is_array($server) || $server === []) {
            return null;
        }

        return [
            'appHost' => $normalizedDomain,
            'host' => trim((string) ($server['host'] ?? '')),
            'user' => trim((string) ($server['server_user'] ?? '')),
            'port' => (int) ($server['port'] ?? 0),
            'driver' => trim((string) ($server['driver'] ?? '')),
        ];
    }
}
