<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260725120000 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Add enabled and utc_offset columns to timezones.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->columnExists('timezones', 'enabled')) {
            $this->addSql('ALTER TABLE timezones ADD COLUMN enabled TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER name');
        }

        if (!$this->columnExists('timezones', 'utc_offset')) {
            $this->addSql('ALTER TABLE timezones ADD COLUMN utc_offset VARCHAR(10) NOT NULL DEFAULT "UTC +00:00" AFTER name');
        }
    }

    public function down(Schema $schema): void
    {
        return;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }
}
