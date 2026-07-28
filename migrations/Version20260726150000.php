<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260726150000 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Create editable Mermaid flowchart storage.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS flowchart (
                id INT AUTO_INCREMENT NOT NULL,
                flow_key VARCHAR(100) NOT NULL,
                app_type VARCHAR(30) DEFAULT \'ADMIN\' NOT NULL,
                title VARCHAR(120) NOT NULL,
                summary VARCHAR(255) DEFAULT NULL,
                mermaid LONGTEXT NOT NULL,
                checkpoints JSON NOT NULL,
                sort_order INT DEFAULT 0 NOT NULL,
                enabled TINYINT(1) DEFAULT 1 NOT NULL,
                INDEX flowchart_app_type_idx (app_type),
                UNIQUE INDEX flowchart_app_key_unique (app_type, flow_key),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        return;
    }
}
