<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\Category;
use ControleOnline\Service\MenuAppearanceProperties;
use PHPUnit\Framework\TestCase;

class MenuAppearancePropertiesTest extends TestCase
{
    public function testCategoryExposesCardColors(): void
    {
        self::assertSame(
            ['textColor', 'iconColor', 'categoryCardBackground', 'categoryCardText', 'categoryCardMenu'],
            MenuAppearanceProperties::fields()
        );
    }

    public function testCategoryCardColorsAreAppliedAndSerialized(): void
    {
        $category = new Category();
        $changes = MenuAppearanceProperties::changes([
            'categoryCardBackground' => '#112233',
            'categoryCardText' => '#445566',
            'categoryCardMenu' => '',
        ]);
        MenuAppearanceProperties::apply($category, $changes);

        $values = MenuAppearanceProperties::values($category);
        self::assertSame('#112233', $values['categoryCardBackground']);
        self::assertSame('#445566', $values['categoryCardText']);
        self::assertNull($values['categoryCardMenu']);
    }

    public function testInvalidCardColorIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MenuAppearanceProperties::changes(['categoryCardText' => 'not-a-color']);
    }

    public function testRoutesDoNotExposeCategoryCardColors(): void
    {
        self::assertSame(['textColor', 'iconColor', 'iconBackgroundColor'], MenuAppearanceProperties::fields(true));
    }
}
