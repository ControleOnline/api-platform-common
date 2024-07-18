<?php

namespace ControleOnline\Service;

use ControleOnline\Message\Notification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class PusherService
{

    public function __construct(private MessageBusInterface $messageBus, private  LoggerInterface $logger)
    {
    }

    public function push(array $data, string $topic)
    {
        try {
            $this->logger->info('Attempting to push message', ['data' => $data, 'topic' => $topic]);

            $message = new Notification($data, $topic);
            $this->messageBus->dispatch($message);

            $this->logger->info('Message dispatched successfully');
        } catch (\Exception $e) {
            $this->logger->error('Failed to dispatch message', ['exception' => $e]);
            throw $e;
        }
    }
}
