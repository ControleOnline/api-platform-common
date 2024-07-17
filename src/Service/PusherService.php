<?php

namespace ControleOnline\Service;

use Gos\Bundle\WebSocketBundle\Pusher\PusherRegistry;

class PusherService
{

    public function __construct(
        private PusherRegistry  $pusher
    ) {
    }

    public function push($data, $topic)
    {
        print_r($this->pusher->getPushers());
    }
}
