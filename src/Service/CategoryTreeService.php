<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Category;

class CategoryTreeService
{
    /**
     * @param Category[] $categories
     * @param int[]|null $projectedIds IDs authorized by a trusted server-side
     *                                 consumer; null keeps the compatible
     *                                 unprojected view. Public request parameters
     *                                 must never be passed here as authority.
     *
     * @return array{items: Category[], totalItems: int, page: int, itemsPerPage: int}
     */
    public function build(
        array $categories,
        int $companyId,
        string $context,
        ?array $projectedIds,
        string $search,
        bool $requireImage,
        int $page,
        int $itemsPerPage
    ): array {
        $page = max(1, $page);
        $itemsPerPage = max(1, $itemsPerPage);
        $scoped = $this->indexScopedCategories($categories, $companyId, $context);
        $targetIds = $projectedIds === null
            ? array_keys($scoped)
            : $this->normalizeIds($projectedIds, $scoped);

        $targetIds = array_values(array_filter(
            $targetIds,
            fn (int $id): bool => $this->matchesFilters(
                $scoped[$id],
                $search,
                $requireImage
            )
        ));

        $includedIds = $this->includeAncestors($targetIds, $scoped);
        $ordered = $this->orderTree($scoped, $includedIds);
        $totalItems = count($ordered);

        return [
            'items' => array_slice($ordered, ($page - 1) * $itemsPerPage, $itemsPerPage),
            'totalItems' => $totalItems,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
        ];
    }

    /**
     * @param Category[] $categories
     *
     * @return array<int, Category>
     */
    private function indexScopedCategories(array $categories, int $companyId, string $context): array
    {
        $scoped = [];

        foreach ($categories as $category) {
            if (!$category instanceof Category || (int) $category->getId() <= 0) {
                continue;
            }

            if (
                (int) $category->getCompany()?->getId() !== $companyId
                || (string) $category->getContext() !== $context
            ) {
                continue;
            }

            $scoped[(int) $category->getId()] = $category;
        }

        return $scoped;
    }

    /**
     * @param mixed[] $ids
     * @param array<int, Category> $scoped
     *
     * @return int[]
     */
    private function normalizeIds(array $ids, array $scoped): array
    {
        $normalized = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0 && isset($scoped[$id])) {
                $normalized[$id] = $id;
            }
        }

        return array_values($normalized);
    }

    private function matchesFilters(Category $category, string $search, bool $requireImage): bool
    {
        if (
            trim($search) !== ''
            && !str_contains(
                mb_strtolower((string) $category->getName()),
                mb_strtolower(trim($search))
            )
        ) {
            return false;
        }

        if (!$requireImage) {
            return true;
        }

        foreach ($category->getCategoryFiles() as $categoryFile) {
            if ($categoryFile->getFile()->getFileType() === 'image') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int[] $targetIds
     * @param array<int, Category> $scoped
     *
     * @return array<int, true>
     */
    private function includeAncestors(array $targetIds, array $scoped): array
    {
        $included = [];

        foreach ($targetIds as $targetId) {
            $currentId = $targetId;
            $path = [];

            while (isset($scoped[$currentId]) && !isset($path[$currentId])) {
                $path[$currentId] = true;
                $included[$currentId] = true;
                $parentId = (int) $scoped[$currentId]->getParent()?->getId();

                if ($parentId <= 0 || !isset($scoped[$parentId])) {
                    break;
                }

                $currentId = $parentId;
            }
        }

        return $included;
    }

    /**
     * @param array<int, Category> $scoped
     * @param array<int, true> $includedIds
     *
     * @return Category[]
     */
    private function orderTree(array $scoped, array $includedIds): array
    {
        $children = [];
        $roots = [];

        foreach ($includedIds as $id => $_included) {
            $parentId = (int) $scoped[$id]->getParent()?->getId();
            if ($parentId > 0 && $parentId !== $id && isset($includedIds[$parentId])) {
                $children[$parentId][] = $id;
            } else {
                $roots[] = $id;
            }
        }

        $sortIds = function (array &$ids) use ($scoped): void {
            usort(
                $ids,
                fn (int $left, int $right): int => $this->compare(
                    $scoped[$left],
                    $scoped[$right]
                )
            );
        };
        $sortIds($roots);
        foreach ($children as &$childIds) {
            $sortIds($childIds);
        }
        unset($childIds);

        $ordered = [];
        $visited = [];
        $visit = function (int $id) use (&$visit, &$ordered, &$visited, $children, $scoped): void {
            if (isset($visited[$id])) {
                return;
            }

            $visited[$id] = true;
            $ordered[] = $scoped[$id];
            foreach ($children[$id] ?? [] as $childId) {
                $visit($childId);
            }
        };

        foreach ($roots as $rootId) {
            $visit($rootId);
        }

        $remaining = array_values(array_diff(array_keys($includedIds), array_keys($visited)));
        $sortIds($remaining);
        foreach ($remaining as $id) {
            $visit($id);
        }

        return $ordered;
    }

    private function compare(Category $left, Category $right): int
    {
        $leftOrder = $left->getSortOrder();
        $rightOrder = $right->getSortOrder();

        if ($leftOrder !== $rightOrder) {
            if ($leftOrder === null) {
                return 1;
            }
            if ($rightOrder === null) {
                return -1;
            }

            return $leftOrder <=> $rightOrder;
        }

        $nameComparison = mb_strtolower((string) $left->getName())
            <=> mb_strtolower((string) $right->getName());

        return $nameComparison !== 0
            ? $nameComparison
            : (int) $left->getId() <=> (int) $right->getId();
    }
}
