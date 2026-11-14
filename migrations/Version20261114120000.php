<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** connections.channel accepts email (IMAP read + SMTP reply; credentials in channel_config). */
final class Version20261114120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'connections.channel ENUM adds email';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE connections MODIFY channel ENUM('whatsapp','whatsapp_official','email') NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM connections WHERE channel = 'email'");
        $this->addSql("ALTER TABLE connections MODIFY channel ENUM('whatsapp','whatsapp_official') NOT NULL");
    }
}
