<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\CategoryProjectionRequestParser;
use ControleOnline\Service\PublicShopCategoryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GetPublicShopCategoryAction
{
    public function __construct(
        private PublicShopCategoryService $categoryService,
        private CategoryProjectionRequestParser $projectionRequestParser,
    ) {
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $companyId = $this->normalizeId($request->query->get('company'));
        $category = $this->categoryService->getItem(
            $id,
            $companyId,
            $this->projectionRequestParser->parse($request)
        );

        if (!$category) {
            return new JsonResponse([
                '@type' => 'Error',
                'hydra:title' => 'Not Found',
                'hydra:description' => 'Category not found.',
            ], 404);
        }

        return new JsonResponse($this->categoryService->serializeCategory($category));
    }

    private function normalizeId(mixed $value): ?int
    {
        $id = (int) preg_replace('/\D+/', '', (string) $value);

        return $id > 0 ? $id : null;
    }

}
