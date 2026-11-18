<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20261118130000 extends TenantAwareMigration
{
    private const COLUMNS = [
        'routeMenuBackground',
        'routeMenuIcon',
        'routeMenuText',
    ];

    public function getDescription(): string
    {
        return 'Add routeMenuBackground, routeMenuIcon and routeMenuText (sidebar colors) to routes.';
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
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Removing the route menu colors would delete saved colors.');
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
