<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260729190000 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Remove obsolete tenant cron_jobs table.';
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists('tenancies') || !$this->tableExists('cron_jobs')) {
            return;
        }

        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('DROP TABLE `cron_jobs`');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        return;
    }

    private function tableExists(string $tableName): bool
    {
        return false !== $this->connection->fetchAssociative(
            'SHOW TABLES LIKE ?',
            [$tableName]
        );
    }
}
