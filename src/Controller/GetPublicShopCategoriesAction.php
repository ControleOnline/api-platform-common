<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\PublicShopCategoryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GetPublicShopCategoriesAction
{
    private const DEFAULT_ITEMS_PER_PAGE = 30;
    private const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private PublicShopCategoryService $categoryService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $itemsPerPage = max(1, min(
            self::MAX_ITEMS_PER_PAGE,
            (int) $request->query->get('itemsPerPage', self::DEFAULT_ITEMS_PER_PAGE)
                ?: self::DEFAULT_ITEMS_PER_PAGE
        ));
        $companyId = $this->normalizeId($request->query->get('company'));
        $search = trim((string) $request->query->get('name', $request->query->get('search', '')));
        $query = $request->query->all();
        $requireImage = filter_var(
            $query['exists']['categoryFiles'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        ) || (($query['categoryFiles']['file']['fileType'] ?? null) === 'image');

        $result = $this->categoryService->getCollection(
            $companyId,
            $search,
            $requireImage,
            $page,
            $itemsPerPage
        );
        $items = array_map(
            fn ($category): array => $this->categoryService->serializeCategory($category),
            $result['items']
        );

        return new JsonResponse([
            '@context' => '/contexts/Category',
            '@id' => '/shop/categories',
            '@type' => 'Collection',
            'totalItems' => $result['totalItems'],
            'member' => $items,
            'page' => $result['page'],
            'itemsPerPage' => $result['itemsPerPage'],
        ]);
    }

    private function normalizeId(mixed $value): ?int
    {
        $id = (int) preg_replace('/\D+/', '', (string) $value);

        return $id > 0 ? $id : null;
    }
}
