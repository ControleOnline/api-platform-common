<?php

namespace ControleOnline\Command;

use ControleOnline\Service\DatabaseSwitchService;
use ControleOnline\Service\LoggerService;
use ControleOnline\Service\MaintenanceRunnerService;
use ControleOnline\Service\SkyNetService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(
    name: 'app:maintenance:run',
    description: 'Executa as rotinas gerais de manutencao configuradas na empresa principal.',
)]
#[AsCronTask(expression: '* * * * *', schedule: 'maintenance')]
class MaintenanceRunCommand extends DefaultCommand
{
    public function __construct(
        LockFactory $lockFactory,
        DatabaseSwitchService $databaseSwitchService,
        LoggerService $loggerService,
        SkyNetService $skyNetService,
        private MaintenanceRunnerService $maintenanceRunnerService,
    ) {
        $this->lockFactory = $lockFactory;
        $this->databaseSwitchService = $databaseSwitchService;
        $this->loggerService = $loggerService;
        $this->skyNetService = $skyNetService;

        parent::__construct('app:maintenance:run');
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Executa as rotinas gerais de manutencao cadastradas na empresa principal.'
        );
    }

    protected function runCommand(): int
    {
        if (!$this->lock->acquire()) {
            $this->addLog(
                '[app:maintenance:run] Outro ciclo de manutencao ainda esta em execucao. Ignorando.',
                0,
                'maintenance'
            );

            return Command::SUCCESS;
        }

        $summary = $this->maintenanceRunnerService->runDueRoutines();
        $executed = is_array($summary['executed'] ?? null)
            ? $summary['executed']
            : [];

        $this->addLog(
            sprintf(
                '[app:maintenance:run] Ciclo %s | devidas=%d | executadas=%d',
                (string) ($summary['ranAt'] ?? ''),
                (int) ($summary['dueCount'] ?? 0),
                (int) ($summary['executedCount'] ?? 0),
            ),
            0,
            'maintenance'
        );

        foreach ($executed as $result) {
            $this->addLog(
                sprintf(
                    '[app:maintenance:run] Rotina=%s | status=%s | resumo=%s',
                    (string) ($result['key'] ?? ''),
                    (string) ($result['status'] ?? 'unknown'),
                    json_encode(
                        $result['summary'] ?? [],
                        JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                        | JSON_PARTIAL_OUTPUT_ON_ERROR
                    ) ?: '{}'
                ),
                0,
                'maintenance'
            );
        }

        return Command::SUCCESS;
    }
}
