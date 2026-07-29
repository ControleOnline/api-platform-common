<?php

namespace ControleOnline\Command;

use ControleOnline\Service\CronJobService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:cron:logs:cleanup',
    description: 'Remove logs antigos da tabela central cron_job_logs.',
)]
class CronLogsCleanupCommand extends Command
{
    public function __construct(
        private CronJobService $cronJobService,
    ) {
        parent::__construct('app:cron:logs:cleanup');
    }

    protected function configure(): void
    {
        $this->addOption('retention-days', null, InputOption::VALUE_REQUIRED, 'Dias de retencao dos logs centrais.', 30);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $retentionDays = filter_var($input->getOption('retention-days'), FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
                'default' => 30,
            ],
        ]);

        $deleted = $this->cronJobService->cleanupExecutionLogs((int) $retentionDays);

        $output->writeln(sprintf(
            '[app:cron:logs:cleanup] retention_days=%d | deleted=%d',
            (int) $retentionDays,
            $deleted
        ));

        return Command::SUCCESS;
    }
}
