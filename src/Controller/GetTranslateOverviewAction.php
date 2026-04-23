<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\TranslateService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GetTranslateOverviewAction
{
    public function __construct(private TranslateService $translateService) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $overview = $this->translateService->buildOverview($request->query->all());

            return new JsonResponse([
                'response' => [
                    'data' => $overview['items'],
                    'summary' => $overview['summary'],
                    'count' => count($overview['items']),
                    'error' => '',
                    'success' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'summary' => [],
                    'count' => 0,
                    'error' => $e->getMessage(),
                    'success' => false,
                ],
            ], 400);
        }
    }
}
