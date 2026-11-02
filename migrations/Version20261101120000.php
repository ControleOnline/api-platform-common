<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Registers the Manager configurator shortcuts as dynamic menus (menu_type = configurator),
 * so they can be enabled/disabled per profile in menu-access-config-page.
 *
 * Existing "home" menus are left untouched. The enabled flag and link types of a new
 * configurator menu are copied from the MANAGER/home menu of the same route when it exists,
 * so a shortcut that is already disabled stays disabled.
 */
final class Version20261101120000 extends TenantAwareMigration
{
    private const APP_TYPE = 'MANAGER';
    private const MENU_TYPE = 'configurator';
    private const CATEGORY_NAME = 'Configuracoes';
    private const MODULE_NAME = 'ui-manager';
    private const DEFAULT_LINK_TYPES = ['owner', 'director', 'manager'];

    /** @return array<int, array<string, mixed>> */
    private function items(): array
    {
        return [
            ['route' => 'GeneralSettings', 'key' => 'configurator_general_settings', 'menu' => 'Configurações gerais', 'icon' => 'settings', 'color' => '#64748B', 'sort' => 10],
            ['route' => 'GenericLogPage', 'key' => 'configurator_logs', 'menu' => 'Logs', 'icon' => 'activity', 'color' => '#0EA5E9', 'sort' => 20],
            ['route' => 'ConnectionsPage', 'key' => 'configurator_connections', 'menu' => 'Conexões', 'icon' => 'radio', 'color' => '#0284C7', 'sort' => 30],
            ['route' => 'IntegrationsPage', 'key' => 'configurator_integrations', 'menu' => 'Integrações', 'icon' => 'link', 'color' => '#7C3AED', 'sort' => 40],
            ['route' => 'ProductShowcasesPage', 'key' => 'configurator_product_showcases', 'menu' => 'Vitrines de preços', 'icon' => 'grid', 'color' => '#0F766E', 'sort' => 50],
            ['route' => 'CronJobsPage', 'key' => 'configurator_cron_jobs', 'menu' => 'Cron jobs', 'icon' => 'clock', 'color' => '#F59E0B', 'sort' => 60],
            ['route' => 'TranslationsReviewPage', 'key' => 'configurator_translations', 'menu' => 'Traduções', 'icon' => 'type', 'color' => '#0F766E', 'sort' => 70],
            ['route' => 'ThemeManagerPage', 'key' => 'configurator_themes', 'menu' => 'Temas', 'icon' => 'droplet', 'color' => '#7C3AED', 'sort' => 80],
        ];
    }

    public function getDescription(): string
    {
        return 'Register the Manager configurator shortcuts as dynamic configurator menus.';
    }

    public function up(Schema $schema): void
    {
        $domain = $this->resolveExecutionDomain();

        // Reuse the main company "Configuracoes" menu category, creating it only when absent.
        $this->addSql(
            "INSERT INTO category (name, icon, color, context, parent_id, company_id)
             SELECT :category_name, 'settings', '\$primary', 'menu', NULL, people_domain.people_id
             FROM people_domain
             WHERE people_domain.domain = :domain
               AND NOT EXISTS (
                   SELECT 1 FROM category existing
                   WHERE existing.context = 'menu'
                     AND existing.company_id = people_domain.people_id
                     AND existing.name LIKE 'Configura%'
               )
             LIMIT 1",
            ['category_name' => self::CATEGORY_NAME, 'domain' => $domain]
        );

        foreach ($this->items() as $item) {
            $route = [
                'module_name' => self::MODULE_NAME,
                'route' => $item['route'],
                'color' => $item['color'],
                'icon' => $item['icon'],
            ];

            $this->addSql(
                'INSERT INTO routes (module_id, route, color, icon)
                 SELECT module.id, :route, :color, :icon
                 FROM module
                 WHERE module.name = :module_name
                   AND NOT EXISTS (SELECT 1 FROM routes WHERE route = :route)
                 LIMIT 1',
                $route
            );

            $this->addSql(
                'INSERT INTO menu (category_id, menu, route_id, menu_key, app_type, menu_type, route_params, sort_order, enabled)
                 SELECT category.id, :menu, routes.id, :menu_key, :app_type, :menu_type, NULL, :sort_order,
                        COALESCE(
                            (SELECT home.enabled FROM menu home
                             WHERE home.route_id = routes.id
                               AND home.app_type = :app_type
                               AND home.menu_type = \'home\'
                             ORDER BY home.id LIMIT 1),
                            1
                        )
                 FROM category
                 INNER JOIN people_domain ON people_domain.people_id = category.company_id
                 INNER JOIN routes ON routes.route = :route
                 WHERE people_domain.domain = :domain
                   AND category.context = \'menu\'
                   AND category.name LIKE \'Configura%\'
                   AND NOT EXISTS (
                       SELECT 1 FROM menu existing
                       WHERE existing.app_type = :app_type
                         AND existing.menu_type = :menu_type
                         AND existing.menu_key = :menu_key
                   )
                 ORDER BY category.id
                 LIMIT 1',
                [
                    'menu' => $item['menu'],
                    'menu_key' => $item['key'],
                    'app_type' => self::APP_TYPE,
                    'menu_type' => self::MENU_TYPE,
                    'sort_order' => $item['sort'],
                    'route' => $item['route'],
                    'domain' => $domain,
                ]
            );

            // Same profiles as the existing home menu of this route.
            $this->addSql(
                'INSERT INTO menu_link_type (menu_id, link_type)
                 SELECT configurator.id, home_link.link_type
                 FROM menu configurator
                 INNER JOIN routes ON routes.id = configurator.route_id
                 INNER JOIN menu home
                    ON home.route_id = routes.id
                   AND home.app_type = configurator.app_type
                   AND home.menu_type = \'home\'
                 INNER JOIN menu_link_type home_link ON home_link.menu_id = home.id
                 WHERE configurator.app_type = :app_type
                   AND configurator.menu_type = :menu_type
                   AND configurator.menu_key = :menu_key
                   AND NOT EXISTS (
                       SELECT 1 FROM menu_link_type existing_link
                       WHERE existing_link.menu_id = configurator.id
                         AND existing_link.link_type = home_link.link_type
                   )',
                ['app_type' => self::APP_TYPE, 'menu_type' => self::MENU_TYPE, 'menu_key' => $item['key']]
            );

            // Routes without a home menu fall back to the default Manager profiles.
            foreach (self::DEFAULT_LINK_TYPES as $linkType) {
                $this->addSql(
                    'INSERT INTO menu_link_type (menu_id, link_type)
                     SELECT configurator.id, :link_type
                     FROM menu configurator
                     INNER JOIN routes ON routes.id = configurator.route_id
                     WHERE configurator.app_type = :app_type
                       AND configurator.menu_type = :menu_type
                       AND configurator.menu_key = :menu_key
                       AND NOT EXISTS (
                           SELECT 1
                           FROM menu home
                           INNER JOIN menu_link_type any_home_link ON any_home_link.menu_id = home.id
                           WHERE home.route_id = routes.id
                             AND home.app_type = configurator.app_type
                             AND home.menu_type = \'home\'
                       )
                       AND NOT EXISTS (
                           SELECT 1 FROM menu_link_type existing_link
                           WHERE existing_link.menu_id = configurator.id
                             AND existing_link.link_type = :link_type
                       )',
                    [
                        'link_type' => $linkType,
                        'app_type' => self::APP_TYPE,
                        'menu_type' => self::MENU_TYPE,
                        'menu_key' => $item['key'],
                    ]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach ($this->items() as $item) {
            $this->addSql(
                'DELETE menu_link_type FROM menu_link_type
                 INNER JOIN menu ON menu.id = menu_link_type.menu_id
                 WHERE menu.app_type = :app_type AND menu.menu_type = :menu_type AND menu.menu_key = :menu_key',
                ['app_type' => self::APP_TYPE, 'menu_type' => self::MENU_TYPE, 'menu_key' => $item['key']]
            );
            $this->addSql(
                'DELETE FROM menu WHERE app_type = :app_type AND menu_type = :menu_type AND menu_key = :menu_key',
                ['app_type' => self::APP_TYPE, 'menu_type' => self::MENU_TYPE, 'menu_key' => $item['key']]
            );
        }
    }
}
