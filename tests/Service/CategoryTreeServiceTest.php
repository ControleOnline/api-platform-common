<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\Category;
use ControleOnline\Entity\CategoryFile;
use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use ControleOnline\Service\CategoryTreeService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class CategoryTreeServiceTest extends TestCase
{
    private CategoryTreeService $service;
    private People $company;

    protected function setUp(): void
    {
        $this->service = new CategoryTreeService();
        $this->company = $this->company(10);
    }

    public function testProjectedTreeIncludesArbitraryAncestorsAndOrdersEverySiblingLevel(): void
    {
        $root = $this->category(1, 'Cardápio', 1);
        $alpha = $this->category(2, 'Alpha', null, $root);
        $beta = $this->category(3, 'Beta', null, $root);
        $priority = $this->category(4, 'Prioridade', 0, $root);
        $levelThree = $this->category(5, 'Nível 3', 0, $beta);
        $levelFour = $this->category(6, 'Nível 4', 0, $levelThree);
        $levelFive = $this->category(7, 'Nível 5', 0, $levelFour);

        $result = $this->service->build(
            [$levelFive, $alpha, $root, $levelFour, $priority, $beta, $levelThree],
            10,
            'products',
            [7, 2, 3, 4],
            '',
            false,
            1,
            20
        );

        self::assertSame(7, $result['totalItems']);
        self::assertSame(
            [1, 4, 2, 3, 5, 6, 7],
            array_map(static fn (Category $category): int => (int) $category->getId(), $result['items'])
        );
    }

    public function testNameAndIdBreakEqualSortOrderTiesDeterministically(): void
    {
        $sameNameHigherId = $this->category(3, 'Bebidas', 2);
        $sameNameLowerId = $this->category(2, 'Bebidas', 2);
        $alphabeticallyFirst = $this->category(4, 'Acompanhamentos', 2);

        $result = $this->service->build(
            [$sameNameHigherId, $sameNameLowerId, $alphabeticallyFirst],
            10,
            'products',
            null,
            '',
            false,
            1,
            20
        );

        self::assertSame(
            [4, 2, 3],
            array_map(static fn (Category $category): int => (int) $category->getId(), $result['items'])
        );
    }

    public function testSearchAndImageFiltersKeepRequiredAncestors(): void
    {
        $root = $this->category(1, 'Cardápio', null);
        $matching = $this->category(2, 'Pizza especial', null, $root);
        $matching->getCategoryFiles()->add(
            (new CategoryFile())->setCategory($matching)->setFile(
                (new File())->setFileType('image')
            )
        );
        $withoutImage = $this->category(3, 'Pizza simples', null, $root);

        $result = $this->service->build(
            [$withoutImage, $matching, $root],
            10,
            'products',
            [2, 3],
            'PIZZA',
            true,
            1,
            20
        );

        self::assertSame(
            [1, 2],
            array_map(static fn (Category $category): int => (int) $category->getId(), $result['items'])
        );
    }

    public function testPaginationUsesStableTreeOrderAndReportsFullProjectedTotal(): void
    {
        $categories = [
            $this->category(1, 'A', 1),
            $this->category(2, 'B', 2),
            $this->category(3, 'C', 3),
            $this->category(4, 'D', 4),
            $this->category(5, 'E', null),
        ];

        $result = $this->service->build(
            array_reverse($categories),
            10,
            'products',
            null,
            '',
            false,
            2,
            2
        );

        self::assertSame(5, $result['totalItems']);
        self::assertSame(2, $result['page']);
        self::assertSame(2, $result['itemsPerPage']);
        self::assertSame(
            [3, 4],
            array_map(static fn (Category $category): int => (int) $category->getId(), $result['items'])
        );
    }

    public function testExplicitEmptyProjectionDoesNotFallBackToAllCategories(): void
    {
        $category = $this->category(1, 'Categoria', 1);

        $empty = $this->service->build(
            [$category],
            10,
            'products',
            [],
            '',
            false,
            1,
            20
        );
        $compatible = $this->service->build(
            [$category],
            10,
            'products',
            null,
            '',
            false,
            1,
            20
        );

        self::assertSame([], $empty['items']);
        self::assertSame(0, $empty['totalItems']);
        self::assertSame([$category], $compatible['items']);
    }

    public function testTenantAndContextAreEnforcedEvenForProjectedIds(): void
    {
        $allowed = $this->category(1, 'Permitida', 1);
        $foreign = $this->category(2, 'Outro tenant', 1, null, $this->company(99));
        $wrongContext = $this->category(3, 'Outro contexto', 1, null, $this->company, 'menu');

        $result = $this->service->build(
            [$foreign, $wrongContext, $allowed],
            10,
            'products',
            [1, 2, 3],
            '',
            false,
            1,
            20
        );

        self::assertSame([$allowed], $result['items']);
        self::assertSame(1, $result['totalItems']);
    }

    private function company(int $id): People
    {
        $company = $this->createMock(People::class);
        $company->method('getId')->willReturn($id);

        return $company;
    }

    private function category(
        int $id,
        string $name,
        ?int $sortOrder,
        ?Category $parent = null,
        ?People $company = null,
        string $context = 'products'
    ): Category {
        $category = (new Category())
            ->setName($name)
            ->setContext($context)
            ->setCompany($company ?? $this->company)
            ->setSortOrder($sortOrder)
            ->setParent($parent);
        (new ReflectionProperty($category, 'id'))->setValue($category, $id);

        return $category;
    }
}
