<?php

namespace ControleOnline\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ApiResponseListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (
            !str_contains($response->headers->get('content-type') ?? '', 'application/json')
        ) {
            return;
        }

        $content = $response->getContent();

        if (!$content) {
            return;
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            return;
        }

        $data['extra_data'] = [
            'timestamp' => time(),
            'custom' => 'valor_dinamico'
        ];

        $response->setContent(json_encode($data));
    }
}
