<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20261117120000 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Add categoryCardBackground, categoryCardText and categoryCardMenu to category and copy legacy colors.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('category')) {
            return;
        }

        // [new column, legacy column the value is copied from]
        $columns = [
            ['categoryCardBackground', 'icon_color'],
            ['categoryCardText', 'text_color'],
            ['categoryCardMenu', 'text_color'],
        ];

        foreach ($columns as [$column]) {
            if (!$this->columnExists('category', $column)) {
                $this->addSql(sprintf('ALTER TABLE `category` ADD `%s` VARCHAR(50) DEFAULT NULL', $column));
            }
        }

        foreach ($columns as [$column, $legacy]) {
            if ($this->columnExists('category', $legacy)) {
                // Only fills empty targets, so re-running never overwrites edited values.
                $this->addSql(sprintf(
                    'UPDATE `category` SET `%1$s` = `%2$s` WHERE `%1$s` IS NULL AND `%2$s` IS NOT NULL',
                    $column,
                    $legacy
                ));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Removing the category card colors would delete saved colors.');
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
