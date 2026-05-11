<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\HydratorService;
use ControleOnline\Service\RuntimeRequestInfoService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class RuntimeIpController
{
    public function __construct(
        private HydratorService $hydratorService,
        private RuntimeRequestInfoService $runtimeRequestInfoService
    ) {
    }

    #[Route('/runtime/ip', name: 'runtime_ip', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = $this->runtimeRequestInfoService->resolveClientIpData($request);

            return new JsonResponse($this->hydratorService->result([$payload]));
        } catch (Exception $e) {
            return new JsonResponse($this->hydratorService->error($e), 500);
        }
    }
}
