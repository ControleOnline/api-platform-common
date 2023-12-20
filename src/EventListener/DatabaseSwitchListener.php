<?php

namespace ControleOnline\EventListener;

use Doctrine\DBAL\Connection;
use Exception;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Request;


class DatabaseSwitchListener
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $params = $this->getDbData($event->getRequest());

        $this->connection->close();
        $this->connection->__construct(
            $params,
            $this->connection->getDriver(),
            $this->connection->getConfiguration(),
            $this->connection->getEventManager()
        );
        $this->connection->connect();
    }

    private function getDbData(Request $request)
    {
        $host = $request->headers->get('Host') ? $request->get('domain') : null;
        if (!$host)
            throw new Exception('Please define header param "Host"', 301);


        $params = $this->connection->getParams();
        $sql = 'SELECT db_host, db_name, db_port, db_user, db_password FROM `databases` WHERE app_host = :app_host';
        $statement = $this->connection->executeQuery($sql, ['app_host' => $host]);
        $result = $statement->fetchAssociative();
        $params['host'] = $result['db_host'];
        $params['port'] = $result['db_port'];
        $params['dbname'] = $result['db_name'];
        $params['user'] = $result['db_user'];
        $params['password'] = $result['db_password'];

        return $params;
    }
}
