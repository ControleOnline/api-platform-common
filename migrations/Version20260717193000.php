<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260717193000 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Drop obsolete cron_jobs columns and keep cron jobs identified by entity id.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `cron_jobs` DROP INDEX `cron_jobs_people_job_key_unique`');
        $this->addSql('ALTER TABLE `cron_jobs` DROP COLUMN `job_key`, DROP COLUMN `background`, DROP COLUMN `sort_order`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `cron_jobs` ADD `job_key` varchar(120) CHARACTER SET utf8 NOT NULL AFTER `people_id`');
        $this->addSql('ALTER TABLE `cron_jobs` ADD `background` tinyint(1) NOT NULL DEFAULT 1 AFTER `arguments`');
        $this->addSql('ALTER TABLE `cron_jobs` ADD `sort_order` int(11) NOT NULL DEFAULT 0 AFTER `background`');
        $this->addSql('ALTER TABLE `cron_jobs` ADD UNIQUE KEY `cron_jobs_people_job_key_unique` (`people_id`,`job_key`)');
    }
}
