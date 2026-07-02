<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\Category;
use ControleOnline\Entity\Menu;
use ControleOnline\Entity\MenuLinkType;
use ControleOnline\Entity\Module;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Routes;
use ControleOnline\Repository\MenuRepository;
use ControleOnline\Service\MenuConfigService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class MenuConfigServiceTest extends TestCase
{
    public function testNormalizeMenuIncludesAppTypeRouteParamsAndLinkTypes(): void
    {
        $module = new Module();
        $module->setName('ui-manager');
        $module->setColor('#000000');
        $module->setIcon('grid');

        $route = new Routes();
        $route->setId(20);
        $route->setRoute('MenuAccessConfigPage');
        $route->setIcon('list');
        $route->setColor('#2563EB');
        $route->setModule($module);

        $category = new Category();
        $this->setEntityId($category, 10);
        $category->setName('Configuracoes');
        $category->setContext('menu');
        $category->setIcon('settings');
        $category->setColor('#64748B');

        $menu = new Menu();
        $this->setEntityId($menu, 30);
        $menu->setMenu('Menus por perfil');
        $menu->setMenuKey('menu_access');
        $menu->setAppType('manager');
        $menu->setMenuType('toolbar');
        $menu->setRouteParams(['tab' => 'roles']);
        $menu->setSortOrder(50);
        $menu->setEnabled(true);
        $menu->setRoute($route);
        $menu->setCategory($category);

        $managerLink = new MenuLinkType();
        $managerLink->setLinkType('manager');
        $menu->addLinkType($managerLink);

        $ownerLink = new MenuLinkType();
        $ownerLink->setLinkType('owner');
        $menu->addLinkType($ownerLink);

        $service = new MenuConfigService($this->createStub(EntityManagerInterface::class));
        $payload = $service->normalizeMenu($menu);

        self::assertSame('MANAGER', $payload['appType']);
        self::assertSame('toolbar', $payload['menuType']);
        self::assertSame('menu_access', $payload['menuKey']);
        self::assertSame(['tab' => 'roles'], $payload['routeParams']);
        self::assertSame('MenuAccessConfigPage', $payload['route']['route']);
        self::assertSame(['manager', 'owner'], $payload['linkTypes']);
    }

    public function testNormalizeAppTypeFallsBackToManager(): void
    {
        $service = new MenuConfigService($this->createStub(EntityManagerInterface::class));

        self::assertSame('CRM', $service->normalizeAppType('crm'));
        self::assertSame('DELIVERY', $service->normalizeAppType('delivery'));
        self::assertSame('SERVICE', $service->normalizeAppType('service'));
        self::assertSame('MANAGER', $service->normalizeAppType('unknown'));
    }

    public function testAllowedMenuLinkTypesExcludeCommercialLinks(): void
    {
        $service = new MenuConfigService($this->createStub(EntityManagerInterface::class));

        self::assertSame(
            ['employee', 'owner', 'director', 'manager', 'salesman', 'after-sales', 'courier'],
            $service->getAllowedLinkTypes()
        );
    }

    public function testGetMenuForPeopleDelegatesFiltersAndBuildsRuntimePayload(): void
    {
        $userPeople = new People();
        $this->setEntityId($userPeople, 10);

        $company = new People();
        $this->setEntityId($company, 20);

        $repository = $this
            ->getMockBuilder(MenuRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findVisibleRowsForPeople'])
            ->getMock();

        $repository
            ->expects(self::once())
            ->method('findVisibleRowsForPeople')
            ->with(10, 20, 'CRM', true)
            ->willReturn([
                [
                    'id' => 1,
                    'menu_key' => 'opportunities',
                    'app_type' => 'CRM',
                    'route_params' => null,
                    'sort_order' => 10,
                    'menu' => 'Oportunidades',
                    'category_id' => 5,
                    'category_label' => 'Comercial',
                    'category_color' => '#16A34A',
                    'category_icon' => 'users',
                    'icon' => 'target',
                    'color' => '#F59E0B',
                    'route' => 'CrmIndex',
                    'module' => 7,
                ],
            ]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('getRepository')
            ->with(Menu::class)
            ->willReturn($repository);

        $service = new MenuConfigService($manager);
        $payload = $service->getMenuForPeople($userPeople, $company, 'crm', true);

        self::assertSame('Comercial', $payload['modules'][0]['label']);
        self::assertSame('opportunities', $payload['modules'][0]['menus'][0]['menuKey']);
        self::assertSame('CRM', $payload['modules'][0]['menus'][0]['appType']);
        self::assertSame('CrmIndex', $payload['modules'][0]['menus'][0]['route']);
        self::assertSame([], $payload['modules'][0]['menus'][0]['routeParams']);
    }

    public function testGetMenuForPeopleCanFilterByMenuType(): void
    {
        $userPeople = new People();
        $this->setEntityId($userPeople, 10);

        $company = new People();
        $this->setEntityId($company, 20);

        $repository = $this
            ->getMockBuilder(MenuRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findVisibleRowsForPeople'])
            ->getMock();

        $repository
            ->expects(self::once())
            ->method('findVisibleRowsForPeople')
            ->with(10, 20, 'DELIVERY', false, 'toolbar')
            ->willReturn([
                [
                    'id' => 2,
                    'menu_key' => 'orders',
                    'app_type' => 'DELIVERY',
                    'menu_type' => 'toolbar',
                    'route_params' => null,
                    'sort_order' => 10,
                    'menu' => 'Pedidos',
                    'category_id' => 6,
                    'category_label' => 'Operacao',
                    'category_color' => '#0EA5E9',
                    'category_icon' => 'shopping-bag',
                    'icon' => 'shopping-bag',
                    'color' => '#0EA5E9',
                    'route' => 'DeliveryOrdersPage',
                    'module' => 8,
                ],
            ]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('getRepository')
            ->with(Menu::class)
            ->willReturn($repository);

        $service = new MenuConfigService($manager);
        $payload = $service->getMenuForPeople($userPeople, $company, 'delivery', false, 'toolbar');

        self::assertSame('toolbar', $payload['modules'][0]['menus'][0]['menuType']);
        self::assertSame('DeliveryOrdersPage', $payload['modules'][0]['menus'][0]['route']);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }
}
