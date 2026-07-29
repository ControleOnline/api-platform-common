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

        if ($this->tableExists('product_file')) {
            $this->addSql(
                'UPDATE `files` f
                    INNER JOIN `product_file` pf ON pf.file_id = f.id
                    SET f.`public` = 1
                    WHERE LOWER(f.file_type) = \'image\''
            );
        }

        if ($this->tableExists('people_media') && $this->tableExists('media_types')) {
            $this->addSql(
                'UPDATE `files` f
                    INNER JOIN `people_media` pm ON pm.file_id = f.id
                    INNER JOIN `media_types` mt ON mt.id = pm.media_type_id
                    SET f.`public` = 1
                    WHERE LOWER(f.file_type) = \'image\'
                      AND LOWER(mt.type) IN (\'background\', \'icon\', \'logo\', \'pin\', \'stamp\')'
            );
        }
    }

    public function down(Schema $schema): void
    {
        return;
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }
}
