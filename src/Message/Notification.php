<?php

namespace ControleOnline\Message;

class Notification
{
    private $data;
    private $topic;

    public function __construct(array $data, string $topic)
    {
        $this->data = $data;
        $this->topic = $topic;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getTopic(): string
    {
        return $this->topic;
    }
}
