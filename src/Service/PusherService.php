<?php

namespace ControleOnline\Service;

use Psr\Log\LoggerInterface;
use Gos\Component\WebSocketClient\Wamp\Client;

class PusherService
{

    public function __construct(private  LoggerInterface $logger)
    {
    }

    public function push(array $data, string $topic)
    {
        try {
            $this->logger->info('Attempting to push message', ['data' => $data, 'topic' => $topic]);
            
            $webSocketClient =  new Client('localhost', '8080');
            $webSocketClient->connect();
            $webSocketClient->publish($topic, json_encode($data));
            $webSocketClient->disconnect();


            $this->logger->info('Message dispatched successfully');
        } catch (\Exception $e) {
            $this->logger->error('Failed to dispatch message', ['exception' => $e]);
            throw $e;
        }
    }
}
