<?php

namespace ControleOnline\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use ApiPlatform\Symfony\EventListener\EventPriorities;


class AddExtraDataSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onKernelView', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->attributes->get('_api_resource_class')) {
            return;
        }

        if (!in_array($request->getMethod(), ['GET'])) {
            return;
        }

        $data = $event->getControllerResult();

        if (is_iterable($data)) {
            $data = array_map(fn($item) => $this->normalize($item), iterator_to_array($data));
        } elseif (is_object($data)) {
            $data = $this->normalize($data);
        }

        $event->setControllerResult($data);
    }

    private function normalize(object $object): array
    {
        return array_merge(
            get_object_vars($object),
            [
                'extra_data' => [
                    'timestamp' => time(),
                    'custom' => 'valor_dinamico'
                ]
            ]
        );
    }
}
