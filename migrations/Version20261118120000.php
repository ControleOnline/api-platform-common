<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20261118120000 extends TenantAwareMigration
{
    private const COLUMNS = [
        'routeCardBackground',
        'routeCardIcon',
        'routeCardIconBackground',
        'routeCardText',
        'routeCardBorder',
    ];

    public function getDescription(): string
    {
        return 'Add routeCard* color columns to routes and copy legacy icon_background_color to routeCardBackground.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('routes')) {
            return;
        }

        foreach (self::COLUMNS as $column) {
            if (!$this->columnExists('routes', $column)) {
                $this->addSql(sprintf('ALTER TABLE `routes` ADD `%s` VARCHAR(50) DEFAULT NULL', $column));
            }
        }

        if ($this->columnExists('routes', 'icon_background_color')) {
            // Only fills empty targets, so re-running never overwrites edited values.
            $this->addSql(
                'UPDATE `routes` SET `routeCardBackground` = `icon_background_color` WHERE `routeCardBackground` IS NULL AND `icon_background_color` IS NOT NULL'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Removing the route card colors would delete saved colors.');
    }

    private function tableExists(string $tableName): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$tableName]
        );
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$tableName, $columnName]
        );
    }
}
