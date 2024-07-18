<?php

namespace ControleOnline\Service;

use ControleOnline\Message\Notification;
use stdClass;
use Symfony\Component\Messenger\MessageBusInterface;

class PusherService
{

    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    public function push(array $data, string $topic)
    {

        return;
        try {
            $message = new Notification($data, $topic);
            $this->messageBus->dispatch($message);
        } catch (\Exception $e) {
            // Handle exception, e.g., log the error
            throw $e; // Or rethrow for further handling
        }
    }
}
