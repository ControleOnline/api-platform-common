<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use Doctrine\DBAL\Schema\Schema;

final class Version20260717192000 extends AbstractTenantAwareMigration
{
    private const MENU_MODULE_NAME = 'ui-manager';
    private const MENU_CATEGORY_NAME = 'Configuracoes';
    private const MENU_ROUTE_NAME = 'CronJobsPage';
    private const MENU_KEY = 'cron_jobs';
    private const MENU_LABEL = 'Jobs agendados';

    public function getDescription(): string
    {
        return 'Seed the admin menu entry for cron jobs.';
    }

    public function up(Schema $schema): void
    {
        $mainCompanyId = $this->getMainCompanyId();

        $this->addSql(
            'INSERT INTO module (name, color, icon, description)
             SELECT :name, :color, :icon, :description
             FROM DUAL
             WHERE NOT EXISTS (
                 SELECT 1
                 FROM module
                 WHERE name = :name
             )',
            [
                'name' => self::MENU_MODULE_NAME,
                'color' => '#64748B',
                'icon' => 'settings',
                'description' => 'Modulo administrativo do app.',
            ]
        );

        $this->addSql(
            'INSERT INTO routes (module_id, route, color, icon)
             SELECT (
                 SELECT id
                 FROM module
                 WHERE name = :module_name
                 LIMIT 1
             ), :route, :color, :icon
             FROM DUAL
             WHERE NOT EXISTS (
                 SELECT 1
                 FROM routes
                 WHERE route = :route
             )',
            [
                'module_name' => self::MENU_MODULE_NAME,
                'route' => self::MENU_ROUTE_NAME,
                'color' => '#F59E0B',
                'icon' => 'clock',
            ]
        );

        $this->addSql(
            'INSERT INTO category (name, context, company_id, icon, color)
             SELECT :name, :context, :company_id, :icon, :color
             FROM DUAL
             WHERE NOT EXISTS (
                 SELECT 1
                 FROM category
                 WHERE company_id = :company_id
                   AND context = :context
                   AND name = :name
             )',
            [
                'name' => self::MENU_CATEGORY_NAME,
                'context' => 'menu',
                'company_id' => $mainCompanyId,
                'icon' => 'settings',
                'color' => '#64748B',
            ]
        );

        $this->addSql(
            'INSERT INTO menu (category_id, menu, route_id, menu_key, app_type, menu_type, sort_order, enabled)
             SELECT
                 (
                     SELECT id
                     FROM category
                     WHERE company_id = :company_id
                       AND context = :context
                       AND name = :category_name
                     LIMIT 1
                 ),
                 :menu,
                 (
                     SELECT id
                     FROM routes
                     WHERE route = :route_name
                     LIMIT 1
                 ),
                 :menu_key,
                 :app_type,
                 :menu_type,
                 :sort_order,
                 1
             FROM DUAL
             WHERE NOT EXISTS (
                 SELECT 1
                 FROM menu
                 WHERE app_type = :app_type
                   AND menu_type = :menu_type
                   AND menu_key = :menu_key
             )',
            [
                'company_id' => $mainCompanyId,
                'context' => 'menu',
                'category_name' => self::MENU_CATEGORY_NAME,
                'route_name' => self::MENU_ROUTE_NAME,
                'menu' => self::MENU_LABEL,
                'menu_key' => self::MENU_KEY,
                'app_type' => 'ADMIN',
                'menu_type' => 'home',
                'sort_order' => 15,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'DELETE FROM menu
             WHERE app_type = :app_type
               AND menu_type = :menu_type
               AND menu_key = :menu_key',
            [
                'app_type' => 'ADMIN',
                'menu_type' => 'home',
                'menu_key' => self::MENU_KEY,
            ]
        );
    }
}
