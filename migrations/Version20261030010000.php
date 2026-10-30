<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * connections.provider + provider_config for WhatsApp dual transport (official Cloud API vs Baileys).
 */
final class Version20261030010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add connections.provider and connections.provider_config for official/unofficial WhatsApp transport';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE connections ADD provider VARCHAR(30) DEFAULT 'unofficial' NULL");
        $this->addSql('ALTER TABLE connections ADD provider_config LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connections DROP provider');
        $this->addSql('ALTER TABLE connections DROP provider_config');
    }
}
