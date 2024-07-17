<?php

namespace ControleOnline\Message;

class PushMessage
{
    public function __construct(
        public readonly string $topic,
        public readonly array $data
    ) {
    }
}
