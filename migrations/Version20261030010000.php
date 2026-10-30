<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Expand connections.channel for official WhatsApp Cloud API and add channel_config for credentials.
 */
final class Version20261030010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'connections.channel ENUM adds whatsapp_official; channel_config for Meta credentials';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE connections MODIFY channel ENUM('whatsapp','whatsapp_official') NOT NULL");
        $this->addSql('ALTER TABLE connections ADD channel_config LONGTEXT DEFAULT NULL');
        // Drop leftover columns from earlier draft of this migration if present
        $this->addSql('ALTER TABLE connections DROP COLUMN IF EXISTS provider');
        $this->addSql('ALTER TABLE connections DROP COLUMN IF EXISTS provider_config');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connections DROP COLUMN IF EXISTS channel_config');
        $this->addSql("ALTER TABLE connections MODIFY channel ENUM('whatsapp') NOT NULL");
    }
}
