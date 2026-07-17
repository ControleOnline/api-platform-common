<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260717191000 extends TenantAwareMigration
{
    private const COMMON_MODULE_ID = 8;

    public function getDescription(): string
    {
        return 'Seed database-backed maintenance routines for the Symfony Scheduler.';
    }

    public function up(Schema $schema): void
    {
        $peopleId = $this->getMainCompanyId();
        $routinesConfig = json_encode(
            $this->getMaintenanceRoutinesSeed(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $this->addSql(
            'INSERT INTO config (visibility, people_id, module_id, config_key, config_value)
             SELECT :visibility, :people_id, :module_id, :config_key, :config_value
             WHERE NOT EXISTS (
                SELECT 1
                FROM config
                WHERE people_id = :people_id
                  AND module_id = :module_id
                  AND config_key = :config_key
             )',
            [
                'visibility' => 'public',
                'people_id' => $peopleId,
                'module_id' => self::COMMON_MODULE_ID,
                'config_key' => 'maintenance-routines',
                'config_value' => $routinesConfig,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $peopleId = $this->getMainCompanyId();

        $this->addSql(
            'DELETE FROM config
             WHERE people_id = :people_id
               AND module_id = :module_id
               AND config_key = :config_key',
            [
                'people_id' => $peopleId,
                'module_id' => self::COMMON_MODULE_ID,
                'config_key' => 'maintenance-routines',
            ]
        );
    }

    private function getMaintenanceRoutinesSeed(): array
    {
        return [
            'cleanup_logs' => [
                'enabled' => true,
                'cronExpression' => '* * * * *',
            ],
            'cleanup_ephemeral_integrations' => [
                'enabled' => true,
                'cronExpression' => '* * * * *',
            ],
        ];
    }
}
