<?php

namespace ControleOnline\Common\Tests\EventSubscriber;

use App\Service\EmailService;
use ControleOnline\EventSubscriber\BackendExceptionSubscriber;
use ControleOnline\Service\SystemLogConfigService;
use ControleOnline\Service\SystemLogWriter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BackendExceptionSubscriberTest extends TestCase
{
    public function testLogsAndEmailsWhenStatusCodeIs500(): void
    {
        $logWriter = $this->createMock(SystemLogWriter::class);
        $logWriter
            ->expects(self::once())
            ->method('write')
            ->with(
                'generic',
                'critical',
                HttpException::class,
                null,
                self::callback(function (array $payload): bool {
                    self::assertSame('backend-error', $payload['channel']);
                    self::assertSame(500, $payload['context']['statusCode']);
                    self::assertSame('/orders/1', $payload['context']['path']);

                    return true;
                }),
                'backend-error'
            )
            ->willReturn(true);

        $configService = $this->createMock(SystemLogConfigService::class);
        $configService
            ->expects(self::once())
            ->method('getErrorEmailSettings')
            ->willReturn([
                'enabled' => true,
                'recipients' => ['ops@empresa.com.br'],
            ]);

        $emailService = $this->createMock(EmailService::class);
        $emailService
            ->expects(self::once())
            ->method('sendErrorNotification')
            ->with(
                'Erro 500 Detectado',
                self::isType('string'),
                ['ops@empresa.com.br']
            );

        $subscriber = new BackendExceptionSubscriber(
            $logWriter,
            $configService,
            $emailService,
            $this->createMock(TokenStorageInterface::class),
        );

        $request = Request::create('https://api.example.com/orders/1', 'POST');
        $request->attributes->set('_route', 'orders_create');
        $request->headers->set('App-Domain', 'app.example.com');

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new HttpException(500, 'Falha no checkout')
        );

        $subscriber->onKernelException($event);
    }

    public function testDoesNotSendEmailForClientErrors(): void
    {
        $logWriter = $this->createMock(SystemLogWriter::class);
        $logWriter
            ->expects(self::once())
            ->method('write')
            ->with(
                'generic',
                'error',
                HttpException::class,
                null,
                self::callback(fn(array $payload): bool => $payload['context']['statusCode'] === 404),
                'backend-error'
            )
            ->willReturn(true);

        $configService = $this->createMock(SystemLogConfigService::class);
        $configService
            ->expects(self::never())
            ->method('getErrorEmailSettings');

        $emailService = $this->createMock(EmailService::class);
        $emailService
            ->expects(self::never())
            ->method('sendErrorNotification');

        $subscriber = new BackendExceptionSubscriber(
            $logWriter,
            $configService,
            $emailService,
            $this->createMock(TokenStorageInterface::class),
        );

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('https://api.example.com/missing', 'GET'),
            HttpKernelInterface::MAIN_REQUEST,
            new HttpException(404, 'Nao encontrado')
        );

        $subscriber->onKernelException($event);
    }
}
