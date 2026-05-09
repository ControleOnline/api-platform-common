<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\MenuConfigService;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CreateMenuConfigAction
{
    public function __construct(private MenuConfigService $menuConfigService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent() ?: '{}', true);
        if (!is_array($payload)) {
            $payload = [];
        }

        try {
            $menu = $this->menuConfigService->createMenu($payload);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse([
                '@type' => 'Error',
                'description' => $exception->getMessage(),
            ], 400);
        }

        return new JsonResponse($this->menuConfigService->normalizeMenu($menu), 201);
    }
}
