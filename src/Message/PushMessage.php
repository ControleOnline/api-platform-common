<?php

namespace ControleOnline\Message;

class PushMessage
{

    public  string $topic;
    public  array $data;

    public function __construct(
        string $topic,
        array $data
    ) {
        $this->topic =  $topic;
        $this->data =  $data;
    }
}
