<?php

namespace ControleOnline\Service;

use Symfony\Component\HttpFoundation\Request;

class RuntimeRequestInfoService
{
    public function resolveClientIpData(Request $request): array
    {
        return [
            'ip' => $this->normalizeValue($request->getClientIp()),
            'ips' => $this->normalizeList($request->getClientIps()),
            'remoteAddr' => $this->normalizeValue($request->server->get('REMOTE_ADDR')),
            'forwardedFor' => $this->parseHeaderList($request->headers->get('x-forwarded-for')),
            'realIp' => $this->normalizeValue($request->headers->get('x-real-ip')),
            'trueClientIp' => $this->normalizeValue($request->headers->get('true-client-ip')),
            'cfConnectingIp' => $this->normalizeValue($request->headers->get('cf-connecting-ip')),
        ];
    }

    private function normalizeValue(mixed $value): ?string
    {
        $normalizedValue = trim((string) ($value ?? ''));

        return $normalizedValue !== '' ? $normalizedValue : null;
    }

    private function normalizeList(array $values): array
    {
        return array_values(array_filter(array_map(
            fn(mixed $value): ?string => $this->normalizeValue($value),
            $values
        )));
    }

    private function parseHeaderList(?string $headerValue): array
    {
        $normalizedHeader = trim((string) ($headerValue ?? ''));
        if ($normalizedHeader === '') {
            return [];
        }

        return $this->normalizeList(explode(',', $normalizedHeader));
    }
}
