<?php

declare(strict_types=1);

namespace DoctrineMigrations\ZTenantSeed;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Requests (app-community#1592–#1600):
 * - "Solicitações" (McpPage, /ai) in every customer app: the company's teams open and follow
 *   relationship/support requests there.
 * - "Atendimento de suporte" (SupportFunnel, /support) in the Manager: analysts answer support.
 * The CRM keeps "Oportunidades" (CrmIndex) for relationship requests.
 * Idempotent: routes and menus are inserted once and kept up to date.
 */
final class Version20261117120000 extends TenantAwareMigration
{
    private const HUMAN_LINKS = ['owner', 'director', 'manager', 'admin', 'employee', 'salesman', 'after-sales', 'courier'];
    private const SUPPORT_LINKS = ['owner', 'director', 'manager', 'admin', 'employee', 'after-sales'];

    public function getDescription(): string
    {
        return 'Seed Solicitações (McpPage) in customer apps and Atendimento de suporte (SupportFunnel) in the Manager.';
    }

    public function up(Schema $schema): void
    {
        $domain = $this->resolveExecutionDomain();

        $this->route('McpPage', '#0EA5E9', 'message-circle');
        $this->route('SupportFunnel', '#7C3AED', 'headphones');

        $requests = [
            'MANAGER' => 'Configurações',
            'CRM' => 'Configurações',
            'DELIVERY' => 'Operacao',
            'POS' => 'Operacao',
            'PPC' => 'Producao',
            'SERVICE' => 'Operacoes',
        ];
        foreach ($requests as $appType => $category) {
            $this->menu($domain, $appType, $category, 'McpPage', 'Solicitações', 'requests', 900, self::HUMAN_LINKS);
        }
        $this->menu($domain, 'MANAGER', 'Operacoes', 'SupportFunnel', 'Atendimento de suporte', 'support_requests', 215, self::SUPPORT_LINKS);
    }

    public function down(Schema $schema): void
    {
        return;
    }

    private function route(string $route, string $color, string $icon): void
    {
        $params = ['module_name' => 'ui-crm', 'route' => $route, 'color' => $color, 'icon' => $icon];
        $this->addSql(
            'INSERT INTO routes (module_id, route, color, icon)
             SELECT module.id, :route, :color, :icon
             FROM module
             WHERE module.name = :module_name
               AND NOT EXISTS (SELECT 1 FROM routes WHERE route = :route)
             LIMIT 1',
            $params
        );
        $this->addSql(
            'UPDATE routes
             INNER JOIN module ON module.name = :module_name
             SET routes.module_id = module.id, routes.color = :color, routes.icon = :icon
             WHERE routes.route = :route',
            $params
        );
    }

    /** @param list<string> $linkTypes */
    private function menu(string $domain, string $appType, string $category, string $route, string $label, string $key, int $sort, array $linkTypes): void
    {
        $params = [
            'domain' => $domain,
            'category_context' => 'menu',
            'category_name' => $category,
            'route' => $route,
            'menu' => $label,
            'menu_key' => $key,
            'app_type' => $appType,
            'menu_type' => 'home',
            'route_params' => null,
            'sort_order' => $sort,
            'enabled' => 1,
        ];
        $this->addSql(
            'INSERT INTO menu (category_id, menu, route_id, menu_key, app_type, menu_type, route_params, sort_order, enabled)
             SELECT category.id, :menu, routes.id, :menu_key, :app_type, :menu_type, :route_params, :sort_order, :enabled
             FROM category
             INNER JOIN people_domain ON people_domain.people_id = category.company_id
             INNER JOIN routes ON routes.route = :route
             WHERE people_domain.domain = :domain
               AND category.context = :category_context
               AND category.name = :category_name
               AND NOT EXISTS (
                   SELECT 1 FROM menu WHERE app_type = :app_type AND menu_type = :menu_type AND menu_key = :menu_key
               )
             LIMIT 1',
            $params
        );
        $this->addSql(
            'UPDATE menu
             INNER JOIN category ON category.context = :category_context AND category.name = :category_name
             INNER JOIN people_domain ON people_domain.people_id = category.company_id AND people_domain.domain = :domain
             INNER JOIN routes ON routes.route = :route
             SET menu.category_id = category.id,
                 menu.menu = :menu,
                 menu.route_id = routes.id,
                 menu.route_params = :route_params,
                 menu.sort_order = :sort_order,
                 menu.enabled = :enabled
             WHERE menu.app_type = :app_type AND menu.menu_type = :menu_type AND menu.menu_key = :menu_key',
            $params
        );
        foreach ($linkTypes as $linkType) {
            $this->addSql(
                'INSERT INTO menu_link_type (menu_id, link_type)
                 SELECT menu.id, :link_type
                 FROM menu
                 WHERE menu.app_type = :app_type AND menu.menu_type = :menu_type AND menu.menu_key = :menu_key
                   AND NOT EXISTS (
                       SELECT 1 FROM menu_link_type existing_link
                       WHERE existing_link.menu_id = menu.id AND existing_link.link_type = :link_type
                   )',
                ['link_type' => $linkType, 'app_type' => $appType, 'menu_type' => 'home', 'menu_key' => $key]
            );
        }
    }
}
