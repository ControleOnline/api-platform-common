<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;

class TimezoneSubscriber implements EventSubscriberInterface
{
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            //KernelEvents::REQUEST => ['onKernelRequest', 10],
            KernelEvents::REQUEST => ['onKernelRequest'],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $user = $this->security->getUser();
        if (!$user) return;

        $timezone = $user->getTimezone();
        date_default_timezone_set($timezone);
        $connection = $this->entityManager->getConnection();
        if (filter_var($_ENV['MYSQL_USER_TIMEZONE'], FILTER_VALIDATE_BOOLEAN))
            $connection->executeStatement('SET time_zone = :timezone', ['timezone' => $this->getMysqlTimezoneOffset($timezone)]);
        
    }

    private function getMysqlTimezoneOffset(string $timezone): string
    {
        $dateTimeZone = new \DateTimeZone($timezone);
        $offset = $dateTimeZone->getOffset(new \DateTimeImmutable('now', $dateTimeZone));
        $sign = $offset < 0 ? '-' : '+';
        $offset = abs($offset);

        return sprintf('%s%02d:%02d', $sign, intdiv($offset, 3600), intdiv($offset % 3600, 60));
    }
}
