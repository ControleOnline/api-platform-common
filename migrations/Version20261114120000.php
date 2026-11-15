<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

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
        $this->addSql(sprintf(
            'ALTER TABLE connections MODIFY channel ENUM(%s) NOT NULL',
            $this->enumList(['whatsapp', 'whatsapp_official', 'email'])
        ));
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM connections WHERE channel = 'email'");
        $this->addSql("ALTER TABLE connections MODIFY channel ENUM('whatsapp','whatsapp_official') NOT NULL");
    }

    // Schema probes keep this migration idempotent across tenants (api-platform-common#64).
    private function hasColumn(string $column): bool
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'connections' AND COLUMN_NAME = ?",
            [$column]
        ) > 0;
    }

    /**
     * Quoted ENUM list with the required values plus any value the column already accepts,
     * so a later migration (e.g. email) is never undone by an earlier one running late.
     *
     * @param list<string> $required
     */
    private function enumList(array $required): string
    {
        $type = (string) $this->connection->fetchOne(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'connections' AND COLUMN_NAME = 'channel'"
        );
        preg_match_all("/'([^']*)'/", $type, $matches);
        $values = array_values(array_unique([...$required, ...($matches[1] ?? [])]));

        return implode(',', array_map(fn (string $v) => $this->connection->quote($v), $values));
    }
}
