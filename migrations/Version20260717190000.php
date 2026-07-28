<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260717190000 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Create the cron_jobs table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS `cron_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `people_id` int(11) NOT NULL,
  `job_key` varchar(120) CHARACTER SET utf8 NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 NOT NULL,
  `description` text CHARACTER SET utf8 NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT \'1\',
  `cron_expression` varchar(120) CHARACTER SET utf8 NOT NULL,
  `command` varchar(255) CHARACTER SET utf8 NOT NULL,
  `arguments` json NOT NULL,
  `background` tinyint(1) NOT NULL DEFAULT \'1\',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cron_jobs_people_job_key_unique` (`people_id`,`job_key`),
  KEY `cron_jobs_people_id_idx` (`people_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        return;
    }

}
