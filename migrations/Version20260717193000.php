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
        if (!$this->tableExists('cron_jobs')) {
            return;
        }

        if ($this->indexExists('cron_jobs', 'cron_jobs_people_job_key_unique')) {
            $this->addSql('ALTER TABLE `cron_jobs` DROP INDEX `cron_jobs_people_job_key_unique`');
        }

        $columns = array_values(array_filter(
            ['job_key', 'background', 'sort_order'],
            fn (string $column): bool => $this->columnExists('cron_jobs', $column)
        ));

        if ($columns !== []) {
            $this->addSql(sprintf(
                'ALTER TABLE `cron_jobs` %s',
                implode(', ', array_map(static fn (string $column): string => sprintf('DROP COLUMN `%s`', $column), $columns))
            ));
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

    private function indexExists(string $tableName, string $indexName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?',
            [$tableName, $indexName]
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
