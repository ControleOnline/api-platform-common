<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable category/route foreground and route icon background colors; preserve existing palettes';
    }

    public function isTransactional(): bool
    {
        // MySQL schema changes implicitly commit; this migration contains DDL only.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category ADD text_color VARCHAR(50) DEFAULT NULL, ADD icon_color VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE routes ADD text_color VARCHAR(50) DEFAULT NULL, ADD icon_color VARCHAR(50) DEFAULT NULL, ADD icon_background_color VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Rolling back the application may retain these nullable columns without losing saved colors.
        $this->throwIrreversibleMigrationException('Removing appearance columns would delete saved colors.');
    }
}
