<?php

declare(strict_types=1);

namespace DoctrineMigrations\ZTenantSeed;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260728090000 extends TenantAwareMigration
{
    private const DEFAULT_LANGUAGE = 'pt-br';
    private const JAGUNCOS_DOMAIN = 'erp.jaguncos.com.br';
    private const JAGUNCOS_COMPANY_NAME = 'Jagunços';
    private const MENU_CATEGORY_NAME = 'Configuracoes';
    private const MENU_CATEGORY_CONTEXT = 'menu';

    public function getDescription(): string
    {
        return 'Seed the minimal tenant identity and operational catalogs after schema migrations.';
    }

    public function up(Schema $schema): void
    {
        $domain = $this->resolveExecutionDomain();
        $companyName = $this->resolveCompanyName($domain);

        $this->seedLanguages();
        $this->seedMainCompany($domain, $companyName);
        $this->seedPeopleDomain($domain, $companyName);

        $this->seedModules();
        $this->seedMenuCategory($domain);
        $this->seedRoutes();
        $this->seedMenus($domain);
        $this->seedMenuLinkTypes();
        $this->seedMediaTypes();
        $this->seedTimezones();
        $this->seedMaintenanceConfig($domain);
    }

    public function down(Schema $schema): void
    {
        return;
    }

    private function resolveCompanyName(string $domain): string
    {
        return $domain === self::JAGUNCOS_DOMAIN ? self::JAGUNCOS_COMPANY_NAME : $domain;
    }

    private function seedLanguages(): void
    {
        $this->addSql(
            'INSERT INTO `language` (`language`, `locked`)
             SELECT :language, 0
             FROM DUAL
             WHERE NOT EXISTS (
                 SELECT 1 FROM `language` WHERE `language` = :language
             )',
            ['language' => self::DEFAULT_LANGUAGE]
        );
    }

    private function seedMainCompany(string $domain, string $companyName): void
    {
        $this->addSql(
            'INSERT INTO people (
                 name,
                 alias,
                 enable,
                 people_type,
                 language_id,
                 foundation_date,
                 other_informations
             )
             SELECT
                 :name,
                 :alias,
                 1,
                 :people_type,
                 lang.id,
                 NOW(),
                 :other_informations
             FROM `language` lang
             WHERE lang.`language` = :language
               AND NOT EXISTS (
                   SELECT 1 FROM people_domain WHERE domain = :domain
               )
               AND NOT EXISTS (
                   SELECT 1 FROM people WHERE alias = :alias AND people_type = :people_type
               )
             LIMIT 1',
            [
                'name' => $companyName,
                'alias' => $companyName,
                'people_type' => 'J',
                'language' => self::DEFAULT_LANGUAGE,
                'domain' => $domain,
                'other_informations' => json_encode(
                    ['source' => 'tenant-seed', 'domain' => $domain],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
            ]
        );
    }

    private function seedPeopleDomain(string $domain, string $companyName): void
    {
        $this->addSql(
            'INSERT INTO people_domain (people_id, domain, theme_id, domain_type)
             SELECT people.id, :domain, NULL, :domain_type
             FROM people
             WHERE people.alias = :alias
               AND people.people_type = :people_type
               AND NOT EXISTS (
                   SELECT 1 FROM people_domain WHERE domain = :domain
               )
             ORDER BY people.id
             LIMIT 1',
            [
                'domain' => $domain,
                'domain_type' => 'ERP',
                'alias' => $companyName,
                'people_type' => 'J',
            ]
        );
    }

    private function seedModules(): void
    {
        foreach ($this->getModulesSeed() as $module) {
            $this->addSql(
                'INSERT INTO module (name, color, icon, description)
                 SELECT :name, :color, :icon, :description
                 FROM DUAL
                 WHERE NOT EXISTS (
                     SELECT 1 FROM module WHERE name = :name
                 )',
                $module
            );
        }
    }

    private function seedMenuCategory(string $domain): void
    {
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
                'name' => self::MENU_CATEGORY_NAME,
                'context' => self::MENU_CATEGORY_CONTEXT,
                'domain' => $domain,
                'icon' => 'settings',
                'color' => '#64748B',
            ]
        );
    }

    private function seedRoutes(): void
    {
        foreach ($this->getRoutesSeed() as $route) {
            $this->addSql(
                'INSERT INTO routes (module_id, route, color, icon)
                 SELECT module.id, :route, :color, :icon
                 FROM module
                 WHERE module.name = :module_name
                   AND NOT EXISTS (
                       SELECT 1 FROM routes WHERE route = :route
                   )
                 LIMIT 1',
                $route
            );

            $this->addSql(
                'UPDATE routes
                 INNER JOIN module ON module.name = :module_name
                 SET routes.module_id = module.id,
                     routes.color = :color,
                     routes.icon = :icon
                 WHERE routes.route = :route',
                $route
            );
        }
    }

    private function seedMenus(string $domain): void
    {
        foreach ($this->getMenusSeed() as $menu) {
            $params = [
                'domain' => $domain,
                'category_context' => self::MENU_CATEGORY_CONTEXT,
                'category_name' => self::MENU_CATEGORY_NAME,
                ...$menu,
            ];

            $this->addSql(
                'INSERT INTO menu (category_id, menu, route_id, menu_key, app_type, menu_type, sort_order, enabled)
                 SELECT category.id, :menu, routes.id, :menu_key, :app_type, :menu_type, :sort_order, 1
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
                     menu.sort_order = :sort_order,
                     menu.enabled = 1
                 WHERE menu.app_type = :app_type
                   AND menu.menu_type = :menu_type
                   AND menu.menu_key = :menu_key',
                $params
            );
        }
    }

    private function seedMenuLinkTypes(): void
    {
        foreach (['owner', 'director', 'manager'] as $linkType) {
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
                    'app_type' => 'MANAGER',
                    'menu_type' => 'home',
                    'menu_key' => 'product_showcases',
                ]
            );
        }
    }

    private function seedMediaTypes(): void
    {
        foreach ($this->getMediaTypesSeed() as $mediaType) {
            $this->addSql(
                'INSERT INTO media_types (type, people_type)
                 VALUES (:type, :people_type)
                 ON DUPLICATE KEY UPDATE people_type = VALUES(people_type)',
                $mediaType
            );
        }
    }

    private function seedTimezones(): void
    {
        foreach ($this->getTimezonesSeed() as $timezone) {
            $this->addSql(
                'INSERT INTO timezones (name, utc_offset, enabled)
                 VALUES (:name, :utc_offset, :enabled)
                 ON DUPLICATE KEY UPDATE
                    utc_offset = VALUES(utc_offset),
                    enabled = VALUES(enabled)',
                $timezone
            );
        }
    }

    private function seedMaintenanceConfig(string $domain): void
    {
        $this->addSql(
            'INSERT INTO config (visibility, people_id, module_id, config_key, config_value)
             SELECT :visibility, people_domain.people_id, module.id, :config_key, :config_value
             FROM module
             INNER JOIN people_domain ON people_domain.domain = :domain
             WHERE module.name = :module_name
               AND NOT EXISTS (
                   SELECT 1
                   FROM config
                   WHERE people_id = people_domain.people_id
                     AND module_id = module.id
                     AND config_key = :config_key
               )
             LIMIT 1',
            [
                'visibility' => 'public',
                'domain' => $domain,
                'module_name' => 'common',
                'config_key' => 'maintenance-routines',
                'config_value' => json_encode(
                    $this->getMaintenanceRoutinesSeed(),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
            ]
        );
    }

    private function getModulesSeed(): array
    {
        return [
            [
                'name' => 'common',
                'color' => '#64748B',
                'icon' => 'settings',
                'description' => 'Modulo comum da API.',
            ],
            [
                'name' => 'ui-manager',
                'color' => '#64748B',
                'icon' => 'settings',
                'description' => 'Modulo administrativo do app.',
            ],
        ];
    }

    private function getRoutesSeed(): array
    {
        return [
            [
                'module_name' => 'ui-manager',
                'route' => 'CronJobsPage',
                'color' => '#F59E0B',
                'icon' => 'clock',
            ],
            [
                'module_name' => 'ui-manager',
                'route' => 'PeopleDomainsPage',
                'color' => '#0EA5E9',
                'icon' => 'globe',
            ],
            [
                'module_name' => 'ui-manager',
                'route' => 'ProductShowcasesPage',
                'color' => '#0F766E',
                'icon' => 'grid',
            ],
            [
                'module_name' => 'ui-manager',
                'route' => 'FlowchartsPage',
                'color' => '#0F766E',
                'icon' => 'git-branch',
            ],
        ];
    }

    private function getMenusSeed(): array
    {
        return [
            [
                'route' => 'PeopleDomainsPage',
                'menu' => 'Domínios',
                'menu_key' => 'people_domains',
                'app_type' => 'ADMIN',
                'menu_type' => 'home',
                'sort_order' => 12,
            ],
            [
                'route' => 'CronJobsPage',
                'menu' => 'Jobs agendados',
                'menu_key' => 'cron_jobs',
                'app_type' => 'ADMIN',
                'menu_type' => 'home',
                'sort_order' => 15,
            ],
            [
                'route' => 'FlowchartsPage',
                'menu' => 'Fluxogramas',
                'menu_key' => 'flowcharts',
                'app_type' => 'ADMIN',
                'menu_type' => 'home',
                'sort_order' => 40,
            ],
            [
                'route' => 'ProductShowcasesPage',
                'menu' => 'Vitrines de preços',
                'menu_key' => 'product_showcases',
                'app_type' => 'MANAGER',
                'menu_type' => 'home',
                'sort_order' => 65,
            ],
        ];
    }

    private function getMediaTypesSeed(): array
    {
        return [
            ['type' => 'avatar', 'people_type' => 'F'],
            ['type' => 'logo', 'people_type' => 'J'],
            ['type' => 'background', 'people_type' => 'J'],
            ['type' => 'icon', 'people_type' => 'J'],
            ['type' => 'stamp', 'people_type' => 'J'],
            ['type' => 'pin', 'people_type' => 'J'],
        ];
    }

    private function getTimezonesSeed(): array
    {
        return [
            ['name' => 'America/Noronha', 'utc_offset' => 'UTC -02:00', 'enabled' => 0],
            ['name' => 'America/Sao_Paulo', 'utc_offset' => 'UTC -03:00', 'enabled' => 1],
            ['name' => 'America/Belem', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Fortaleza', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Recife', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Araguaina', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Maceio', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Bahia', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Santarem', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Argentina/Buenos_Aires', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Argentina/Catamarca', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Argentina/Cordoba', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Argentina/Jujuy', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Argentina/La_Rioja', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Argentina/Mendoza', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Argentina/Rio_Gallegos', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Argentina/Salta', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Argentina/San_Juan', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Argentina/San_Luis', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Argentina/Tucuman', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Argentina/Ushuaia', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Paramaribo', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Montevideo', 'utc_offset' => 'UTC -03:00', 'enabled' => 0],
            ['name' => 'America/Campo_Grande', 'utc_offset' => 'UTC -04:00', 'enabled' => 1],
            ['name' => 'America/Cuiaba', 'utc_offset' => 'UTC -04:00', 'enabled' => 0],
            ['name' => 'America/Porto_Velho', 'utc_offset' => 'UTC -04:00', 'enabled' => 0],
            ['name' => 'America/Boa_Vista', 'utc_offset' => 'UTC -04:00', 'enabled' => 0],
            ['name' => 'America/Manaus', 'utc_offset' => 'UTC -04:00', 'enabled' => 0],
            ['name' => 'America/La_Paz', 'utc_offset' => 'UTC -04:00', 'enabled' => 0],
            ['name' => 'America/Santiago', 'utc_offset' => 'UTC -04:00', 'enabled' => 0],
            ['name' => 'America/Guyana', 'utc_offset' => 'UTC -04:00', 'enabled' => 0],
            ['name' => 'America/Asuncion', 'utc_offset' => 'UTC -04:00', 'enabled' => 0],
            ['name' => 'America/Caracas', 'utc_offset' => 'UTC -04:00', 'enabled' => 0],
            ['name' => 'America/Rio_Branco', 'utc_offset' => 'UTC -05:00', 'enabled' => 0],
            ['name' => 'America/Eirunepe', 'utc_offset' => 'UTC -05:00', 'enabled' => 0],
            ['name' => 'America/Bogota', 'utc_offset' => 'UTC -05:00', 'enabled' => 0],
            ['name' => 'America/Lima', 'utc_offset' => 'UTC -05:00', 'enabled' => 0],
            ['name' => 'America/Guayaquil', 'utc_offset' => 'UTC -05:00', 'enabled' => 0],
            ['name' => 'Pacific/Galapagos', 'utc_offset' => 'UTC -06:00', 'enabled' => 0],
            ['name' => 'Pacific/Easter', 'utc_offset' => 'UTC -06:00', 'enabled' => 0],
        ];
    }

    private function getMaintenanceRoutinesSeed(): array
    {
        return [
            'cleanup_logs' => [
                'enabled' => true,
                'cronExpression' => '* * * * *',
            ],
            'cleanup_ephemeral_integrations' => [
                'enabled' => true,
                'cronExpression' => '* * * * *',
            ],
        ];
    }

    private function foreignKeyExists(string $tableName, string $constraintName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            [$tableName, $constraintName, 'FOREIGN KEY']
        ) > 0;
    }
}
