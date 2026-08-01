<?php

namespace ControleOnline\Tests\Entity;

use ControleOnline\Entity\Category;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testSortOrderIsNullableAndAcceptsZero(): void
    {
        $category = new Category();

        self::assertNull($category->getSortOrder());
        self::assertSame($category, $category->setSortOrder(0));
        self::assertSame(0, $category->getSortOrder());
        self::assertSame($category, $category->setSortOrder(null));
        self::assertNull($category->getSortOrder());
    }
}
