<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Populate official IBGE codes for Brazilian states.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('state') || !$this->columnExists('state', 'cod_ibge')) {
            return;
        }

        $codes = [
            'AC' => 12,
            'AL' => 27,
            'AP' => 16,
            'AM' => 13,
            'BA' => 29,
            'CE' => 23,
            'DF' => 53,
            'ES' => 32,
            'GO' => 52,
            'MA' => 21,
            'MT' => 51,
            'MS' => 50,
            'MG' => 31,
            'PA' => 15,
            'PB' => 25,
            'PR' => 41,
            'PE' => 26,
            'PI' => 22,
            'RJ' => 33,
            'RN' => 24,
            'RS' => 43,
            'RO' => 11,
            'RR' => 14,
            'SC' => 42,
            'SP' => 35,
            'SE' => 28,
            'TO' => 17,
        ];

        foreach ($codes as $uf => $code) {
            $this->addSql(
                'UPDATE `state` SET `cod_ibge` = :code WHERE UPPER(`UF`) = :uf',
                ['code' => $code, 'uf' => $uf]
            );
        }
    }

    public function down(Schema $schema): void
    {
        return;
    }

    private function tableExists(string $tableName): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$tableName]
        );
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$tableName, $columnName]
        );
    }
}
