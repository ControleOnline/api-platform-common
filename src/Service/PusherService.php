<?php

namespace ControleOnline\Service;

use ControleOnline\Message\PushMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class PusherService
{

    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    public function push(array $data, string $topic)
    {
        try {
            $message = new PushMessage($topic, $data);
            $this->messageBus->dispatch($message);
        } catch (\Exception $e) {
            // Handle exception, e.g., log the error
            throw $e; // Or rethrow for further handling
        }
    }
}
