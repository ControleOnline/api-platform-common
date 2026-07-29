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

        $this->addSql('ALTER TABLE `cron_jobs` ADD `last_execution_at` DATETIME DEFAULT NULL AFTER `arguments`');
        $this->addSql('ALTER TABLE `cron_jobs` ADD `last_status` VARCHAR(20) DEFAULT NULL AFTER `last_execution_at`');
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
}
