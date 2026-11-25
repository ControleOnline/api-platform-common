<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** imports.created_by: people who uploaded the file (seller imports go to his own portfolio). */
final class Version20261125120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'imports.created_by references the uploader people';
    }

    public function up(Schema $schema): void
    {
        if ($this->hasColumn('created_by')) {
            return;
        }

        // No FK: the tenant-registry database also carries an imports table but no people table.
        $this->addSql('ALTER TABLE imports ADD created_by INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_IMPORTS_CREATED_BY ON imports (created_by)');
    }

    public function down(Schema $schema): void
    {
        if (!$this->hasColumn('created_by')) {
            return;
        }

        $this->addSql('DROP INDEX IDX_IMPORTS_CREATED_BY ON imports');
        $this->addSql('ALTER TABLE imports DROP created_by');
    }

    // Schema probe keeps this migration idempotent across tenants (api-platform-common#64).
    private function hasColumn(string $column): bool
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'imports' AND COLUMN_NAME = ?",
            [$column]
        ) > 0;
    }
}
