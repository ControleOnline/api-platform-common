<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Category;
use ControleOnline\Entity\Config;
use ControleOnline\Entity\People;
use ControleOnline\Repository\CategoryRepository;

class PublicShopCategoryService
{
    private const CATEGORY_CONTEXT = 'products';
    private const VISIBLE_COMPANY_IDS_CONFIG_KEY = 'shop-franchise-visible-company-ids';

    public function __construct(
        private DomainService $domainService,
        private ConfigService $configService,
        private CategoryRepository $categoryRepository,
        private CategoryTreeService $categoryTreeService,
        private CategoryPayloadService $categoryPayloadService,
    ) {
    }

    /**
     * @return array{items: Category[], totalItems: int, page: int, itemsPerPage: int}
     */
    public function getCollection(
        ?int $requestedCompanyId,
        string $search,
        bool $requireImage,
        int $page,
        int $itemsPerPage
    ): array {
        $companyId = $this->resolvePublicShopCompanyId($requestedCompanyId);
        if ($companyId === null) {
            return [
                'items' => [],
                'totalItems' => 0,
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
            ];
        }

        return $this->categoryTreeService->build(
            $this->categoryRepository->findTreeCandidates($companyId, self::CATEGORY_CONTEXT),
            $companyId,
            self::CATEGORY_CONTEXT,
            null,
            $search,
            $requireImage,
            $page,
            $itemsPerPage
        );
    }

    public function getItem(int $id, ?int $requestedCompanyId): ?Category
    {
        $companyId = $this->resolvePublicShopCompanyId($requestedCompanyId);
        if ($companyId === null) {
            return null;
        }

        $result = $this->categoryTreeService->build(
            $this->categoryRepository->findTreeCandidates($companyId, self::CATEGORY_CONTEXT),
            $companyId,
            self::CATEGORY_CONTEXT,
            null,
            '',
            false,
            1,
            PHP_INT_MAX
        );

        foreach ($result['items'] as $category) {
            if ((int) $category->getId() === $id) {
                return $category;
            }
        }

        return null;
    }

    public function serializeCategory(Category $category): array
    {
        return $this->categoryPayloadService->serialize($category, '/shop/categories');
    }

    private function resolvePublicShopCompanyId(?int $requestedCompanyId): ?int
    {
        $peopleDomain = $this->domainService->getPeopleDomain();
        if (strtoupper(trim((string) $peopleDomain->getDomainType())) !== 'SHOP') {
            return null;
        }

        $domainCompany = $peopleDomain->getPeople();
        if (!$domainCompany instanceof People) {
            return null;
        }

        $domainCompanyId = (int) $domainCompany->getId();
        $allowedCompanyIds = [$domainCompanyId];

        foreach ($this->configService->getCompanyConfigs($domainCompany, 'public') as $config) {
            if (
                !$config instanceof Config
                || $config->getConfigKey() !== self::VISIBLE_COMPANY_IDS_CONFIG_KEY
            ) {
                continue;
            }

            $allowedCompanyIds = array_merge(
                $allowedCompanyIds,
                $this->normalizeIds($config->getConfigValue())
            );
        }

        $allowedCompanyIds = array_values(array_unique($allowedCompanyIds));
        $companyId = $requestedCompanyId ?: $domainCompanyId;

        return in_array($companyId, $allowedCompanyIds, true) ? $companyId : null;
    }

    /**
     * @return int[]
     */
    private function normalizeIds(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/', $value);
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): int => (int) preg_replace('/\D+/', '', (string) $id),
            $value
        ))));
    }
}
