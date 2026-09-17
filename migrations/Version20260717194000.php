<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260717194000 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Add cron_jobs execution tracking fields.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('cron_jobs')) {
            return;
        }

        if (!$this->columnExists('cron_jobs', 'last_execution_at')) {
            $this->addSql('ALTER TABLE `cron_jobs` ADD `last_execution_at` DATETIME DEFAULT NULL AFTER `arguments`');
        }

        if (!$this->columnExists('cron_jobs', 'last_status')) {
            $this->addSql('ALTER TABLE `cron_jobs` ADD `last_status` VARCHAR(20) DEFAULT NULL AFTER `last_execution_at`');
        }
    }

    public function down(Schema $schema): void
    {
        return;
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
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
