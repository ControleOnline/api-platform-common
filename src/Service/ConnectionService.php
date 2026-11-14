<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Connection;

/**
 * Entity hooks for connections (called by DefaultEventListener).
 * The UI creates connections without a status (status_id is NOT NULL), so a new one starts "open".
 */
class ConnectionService
{
    public function __construct(private StatusService $statusService)
    {
    }

    public function prePersist(Connection $connection): Connection
    {
        if (!$connection->getStatus()) {
            $connection->setStatus($this->statusService->discoveryStatus('open', 'open', 'connection'));
        }

        return $connection;
    }
}
