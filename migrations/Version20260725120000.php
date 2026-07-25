<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260725120000 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Add enabled and utc_offset columns to timezones, insert remaining South America TZs, and enable only Sao_Paulo and Campo_Grande.';
    }

    public function up(Schema $schema): void
    {
        // 1️⃣ Adiciona coluna enabled
        $this->addSql('
            ALTER TABLE timezones
            ADD COLUMN enabled TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER name
        ');

        // 2️⃣ Adiciona coluna utc_offset
        $this->addSql('
            ALTER TABLE timezones
            ADD COLUMN utc_offset VARCHAR(10) NOT NULL DEFAULT "UTC +00:00" AFTER name
        ');

        // 3️⃣ Insere timezones restantes da América do Sul
        $this->addSql("
            INSERT IGNORE INTO timezones (name) VALUES
            ('America/Argentina/Buenos_Aires'),
            ('America/Argentina/Catamarca'),
            ('America/Argentina/Cordoba'),
            ('America/Argentina/Jujuy'),
            ('America/Argentina/La_Rioja'),
            ('America/Argentina/Mendoza'),
            ('America/Argentina/Rio_Gallegos'),
            ('America/Argentina/Salta'),
            ('America/Argentina/San_Juan'),
            ('America/Argentina/San_Luis'),
            ('America/Argentina/Tucuman'),
            ('America/Argentina/Ushuaia'),
            ('America/La_Paz'),
            ('America/Santiago'),
            ('Pacific/Easter'),
            ('America/Bogota'),
            ('America/Guayaquil'),
            ('Pacific/Galapagos'),
            ('America/Guyana'),
            ('America/Asuncion'),
            ('America/Lima'),
            ('America/Paramaribo'),
            ('America/Montevideo'),
            ('America/Caracas')
        ");

        // 4️⃣ Atualiza offsets (Julho 2026)

        $this->addSql("
            UPDATE timezones SET utc_offset = 'UTC -02:00'
            WHERE name = 'America/Noronha';

            UPDATE timezones SET utc_offset = 'UTC -03:00'
            WHERE name IN (
                'America/Sao_Paulo',
                'America/Belem',
                'America/Fortaleza',
                'America/Recife',
                'America/Araguaina',
                'America/Maceio',
                'America/Bahia',
                'America/Santarem',
                'America/Argentina/Buenos_Aires',
                'America/Argentina/Catamarca',
                'America/Argentina/Cordoba',
                'America/Argentina/Jujuy',
                'America/Argentina/La_Rioja',
                'America/Argentina/Mendoza',
                'America/Argentina/Rio_Gallegos',
                'America/Argentina/Salta',
                'America/Argentina/San_Juan',
                'America/Argentina/San_Luis',
                'America/Argentina/Tucuman',
                'America/Argentina/Ushuaia',
                'America/Paramaribo',
                'America/Montevideo'
            );

            UPDATE timezones SET utc_offset = 'UTC -04:00'
            WHERE name IN (
                'America/Campo_Grande',
                'America/Cuiaba',
                'America/Porto_Velho',
                'America/Boa_Vista',
                'America/Manaus',
                'America/La_Paz',
                'America/Santiago',
                'America/Guyana',
                'America/Asuncion',
                'America/Caracas'
            );

            UPDATE timezones SET utc_offset = 'UTC -05:00'
            WHERE name IN (
                'America/Rio_Branco',
                'America/Eirunepe',
                'America/Bogota',
                'America/Lima',
                'America/Guayaquil'
            );

            UPDATE timezones SET utc_offset = 'UTC -06:00'
            WHERE name = 'Pacific/Galapagos';
        ");

        // 5️⃣ Garante todos desabilitados
        $this->addSql('UPDATE timezones SET enabled = 0');

        // 6️⃣ Ativa apenas São Paulo e Campo Grande
        $this->addSql("
            UPDATE timezones
            SET enabled = 1
            WHERE name IN (
                'America/Sao_Paulo',
                'America/Campo_Grande'
            )
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE timezones DROP COLUMN enabled');
        $this->addSql('ALTER TABLE timezones DROP COLUMN utc_offset');
    }
}