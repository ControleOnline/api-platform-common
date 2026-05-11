<?php

namespace ControleOnline\Common\Tests\Service;

use ControleOnline\Service\RuntimeRequestInfoService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RuntimeRequestInfoServiceTest extends TestCase
{
    public function testResolvesClientIpFromRequestWithoutDatabaseAccess(): void
    {
        $request = new Request(
            server: [
                'REMOTE_ADDR' => '203.0.113.42',
            ]
        );
        $request->headers->set('x-forwarded-for', '198.51.100.10, 10.0.0.1');
        $request->headers->set('x-real-ip', '198.51.100.11');
        $request->headers->set('true-client-ip', '198.51.100.12');
        $request->headers->set('cf-connecting-ip', '198.51.100.13');

        $service = new RuntimeRequestInfoService();

        self::assertSame(
            [
                'ip' => '203.0.113.42',
                'ips' => ['203.0.113.42'],
                'remoteAddr' => '203.0.113.42',
                'forwardedFor' => ['198.51.100.10', '10.0.0.1'],
                'realIp' => '198.51.100.11',
                'trueClientIp' => '198.51.100.12',
                'cfConnectingIp' => '198.51.100.13',
            ],
            $service->resolveClientIpData($request)
        );
    }

    public function testNormalizesMissingHeadersToNullOrEmptyLists(): void
    {
        $request = new Request(
            server: [
                'REMOTE_ADDR' => '203.0.113.42',
            ]
        );

        $service = new RuntimeRequestInfoService();

        self::assertSame(
            [
                'ip' => '203.0.113.42',
                'ips' => ['203.0.113.42'],
                'remoteAddr' => '203.0.113.42',
                'forwardedFor' => [],
                'realIp' => null,
                'trueClientIp' => null,
                'cfConnectingIp' => null,
            ],
            $service->resolveClientIpData($request)
        );
    }
}
