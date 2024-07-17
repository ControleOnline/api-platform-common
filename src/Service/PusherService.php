<?php

namespace ControleOnline\Service;

use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;

class PusherService
{

    public function __construct(
        private PusherInterface $pusher
    ) {
    }

    public function push($data, $topic)
    {
        $this->pusher->push($data, $topic);
    }
}
