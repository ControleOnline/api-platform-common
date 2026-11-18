<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Secrets saved as public were served without login by /people/company/default
 * (e.g. asaas-key, asaas-webhook-token). Same rule as ConfigService::isSensitiveKey.
 */
final class Version20261118150000 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Make secret-like configs (keys, tokens, passwords) private.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('config')) {
            return;
        }

        $this->addSql(
            "UPDATE `config` SET `visibility` = 'private'"
            . " WHERE `visibility` <> 'private'"
            . " AND LOWER(`config_key`) REGEXP '(secret|token|password|passwd|senha|credential|api[-_]?key|access[-_]?key|(^|[-_])key$|^ai-providers$)'"
            . " AND LOWER(`config_key`) NOT REGEXP '(google-maps|google-tag-manager|site[-_]?key)'"
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Secrets must not become public again.');
    }

    private function tableExists(string $tableName): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$tableName]
        );
    }
}
