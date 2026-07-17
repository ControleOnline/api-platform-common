<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717190000 extends AbstractMigration
{
    private const MAIN_DOMAIN = 'api.controleonline.com';

    public function getDescription(): string
    {
        return 'Create the cron_jobs table and seed the main-company cron jobs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS `cron_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `people_id` int(11) NOT NULL,
  `job_key` varchar(120) CHARACTER SET utf8 NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 NOT NULL,
  `description` text CHARACTER SET utf8 NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT \'1\',
  `cron_expression` varchar(120) CHARACTER SET utf8 NOT NULL,
  `command` varchar(255) CHARACTER SET utf8 NOT NULL,
  `arguments` json NOT NULL,
  `background` tinyint(1) NOT NULL DEFAULT \'1\',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cron_jobs_people_job_key_unique` (`people_id`,`job_key`),
  KEY `cron_jobs_people_id_idx` (`people_id`),
  CONSTRAINT `cron_jobs_people_id_fk` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $peopleId = $this->getMainCompanyId();

        foreach ($this->getCronJobsSeed() as $job) {
            $this->addSql(
                'INSERT INTO cron_jobs (
                    people_id,
                    job_key,
                    title,
                    description,
                    enabled,
                    cron_expression,
                    command,
                    arguments,
                    background,
                    sort_order
                ) VALUES (
                    :people_id,
                    :job_key,
                    :title,
                    :description,
                    :enabled,
                    :cron_expression,
                    :command,
                    :arguments,
                    :background,
                    :sort_order
                )',
                [
                    'people_id' => $peopleId,
                    'job_key' => $job['jobKey'],
                    'title' => $job['title'],
                    'description' => $job['description'],
                    'enabled' => $job['enabled'] ? 1 : 0,
                    'cron_expression' => $job['cronExpression'],
                    'command' => $job['command'],
                    'arguments' => json_encode(
                        $job['arguments'],
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                    'background' => $job['background'] ? 1 : 0,
                    'sort_order' => $job['sortOrder'],
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS `cron_jobs`');
    }

    private function getMainCompanyId(): int
    {
        $mainCompanyId = (int) $this->connection->fetchOne(
            'SELECT people_id
             FROM people_domain
             WHERE domain = :domain
             LIMIT 1',
            [
                'domain' => self::MAIN_DOMAIN,
            ]
        );

        if ($mainCompanyId <= 0) {
            throw new \RuntimeException(
                sprintf('Main company for domain "%s" was not found.', self::MAIN_DOMAIN)
            );
        }

        return $mainCompanyId;
    }

    /**
     * @return array<int, array{
     *     jobKey: string,
     *     title: string,
     *     description: string,
     *     enabled: bool,
     *     cronExpression: string,
     *     command: string,
     *     arguments: array<int, string>,
     *     background: bool,
     *     sortOrder: int
     * }>
     */
    private function getCronJobsSeed(): array
    {
        return [
            [
                'jobKey' => 'websocket_start',
                'title' => 'Servidor WebSocket',
                'description' => 'Mantem o servidor WebSocket da API ativo.',
                'enabled' => true,
                'cronExpression' => '* * * * *',
                'command' => 'websocket:start',
                'arguments' => [
                    '--domain=api.controleonline.com',
                    '-p',
                    '8080',
                    '-b',
                    '0.0.0.0',
                ],
                'background' => true,
                'sortOrder' => 10,
            ],
            [
                'jobKey' => 'tenant_messenger_consume',
                'title' => 'Consumer async',
                'description' => 'Mantem o consumer async por tenant ativo.',
                'enabled' => true,
                'cronExpression' => '* * * * *',
                'command' => 'tenant:messenger:consume',
                'arguments' => [
                    'async',
                    '--domain=api.controleonline.com',
                ],
                'background' => true,
                'sortOrder' => 20,
            ],
            [
                'jobKey' => 'tenant_integration_start',
                'title' => 'Integracoes por tenant',
                'description' => 'Processa a fila de integracoes por tenant.',
                'enabled' => true,
                'cronExpression' => '* * * * *',
                'command' => 'tenant:integration:start',
                'arguments' => [
                    '--domain=api.controleonline.com',
                ],
                'background' => true,
                'sortOrder' => 30,
            ],
            [
                'jobKey' => 'import_start',
                'title' => 'Importacoes',
                'description' => 'Processa a fila de importacoes pendentes.',
                'enabled' => true,
                'cronExpression' => '* * * * *',
                'command' => 'import:start',
                'arguments' => [
                    '--domain=api.controleonline.com',
                ],
                'background' => true,
                'sortOrder' => 40,
            ],
            [
                'jobKey' => 'maintenance_run',
                'title' => 'Manutencao',
                'description' => 'Executa as rotinas de manutencao da empresa principal.',
                'enabled' => true,
                'cronExpression' => '* * * * *',
                'command' => 'app:maintenance:run',
                'arguments' => [
                    '--domain=api.controleonline.com',
                ],
                'background' => true,
                'sortOrder' => 50,
            ],
        ];
    }
}
