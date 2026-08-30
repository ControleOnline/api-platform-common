<?php

namespace ControleOnline\Tests\Controller;

use ControleOnline\Controller\DiscoveryMainConfigsAction;
use ControleOnline\Service\ConfigService;
use ControleOnline\Service\HydratorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DiscoveryMainConfigsActionTest extends TestCase
{
    public function testInvalidPeopleReferenceReturnsHydraErrorWithBadRequest(): void
    {
        $exception = new \InvalidArgumentException('People not found');

        $configService = $this->createMock(ConfigService::class);
        $configService
            ->expects(self::once())
            ->method('discoveryMainConfigsFromJson')
            ->with('{"people":"/people/999"}', null)
            ->willThrowException($exception);

        $hydratorService = $this->createMock(HydratorService::class);
        $hydratorService
            ->expects(self::once())
            ->method('error')
            ->with($exception)
            ->willReturn([
                '@context' => '/contexts/Error',
                '@type' => 'Error',
                'hydra:title' => 'An error occurred',
                'hydra:description' => 'People not found',
            ]);

        $response = (new DiscoveryMainConfigsAction(
            $hydratorService,
            $configService
        ))(new Request([], [], [], [], [], [], '{"people":"/people/999"}'));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            [
                '@context' => '/contexts/Error',
                '@type' => 'Error',
                'hydra:title' => 'An error occurred',
                'hydra:description' => 'People not found',
            ],
            json_decode($response->getContent(), true)
        );
    }
}
