<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260720131500 extends TenantAwareMigration
{
    private const MENU_KEY = 'product_showcases';

    public function getDescription(): string
    {
        return 'Grant manager menu visibility for product showcases.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'INSERT INTO menu_link_type (menu_id, link_type)
             SELECT menu.id, link_types.link_type
             FROM menu
             INNER JOIN (
                 SELECT :owner AS link_type
                 UNION ALL SELECT :director
                 UNION ALL SELECT :manager
             ) link_types
             WHERE menu.app_type = :app_type
               AND menu.menu_type = :menu_type
               AND menu.menu_key = :menu_key
               AND NOT EXISTS (
                   SELECT 1
                   FROM menu_link_type existing_link
                   WHERE existing_link.menu_id = menu.id
                     AND existing_link.link_type = link_types.link_type
               )',
            [
                'owner' => 'owner',
                'director' => 'director',
                'manager' => 'manager',
                'app_type' => 'MANAGER',
                'menu_type' => 'home',
                'menu_key' => self::MENU_KEY,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        return;
    }
}
