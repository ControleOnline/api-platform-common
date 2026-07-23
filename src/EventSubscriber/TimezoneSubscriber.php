<?php

namespace ControleOnline\EventSubscriber;

use ControleOnline\Service\TimezoneService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;

class TimezoneSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private TimezoneService $timezoneService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest'],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $this->timezoneService->applyForUser($this->security->getUser());
    }
}
