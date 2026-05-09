<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Category;
use ControleOnline\Entity\Menu;
use ControleOnline\Entity\MenuLinkType;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\Routes;
use ControleOnline\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;

class MenuConfigService
{
    public const APP_TYPES = ['MANAGER', 'CRM', 'POS', 'DELIVERY', 'PPC', 'SHOP'];

    public function __construct(private EntityManagerInterface $manager) {}

    public function getAllowedLinkTypes(): array
    {
        return PeopleLink::HUMAN_LINK;
    }

    public function normalizeAppType(?string $appType): string
    {
        $normalized = strtoupper(trim((string) $appType));

        return in_array($normalized, self::APP_TYPES, true) ? $normalized : 'MANAGER';
    }

    public function getPaginatedMenus(?string $appType, int $page, int $itemsPerPage): array
    {
        $page = max(1, $page);
        $itemsPerPage = max(1, min(200, $itemsPerPage));
        $normalizedAppType = $appType !== null && trim($appType) !== ''
            ? $this->normalizeAppType($appType)
            : null;

        $countQb = $this->manager->createQueryBuilder()
            ->select('COUNT(DISTINCT menu.id)')
            ->from(Menu::class, 'menu');

        $idsQb = $this->manager->createQueryBuilder()
            ->select('menu.id')
            ->from(Menu::class, 'menu')
            ->leftJoin('menu.category', 'category')
            ->addOrderBy('menu.appType', 'ASC')
            ->addOrderBy('category.name', 'ASC')
            ->addOrderBy('menu.sortOrder', 'ASC')
            ->addOrderBy('menu.menu', 'ASC')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage);

        if ($normalizedAppType !== null) {
            $countQb->andWhere('menu.appType = :appType')->setParameter('appType', $normalizedAppType);
            $idsQb->andWhere('menu.appType = :appType')->setParameter('appType', $normalizedAppType);
        }

        $totalItems = (int) $countQb->getQuery()->getSingleScalarResult();
        $ids = array_map(
            static fn(array $row): int => (int) $row['id'],
            $idsQb->getQuery()->getArrayResult()
        );

        if ($ids === []) {
            return ['member' => [], 'totalItems' => $totalItems];
        }

        $menus = $this->manager->createQueryBuilder()
            ->select('menu', 'category', 'route', 'module', 'linkType')
            ->from(Menu::class, 'menu')
            ->leftJoin('menu.category', 'category')
            ->leftJoin('menu.route', 'route')
            ->leftJoin('route.module', 'module')
            ->leftJoin('menu.linkTypes', 'linkType')
            ->andWhere('menu.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $menusById = [];
        foreach ($menus as $menu) {
            if ($menu instanceof Menu) {
                $menusById[(int) $menu->getId()] = $menu;
            }
        }

        $member = [];
        foreach ($ids as $id) {
            if (isset($menusById[$id])) {
                $member[] = $this->normalizeMenu($menusById[$id]);
            }
        }

        return ['member' => $member, 'totalItems' => $totalItems];
    }

    public function getMenuForPeople(People $userPeople, People $company, string $appType, bool $isSuper): array
    {
        $repository = $this->manager->getRepository(Menu::class);
        if (!$repository instanceof MenuRepository) {
            return [];
        }

        return $this->buildMenuPayload(
            $repository->findVisibleRowsForPeople(
                (int) $userPeople->getId(),
                (int) $company->getId(),
                $this->normalizeAppType($appType),
                $isSuper
            )
        );
    }

    public function updateMenu(Menu $menu, array $payload): Menu
    {
        if (array_key_exists('menu', $payload)) {
            $menu->setMenu(trim((string) $payload['menu']));
        }

        if (array_key_exists('menuKey', $payload) || array_key_exists('menu_key', $payload)) {
            $menu->setMenuKey(trim((string) ($payload['menuKey'] ?? $payload['menu_key'])));
        }

        if (array_key_exists('appType', $payload) || array_key_exists('app_type', $payload)) {
            $menu->setAppType($this->normalizeAppType((string) ($payload['appType'] ?? $payload['app_type'])));
        }

        if (array_key_exists('enabled', $payload)) {
            $menu->setEnabled((bool) $payload['enabled']);
        }

        if (array_key_exists('sortOrder', $payload) || array_key_exists('sort_order', $payload)) {
            $menu->setSortOrder((int) ($payload['sortOrder'] ?? $payload['sort_order']));
        }

        if (array_key_exists('routeParams', $payload) || array_key_exists('route_params', $payload)) {
            $menu->setRouteParams($this->normalizeRouteParams($payload['routeParams'] ?? $payload['route_params']));
        }

        if (array_key_exists('route', $payload)) {
            $route = $this->resolveEntity(Routes::class, $payload['route']);
            if ($route instanceof Routes) {
                $menu->setRoute($route);
            }
        }

        if (array_key_exists('category', $payload)) {
            $category = $this->resolveEntity(Category::class, $payload['category']);
            if ($category instanceof Category) {
                $menu->setCategory($category);
            }
        }

        if (array_key_exists('linkTypes', $payload) || array_key_exists('link_types', $payload)) {
            $this->syncLinkTypes($menu, $payload['linkTypes'] ?? $payload['link_types']);
        }

        $this->manager->persist($menu);
        $this->manager->flush();

        return $menu;
    }

    public function normalizeMenu(Menu $menu): array
    {
        $route = $menu->getRoute();
        $category = $menu->getCategory();
        $module = $route instanceof Routes ? $route->getModule() : null;

        return [
            '@id' => '/menu-config/' . $menu->getId(),
            'id' => $menu->getId(),
            'menu' => $menu->getMenu(),
            'label' => $menu->getMenu(),
            'menuKey' => $menu->getMenuKey(),
            'appType' => $menu->getAppType(),
            'routeParams' => $menu->getRouteParams() ?? [],
            'sortOrder' => $menu->getSortOrder(),
            'enabled' => $menu->getEnabled(),
            'route' => $route instanceof Routes ? [
                '@id' => '/routes/' . $route->getId(),
                'id' => $route->getId(),
                'route' => $route->getRoute(),
                'icon' => $route->getIcon(),
                'color' => $route->getColor(),
                'module' => is_object($module) && method_exists($module, 'getName')
                    ? $module->getName()
                    : null,
            ] : null,
            'category' => $category instanceof Category ? [
                '@id' => '/categories/' . $category->getId(),
                'id' => $category->getId(),
                'name' => $category->getName(),
                'icon' => $category->getIcon(),
                'color' => $category->getColor(),
            ] : null,
            'linkTypes' => $this->normalizeMenuLinkTypes($menu),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private function buildMenuPayload(array $rows): array
    {
        $modules = [];

        foreach ($rows as $menu) {
            $categoryId = (int) $menu['category_id'];
            $routeParams = [];

            if (!empty($menu['route_params'])) {
                $decodedRouteParams = json_decode((string) $menu['route_params'], true);
                $routeParams = is_array($decodedRouteParams) ? $decodedRouteParams : [];
            }

            $modules[$categoryId]['id'] = $categoryId;
            $modules[$categoryId]['label'] = $menu['category_label'];
            $modules[$categoryId]['color'] = $menu['category_color'];
            $modules[$categoryId]['icon'] = $menu['category_icon'];
            $modules[$categoryId]['menus'][] = [
                'id' => (int) $menu['id'],
                'menuKey' => $menu['menu_key'],
                'appType' => $menu['app_type'],
                'label' => $menu['menu'],
                'icon' => $menu['icon'],
                'color' => $menu['color'],
                'route' => $menu['route'],
                'routeParams' => $routeParams,
                'sortOrder' => (int) $menu['sort_order'],
                'module' => '/modules/' . $menu['module'],
            ];
        }

        return ['modules' => array_values($modules)];
    }

    private function syncLinkTypes(Menu $menu, mixed $rawLinkTypes): void
    {
        $allowedLinkTypes = $this->getAllowedLinkTypes();
        $nextLinkTypes = array_values(array_unique(array_filter(array_map(
            static fn($linkType): string => trim(strtolower((string) $linkType)),
            is_array($rawLinkTypes) ? $rawLinkTypes : []
        ), static fn(string $linkType): bool => in_array($linkType, $allowedLinkTypes, true))));

        $currentLinks = [];
        foreach ($menu->getLinkTypes() as $linkType) {
            if ($linkType instanceof MenuLinkType) {
                $currentLinks[$linkType->getLinkType()] = $linkType;
            }
        }

        foreach ($currentLinks as $linkType => $linkEntity) {
            if (!in_array($linkType, $nextLinkTypes, true)) {
                $menu->removeLinkType($linkEntity);
                $this->manager->remove($linkEntity);
            }
        }

        foreach ($nextLinkTypes as $linkType) {
            if (isset($currentLinks[$linkType])) {
                continue;
            }

            $linkEntity = new MenuLinkType();
            $linkEntity->setLinkType($linkType);
            $menu->addLinkType($linkEntity);
            $this->manager->persist($linkEntity);
        }
    }

    private function normalizeMenuLinkTypes(Menu $menu): array
    {
        $allowedLinkTypes = $this->getAllowedLinkTypes();
        $linkTypes = [];
        foreach ($menu->getLinkTypes() as $linkType) {
            if (
                $linkType instanceof MenuLinkType
                && in_array($linkType->getLinkType(), $allowedLinkTypes, true)
            ) {
                $linkTypes[] = $linkType->getLinkType();
            }
        }

        sort($linkTypes);

        return $linkTypes;
    }

    private function normalizeRouteParams(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function resolveEntity(string $className, mixed $value): ?object
    {
        $id = $this->extractId($value);

        return $id > 0 ? $this->manager->getRepository($className)->find($id) : null;
    }

    private function extractId(mixed $value): int
    {
        if (is_object($value) && method_exists($value, 'getId')) {
            return (int) $value->getId();
        }

        if (is_array($value)) {
            return $this->extractId($value['id'] ?? $value['@id'] ?? 0);
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value) && preg_match('/(\d+)\s*$/', $value, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
