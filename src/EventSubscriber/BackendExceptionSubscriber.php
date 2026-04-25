<?php

namespace ControleOnline\EventSubscriber;

use App\Service\EmailService;
use ControleOnline\Service\SystemLogConfigService;
use ControleOnline\Service\SystemLogWriter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BackendExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SystemLogWriter $systemLogWriter,
        private SystemLogConfigService $systemLogConfigService,
        private EmailService $emailService,
        private TokenStorageInterface $tokenStorage,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -120],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $exception = $event->getThrowable();
        $request = $event->getRequest();
        $statusCode = $this->resolveStatusCode($exception);
        $level = $statusCode >= 500 ? 'critical' : 'error';

        $payload = [
            'channel' => 'backend-error',
            'level' => $level,
            'message' => trim($exception->getMessage()) !== ''
                ? $exception->getMessage()
                : 'Unhandled backend exception',
            'context' => [
                'statusCode' => $statusCode,
                'exceptionClass' => $exception::class,
                'exceptionCode' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'route' => $request->attributes->get('_route'),
                'method' => $request->getMethod(),
                'uri' => $request->getUri(),
                'path' => $request->getPathInfo(),
                'clientIp' => $request->getClientIp(),
                'appDomain' => $request->headers->get('App-Domain'),
                'requestFormat' => $request->getRequestFormat(),
                'isXmlHttpRequest' => $request->isXmlHttpRequest(),
                'user' => $this->resolveCurrentUserData(),
                'trace' => $exception->getTraceAsString(),
            ],
        ];

        try {
            $this->systemLogWriter->write(
                SystemLogConfigService::POLICY_GENERIC,
                $level,
                $exception::class,
                null,
                $payload,
                'backend-error'
            );
        } catch (\Throwable) {
        }

        if ($statusCode < 500) {
            return;
        }

        $emailSettings = $this->systemLogConfigService->getErrorEmailSettings();
        if (!$emailSettings['enabled'] || $emailSettings['recipients'] === []) {
            return;
        }

        $body = $this->buildEmailBody($payload);
        $this->emailService->sendErrorNotification(
            'Erro 500 Detectado',
            nl2br(htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
            $emailSettings['recipients']
        );
    }

    private function resolveStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof RequestExceptionInterface) {
            return 400;
        }

        return 500;
    }

    private function resolveCurrentUserData(): array
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        if (!is_object($user)) {
            return [];
        }

        $userPeople = method_exists($user, 'getPeople') ? $user->getPeople() : null;

        return array_filter([
            'id' => method_exists($user, 'getId') ? $user->getId() : null,
            'identifier' => method_exists($user, 'getUserIdentifier')
                ? $user->getUserIdentifier()
                : null,
            'peopleId' => is_object($userPeople) && method_exists($userPeople, 'getId')
                ? $userPeople->getId()
                : null,
            'peopleAlias' => is_object($userPeople) && method_exists($userPeople, 'getAlias')
                ? $userPeople->getAlias()
                : null,
        ], static fn(mixed $value): bool => $value !== null && $value !== '');
    }

    private function buildEmailBody(array $payload): string
    {
        $context = $payload['context'] ?? [];
        $user = is_array($context['user'] ?? null) ? $context['user'] : [];

        return <<<TXT
Erro 500 detectado no backend

Usuario: {$this->stringValue($user['identifier'] ?? 'N/A')}
URI: {$this->stringValue($context['uri'] ?? 'N/A')}
Metodo: {$this->stringValue($context['method'] ?? 'N/A')}
Rota: {$this->stringValue($context['route'] ?? 'N/A')}
IP: {$this->stringValue($context['clientIp'] ?? 'N/A')}
Dominio: {$this->stringValue($context['appDomain'] ?? 'N/A')}
Status: {$this->stringValue($context['statusCode'] ?? '500')}

Mensagem:
{$this->stringValue($payload['message'] ?? '(sem mensagem)')}

Excecao: {$this->stringValue($context['exceptionClass'] ?? 'N/A')}
Arquivo: {$this->stringValue($context['file'] ?? 'N/A')}:{$this->stringValue($context['line'] ?? 'N/A')}

Stack trace:
{$this->stringValue($context['trace'] ?? 'N/A')}
TXT;
    }

    private function stringValue(mixed $value): string
    {
        return trim((string) $value) !== '' ? (string) $value : 'N/A';
    }
}
