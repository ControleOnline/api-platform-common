<?php

namespace ControleOnline\Command;

use ControleOnline\Service\DatabaseSwitchService;
use ControleOnline\Service\LogCleanupService;
use ControleOnline\Service\LoggerService;
use ControleOnline\Service\SkyNetService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Lock\LockFactory;

#[AsCommand(
    name: 'app:logs:cleanup',
    description: 'Aplica a politica de retencao dos logs configurada na empresa principal.',
)]
class CleanupLogsCommand extends DefaultCommand
{
    public function __construct(
        LockFactory $lockFactory,
        DatabaseSwitchService $databaseSwitchService,
        LoggerService $loggerService,
        SkyNetService $skyNetService,
        private LogCleanupService $logCleanupService,
    ) {
        $this->lockFactory = $lockFactory;
        $this->databaseSwitchService = $databaseSwitchService;
        $this->loggerService = $loggerService;
        $this->skyNetService = $skyNetService;

        parent::__construct('app:logs:cleanup');
    }

    protected function configure(): void
    {
        $this->setDescription('Remove logs expirados conforme a retencao configurada.');
    }

    protected function runCommand(): int
    {
        $summary = $this->logCleanupService->cleanup();
        $deletedTotal = (int) ($summary['deletedTotal'] ?? 0);
        $deletedByPolicy = is_array($summary['deletedByPolicy'] ?? null)
            ? $summary['deletedByPolicy']
            : [];

        $this->addLog(
            sprintf('[app:logs:cleanup] Total removido: %d', $deletedTotal),
            0,
            'log-cleanup'
        );

        foreach ($deletedByPolicy as $policyKey => $deletedCount) {
            $this->addLog(
                sprintf(
                    '[app:logs:cleanup] Politica=%s | removidos=%d',
                    $policyKey,
                    (int) $deletedCount
                ),
                0,
                'log-cleanup'
            );
        }

        return Command::SUCCESS;
    }
}
