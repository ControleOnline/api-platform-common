<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Category;
use ControleOnline\Service\MenuConfigService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SaveMenuCategoryConfigAction
{
    public function __construct(
        private EntityManagerInterface $manager,
        private MenuConfigService $menuConfigService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $id = (int) $request->attributes->get('id');
        $category = $this->manager->getRepository(Category::class)->find($id);

        if (!$category instanceof Category) {
            return new JsonResponse([
                '@type' => 'Error',
                'description' => 'Category not found',
            ], 404);
        }

        $payload = json_decode($request->getContent() ?: '{}', true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $category = $this->menuConfigService->updateCategory($category, $payload);

        return new JsonResponse($this->menuConfigService->normalizeCategory($category));
    }
}
