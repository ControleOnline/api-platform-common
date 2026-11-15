<?php

declare(strict_types=1);

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\{Category, Menu, People, Routes};
use ControleOnline\Repository\MenuRepository;
use ControleOnline\Service\{MenuAppearanceProperties, MenuConfigService};
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Column;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Attribute\Groups;

final class MenuAppearancePropertiesTest extends TestCase
{
    public function testAbsentFieldsPreserveOriginalColorsAndExplicitNullRestoresThemeFallback(): void
    {
        $route = (new Routes())->setColor('#123456')->setIcon('users')->setTextColor('#ffffff');
        MenuAppearanceProperties::apply($route, MenuAppearanceProperties::changes([], true));
        self::assertSame('#ffffff', $route->getTextColor());
        MenuAppearanceProperties::apply($route, MenuAppearanceProperties::changes(['textColor' => '', 'iconColor' => '$primary', 'iconBackgroundColor' => 'transparent'], true));
        self::assertNull($route->getTextColor());
        self::assertSame('$primary', $route->getIconColor());
        self::assertSame('transparent', $route->getIconBackgroundColor());
        self::assertSame('#123456', $route->getColor());
        self::assertSame('users', $route->getIcon());
    }

    public function testMalformedColorsAreRejectedInsteadOfBeingTruncatedOrInterpretedAsCss(): void
    {
        foreach (['red;display:none', 'inherit', 'currentColor', '$primary}', ['#ffffff'], 123, str_repeat('a', 51)] as $invalid) {
            try { MenuAppearanceProperties::changes(['textColor' => $invalid]);self::fail('Invalid input accepted'); }
            catch (\InvalidArgumentException) { self::assertTrue(true); }
        }
        self::assertSame(['textColor' => 'rgba(1, 2, 3, 0.5)'], MenuAppearanceProperties::changes(['textColor' => 'rgba(1, 2, 3, 0.5)']));
    }

    public function testRouteAppearanceSaveKeepsLinksStatusOrderAndExistingIconAndBackground(): void
    {
        $route = (new Routes())->setRoute('ClientsIndex')->setColor('#0284c7')->setIcon('users');
        $menu = new Menu();$menu->setRoute($route);$menu->setSortOrder(12);$menu->setEnabled(true);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::exactly(2))->method('persist');
        $manager->expects(self::once())->method('flush');
        (new MenuConfigService($manager))->updateMenu($menu, ['textColor' => '#ffffff', 'iconColor' => '$primary', 'iconBackgroundColor' => null]);
        self::assertSame('#ffffff', $route->getTextColor());
        self::assertSame('$primary', $route->getIconColor());
        self::assertSame('#0284c7', $route->getColor());self::assertSame('users', $route->getIcon());
        self::assertSame(12, $menu->getSortOrder());self::assertTrue($menu->getEnabled());
        self::assertCount(0, $menu->getLinkTypes());
    }

    public function testCategoryAndCatalogReturnNullableIndependentAppearanceFields(): void
    {
        $category = (new Category())->setColor('#020202')->setIcon('settings');
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('flush');
        $service = new MenuConfigService($manager);
        $service->updateCategory($category, ['textColor' => '#ffffff', 'iconColor' => '$primary', 'iconBackgroundColor' => '#abcdef']);
        $route = (new Routes())->setRoute('ClientsIndex')->setColor('#0284c7')->setIcon('users');
        $menu = new Menu();$menu->setMenu('Clients');$menu->setRoute($route);$menu->setCategory($category);
        $result = $service->normalizeMenu($menu);
        self::assertSame('#ffffff', $result['category']['textColor']);
        self::assertSame('$primary', $result['category']['iconColor']);
        self::assertArrayNotHasKey('iconBackgroundColor', $result['category']);
        foreach (['textColor','iconColor','iconBackgroundColor'] as $field) self::assertNull($result['route'][$field]);
        self::assertSame('#020202', $category->getColor());
    }

    public function testRuntimePayloadIncludesNewColorsAndDelegatesExistingPermissionFilters(): void
    {
        $repo = $this->getMockBuilder(MenuRepository::class)->disableOriginalConstructor()->onlyMethods(['findVisibleRowsForPeople'])->getMock();
        $repo->expects(self::once())->method('findVisibleRowsForPeople')->with(10,20,'MANAGER',false,'home')->willReturn([[
            'id'=>99,'category_id'=>3,'category_label'=>'Commercial','category_color'=>'#020202','category_icon'=>'grid',
            'category_text_color'=>'#ffffff','category_icon_color'=>'#ffff00','menu_key'=>'clients','app_type'=>'MANAGER',
            'menu_type'=>'home','menu'=>'Clients','icon'=>'users','color'=>'#0284c7','text_color'=>'#ffffff',
            'icon_color'=>'#ffff00','icon_background_color'=>'transparent','route'=>'ClientsIndex','sort_order'=>1,'module'=>2,
        ]]);
        $manager = $this->createStub(EntityManagerInterface::class);$manager->method('getRepository')->willReturn($repo);
        $person = new People();$company = new People();
        (new \ReflectionProperty($person,'id'))->setValue($person,10);(new \ReflectionProperty($company,'id'))->setValue($company,20);
        $result = (new MenuConfigService($manager))->getMenuForPeople($person,$company,'MANAGER',false,'home')['modules'][0];
        self::assertSame('#ffffff',$result['textColor']);self::assertSame('#ffff00',$result['iconColor']);
        self::assertSame('transparent',$result['menus'][0]['iconBackgroundColor']);
        self::assertSame('#0284c7',$result['menus'][0]['color']);
    }

    public function testNewMappingsAreNullableAndDoNotEnableGenericCategoryOrRouteWrites(): void
    {
        foreach ([Category::class=>['textColor','iconColor'],Routes::class=>['textColor','iconColor','iconBackgroundColor']] as $class=>$fields) {
            foreach ($fields as $field) {
                $property = new \ReflectionProperty($class,$field);
                $column = $property->getAttributes(Column::class)[0]->newInstance();
                self::assertTrue($column->nullable);self::assertSame(50,$column->length);
                self::assertSame(strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $field)),$column->name);
                self::assertSame(['menu:read'],$property->getAttributes(Groups::class)[0]->newInstance()->groups);
                self::assertNull($property->getValue(new $class()));
            }
        }
    }

    public function testRuntimeQueryIncludesAppearanceColumnsWithoutRemovingCompanyAndProfileConstraints(): void
    {
        $connection = $this->createMock(Connection::class);
        $result = $this->createStub(Result::class);$result->method('fetchAllAssociative')->willReturn([]);
        $connection->expects(self::once())->method('executeQuery')->willReturnCallback(function ($sql, $params) use ($result) {
            foreach (['category.text_color AS category_text_color','category.icon_color AS category_icon_color','routes.text_color AS text_color','routes.icon_color AS icon_color','routes.icon_background_color AS icon_background_color','people_link.company_id = :companyId','people_link.people_id = :peopleId','menu.enabled = 1'] as $part) self::assertStringContainsString($part,$sql);
            self::assertSame(20,$params['companyId']);self::assertSame(10,$params['peopleId']);
            self::assertSame('MANAGER',$params['appType']);
            return $result;
        });
        $manager = $this->createStub(EntityManagerInterface::class);$manager->method('getConnection')->willReturn($connection);
        $repo = $this->getMockBuilder(MenuRepository::class)->disableOriginalConstructor()->onlyMethods(['getEntityManager'])->getMock();
        $repo->expects(self::once())->method('getEntityManager')->willReturn($manager);
        self::assertSame([],$repo->findVisibleRowsForPeople(10,20,'MANAGER',false,'home'));
    }

    public function testMigrationOnlyAddsFiveNullableColumnsAndDoesNotDeleteExistingColors(): void
    {
        // Inspect the migration source only: neither up/down nor a migration runner is executed.
        $source = file_get_contents(dirname(__DIR__,2).'/migrations/Version20261110220000.php');
        preg_match_all('/ALTER TABLE (category|routes) ([^\']+)/',$source,$matches,PREG_SET_ORDER);
        self::assertCount(2,$matches);
        $columns=[];
        foreach ($matches as $match) {
            preg_match_all('/ADD (\w+) VARCHAR\(50\) DEFAULT NULL/',$match[2],$names);
            $columns[$match[1]]=$names[1];
        }
        self::assertSame(['text_color','icon_color'],$columns['category']);
        self::assertSame(['text_color','icon_color','icon_background_color'],$columns['routes']);
        self::assertDoesNotMatchRegularExpression('/\b(UPDATE|INSERT|DELETE|DROP|MODIFY)\b/',$source);
        self::assertStringContainsString('throwIrreversibleMigrationException',$source);
    }
}
