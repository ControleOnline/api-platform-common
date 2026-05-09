<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\MenuConfigService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GetMenuConfigAction
{
    public function __construct(private MenuConfigService $menuConfigService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $appType = $request->query->get('appType');
        $page = (int) $request->query->get('page', 1);
        $itemsPerPage = (int) $request->query->get('itemsPerPage', 50);

        $result = $this->menuConfigService->getPaginatedMenus($appType, $page, $itemsPerPage);

        return new JsonResponse([
            'member' => $result['member'],
            'totalItems' => $result['totalItems'],
            'summary' => [
                'appTypes' => MenuConfigService::APP_TYPES,
                'linkTypes' => $this->menuConfigService->getAllowedLinkTypes(),
            ],
        ]);
    }
}
