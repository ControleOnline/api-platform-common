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
        $this->addSql('ALTER TABLE `cron_jobs` ADD `last_execution_at` DATETIME DEFAULT NULL AFTER `arguments`');
        $this->addSql('ALTER TABLE `cron_jobs` ADD `last_status` VARCHAR(20) DEFAULT NULL AFTER `last_execution_at`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `cron_jobs` DROP COLUMN `last_status`');
        $this->addSql('ALTER TABLE `cron_jobs` DROP COLUMN `last_execution_at`');
    }
}
