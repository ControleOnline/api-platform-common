<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\LoggerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FrontendDebugLogAction
{
    public function __construct(
        private LoggerService $loggerService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return new JsonResponse(
                ['error' => 'Invalid JSON payload'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Normaliza os campos esperados do frontend para gravar no logger do banco.
        $channel = trim((string) ($payload['channel'] ?? 'frontend-debug'));
        $level = strtolower(trim((string) ($payload['level'] ?? 'info')));
        $message = trim((string) ($payload['message'] ?? 'Frontend debug log'));
        $context = is_array($payload['context'] ?? null)
            ? $payload['context']
            : [];

        $entityClass = trim((string) ($payload['entityClass'] ?? $payload['class'] ?? ''));
        $entityRow = $payload['entityRow'] ?? $payload['row'] ?? null;

        if ($entityClass !== '' && is_numeric($entityRow)) {
            $context['entityClass'] = $entityClass;
            $context['entityRow'] = (int) $entityRow;
        }

        $context['source'] = 'frontend';

        $this->loggerService
            ->getLogger($channel !== '' ? $channel : 'frontend-debug')
            ->log($level !== '' ? $level : 'info', $message !== '' ? $message : 'Frontend debug log', $context);

        return new JsonResponse(
            ['accepted' => true],
            Response::HTTP_ACCEPTED
        );
    }
}
