<?php

namespace ControleOnline\Service;

use Doctrine\ORM\EntityManagerInterface;

class TimezoneService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function applyForUser($user): void
    {
        if (!$user || !method_exists($user, 'getTimezone')) {
            return;
        }

        $timezone = $user->getTimezone()?->getName() ?? 'UTC';

        $this->applyTimezone($timezone);
    }

    private function applyTimezone(string $timezone): void
    {
        $timezone = $this->normalizeTimezone($timezone);
        date_default_timezone_set($timezone);

        if (!filter_var($_ENV['MYSQL_USER_TIMEZONE'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $this->entityManager
            ->getConnection()
            ->executeStatement('SET time_zone = :timezone', ['timezone' => $this->getMysqlTimezoneOffset($timezone)]);
    }

    private function getMysqlTimezoneOffset(string $timezone): string
    {
        $dateTimeZone = new \DateTimeZone($timezone);
        $offset = $dateTimeZone->getOffset(new \DateTimeImmutable('now', $dateTimeZone));
        $sign = $offset < 0 ? '-' : '+';
        $offset = abs($offset);

        return sprintf('%s%02d:%02d', $sign, intdiv($offset, 3600), intdiv($offset % 3600, 60));
    }

    private function normalizeTimezone(string $timezone): string
    {
        $timezone = trim($timezone);
        $timezone = preg_replace('/\s*\(UTC[+-]\d{1,2}(?::\d{2})?\)\s*$/i', '', $timezone) ?? $timezone;

        try {
            new \DateTimeZone($timezone);
        } catch (\DateInvalidTimeZoneException) {
            return 'UTC';
        }

        return $timezone;
    }
}
