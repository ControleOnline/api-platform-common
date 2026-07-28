<?php

declare(strict_types=1);

namespace DoctrineMigrations\ZTenantSeed;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260728161000 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Seed my companies menu entry for tenant users.';
    }

    public function up(Schema $schema): void
    {
        $domain = $this->resolveExecutionDomain();

        $this->addSql(
            'INSERT INTO module (name, color, icon, description)
             VALUES (:name, :color, :icon, :description)
             ON DUPLICATE KEY UPDATE
                color = VALUES(color),
                icon = VALUES(icon),
                description = VALUES(description)',
            [
                'name' => 'ui-manager',
                'color' => '#64748B',
                'icon' => 'settings',
                'description' => 'Modulo administrativo do app.',
            ]
        );

        $this->addSql(
            'INSERT INTO category (name, context, company_id, icon, color)
             SELECT :name, :context, people_domain.people_id, :icon, :color
             FROM people_domain
             WHERE people_domain.domain = :domain
               AND NOT EXISTS (
                   SELECT 1
                   FROM category
                   WHERE company_id = people_domain.people_id
                     AND context = :context
                     AND name = :name
               )',
            [
                'domain' => $domain,
                'name' => 'Configurações',
                'context' => 'menu',
                'icon' => 'settings',
                'color' => '#757575',
            ]
        );

        $this->addSql(
            'INSERT INTO routes (module_id, route, color, icon)
             SELECT module.id, :route, :color, :icon
             FROM module
             WHERE module.name = :module_name
               AND NOT EXISTS (
                   SELECT 1 FROM routes WHERE route = :route
               )
             LIMIT 1',
            [
                'module_name' => 'ui-manager',
                'route' => 'MyCompaniesPage',
                'color' => '#6B3924',
                'icon' => 'briefcase',
            ]
        );

        $this->addSql(
            'UPDATE routes
             INNER JOIN module ON module.name = :module_name
             SET routes.module_id = module.id,
                 routes.color = :color,
                 routes.icon = :icon
             WHERE routes.route = :route',
            [
                'module_name' => 'ui-manager',
                'route' => 'MyCompaniesPage',
                'color' => '#6B3924',
                'icon' => 'briefcase',
            ]
        );

        foreach (['ADMIN' => 8, 'MANAGER' => 8] as $appType => $sortOrder) {
            $params = [
                'domain' => $domain,
                'category_context' => 'menu',
                'category_name' => 'Configurações',
                'route' => 'MyCompaniesPage',
                'menu' => 'Minhas empresas',
                'menu_key' => 'my_companies',
                'app_type' => $appType,
                'menu_type' => 'home',
                'sort_order' => $sortOrder,
            ];

            $this->addSql(
                'INSERT INTO menu (category_id, menu, route_id, menu_key, app_type, menu_type, route_params, sort_order, enabled)
                 SELECT category.id, :menu, routes.id, :menu_key, :app_type, :menu_type, NULL, :sort_order, 1
                 FROM category
                 INNER JOIN people_domain ON people_domain.people_id = category.company_id
                 INNER JOIN routes ON routes.route = :route
                 WHERE people_domain.domain = :domain
                   AND category.context = :category_context
                   AND category.name = :category_name
                   AND NOT EXISTS (
                       SELECT 1
                       FROM menu
                       WHERE app_type = :app_type
                         AND menu_type = :menu_type
                         AND menu_key = :menu_key
                   )
                 LIMIT 1',
                $params
            );

            $this->addSql(
                'UPDATE menu
                 INNER JOIN category
                    ON category.context = :category_context
                   AND category.name = :category_name
                 INNER JOIN people_domain
                    ON people_domain.people_id = category.company_id
                   AND people_domain.domain = :domain
                 INNER JOIN routes ON routes.route = :route
                 SET menu.category_id = category.id,
                     menu.menu = :menu,
                     menu.route_id = routes.id,
                     menu.route_params = NULL,
                     menu.sort_order = :sort_order,
                     menu.enabled = 1
                 WHERE menu.app_type = :app_type
                   AND menu.menu_type = :menu_type
                   AND menu.menu_key = :menu_key',
                $params
            );

            foreach (['owner', 'director', 'manager', 'admin'] as $linkType) {
                $this->addSql(
                    'INSERT INTO menu_link_type (menu_id, link_type)
                     SELECT menu.id, :link_type
                     FROM menu
                     WHERE menu.app_type = :app_type
                       AND menu.menu_type = :menu_type
                       AND menu.menu_key = :menu_key
                       AND NOT EXISTS (
                           SELECT 1
                           FROM menu_link_type existing_link
                           WHERE existing_link.menu_id = menu.id
                             AND existing_link.link_type = :link_type
                       )',
                    [
                        'link_type' => $linkType,
                        'app_type' => $appType,
                        'menu_type' => 'home',
                        'menu_key' => 'my_companies',
                    ]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        return;
    }
}
