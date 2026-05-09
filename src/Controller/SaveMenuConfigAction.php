<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Menu;
use ControleOnline\Service\MenuConfigService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SaveMenuConfigAction
{
    public function __construct(
        private EntityManagerInterface $manager,
        private MenuConfigService $menuConfigService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $id = (int) $request->attributes->get('id');
        $menu = $this->manager->getRepository(Menu::class)->find($id);

        if (!$menu instanceof Menu) {
            return new JsonResponse([
                '@type' => 'Error',
                'description' => 'Menu not found',
            ], 404);
        }

        $payload = json_decode($request->getContent() ?: '{}', true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $menu = $this->menuConfigService->updateMenu($menu, $payload);

        return new JsonResponse($this->menuConfigService->normalizeMenu($menu));
    }
}
