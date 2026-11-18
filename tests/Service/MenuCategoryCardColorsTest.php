<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\Category;
use ControleOnline\Service\MenuAppearanceProperties;
use PHPUnit\Framework\TestCase;

class MenuCategoryCardColorsTest extends TestCase
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

    public function testRoutesExposeRouteCardColorsOnly(): void
    {
        self::assertSame(['textColor', 'iconColor', 'iconBackgroundColor', 'routeCardBackground', 'routeCardIcon', 'routeCardIconBackground', 'routeCardText', 'routeCardBorder', 'routeMenuBackground', 'routeMenuIcon', 'routeMenuText'], MenuAppearanceProperties::fields(true));
    }

    public function testRouteCardColorsAreAppliedAndSerialized(): void
    {
        $route = new \ControleOnline\Entity\Routes();
        $changes = MenuAppearanceProperties::changes([
            'routeCardBackground' => '#112233',
            'routeCardIcon' => '$primary',
            'routeCardIconBackground' => 'rgb(1, 2, 3)',
            'routeCardText' => '#445566',
            'routeCardBorder' => '',
            'routeMenuBackground' => '#abcdef',
            'routeMenuIcon' => '$primary',
            'routeMenuText' => '#fedcba',
        ], true);
        MenuAppearanceProperties::apply($route, $changes);

        $values = MenuAppearanceProperties::values($route, true);
        self::assertSame('#112233', $values['routeCardBackground']);
        self::assertSame('$primary', $values['routeCardIcon']);
        self::assertSame('rgb(1, 2, 3)', $values['routeCardIconBackground']);
        self::assertSame('#445566', $values['routeCardText']);
        self::assertNull($values['routeCardBorder']);
        self::assertSame('#abcdef', $values['routeMenuBackground']);
        self::assertSame('$primary', $values['routeMenuIcon']);
        self::assertSame('#fedcba', $values['routeMenuText']);
    }

    public function testInvalidRouteCardColorIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MenuAppearanceProperties::changes(['routeCardBorder' => 'nope'], true);
    }
}
