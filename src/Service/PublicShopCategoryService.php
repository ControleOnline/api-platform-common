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
    ) {
    }

    /**
     * @param int[]|null $projectedCategoryIds IDs supplied by the commercial
     *                                             module after publication checks.
     *
     * @return array{items: Category[], totalItems: int, page: int, itemsPerPage: int}
     */
    public function getCollection(
        ?int $requestedCompanyId,
        string $search,
        bool $requireImage,
        int $page,
        int $itemsPerPage,
        ?array $projectedCategoryIds = null
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
            $projectedCategoryIds,
            $search,
            $requireImage,
            $page,
            $itemsPerPage
        );
    }

    /**
     * @param int[]|null $projectedCategoryIds
     */
    public function getItem(
        int $id,
        ?int $requestedCompanyId,
        ?array $projectedCategoryIds = null
    ): ?Category {
        $companyId = $this->resolvePublicShopCompanyId($requestedCompanyId);
        if ($companyId === null) {
            return null;
        }

        $result = $this->categoryTreeService->build(
            $this->categoryRepository->findTreeCandidates($companyId, self::CATEGORY_CONTEXT),
            $companyId,
            self::CATEGORY_CONTEXT,
            $projectedCategoryIds,
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
        $files = [];
        foreach ($category->getCategoryFiles() as $categoryFile) {
            $file = $categoryFile->getFile();
            $files[] = [
                '@id' => '/category_files/' . $categoryFile->getId(),
                'id' => $categoryFile->getId(),
                'file' => [
                    '@id' => '/files/' . $file->getId(),
                    'id' => $file->getId(),
                    'fileType' => $file->getFileType(),
                    'fileName' => $file->getFileName(),
                    'context' => $file->getContext(),
                    'extension' => $file->getExtension(),
                ],
            ];
        }

        $parent = $category->getParent();

        return [
            '@id' => '/shop/categories/' . $category->getId(),
            '@type' => 'Category',
            'id' => $category->getId(),
            'name' => $category->getName(),
            'categoryFiles' => $files,
            'context' => $category->getContext(),
            'parent' => $parent ? [
                '@id' => '/shop/categories/' . $parent->getId(),
                'id' => $parent->getId(),
                'name' => $parent->getName(),
            ] : null,
            'company' => '/people/' . $category->getCompany()->getId(),
            'icon' => $category->getIcon(),
            'color' => $category->getColor(),
            'sortOrder' => $category->getSortOrder(),
        ];
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
