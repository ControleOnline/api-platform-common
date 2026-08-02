<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260731220000 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Add canonical sibling order to categories.';
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists('category') && !$this->columnExists('category', 'sort_order')) {
            $this->addSql('ALTER TABLE `category` ADD `sort_order` INT DEFAULT NULL AFTER `parent_id`');
        }
    }

    public function down(Schema $schema): void
    {
        return;
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
