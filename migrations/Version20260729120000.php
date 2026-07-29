<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260729120000 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Add public visibility flag to files.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('files')) {
            return;
        }

        if (!$this->columnExists('files', 'public')) {
            $this->addSql('ALTER TABLE `files` ADD `public` TINYINT(1) DEFAULT 0 NOT NULL');
        }
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

    private function columnExists(string $tableName, string $columnName): bool
    {
        $tableName = str_replace('`', '``', $tableName);

        return false !== $this->connection->fetchAssociative(
            sprintf('SHOW COLUMNS FROM `%s` LIKE ?', $tableName),
            [$columnName]
        );
    }
}
