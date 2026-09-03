<?php

namespace ControleOnline\Command;

use ControleOnline\Service\CronJobRunnerService;
use ControleOnline\Service\CronJobService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;

#[AsCommand(
    name: 'app:cron:run',
    description: 'Executa a agenda central de crons no master, expandindo jobs por tenant quando necessario.',
)]
class CronRunCommand extends DefaultCommand
{
    public function __construct(
        private CronJobService $cronJobService,
        private CronJobRunnerService $cronJobRunnerService,
        LockFactory $lockFactory,
    ) {
        $this->lockFactory = $lockFactory;
        parent::__construct('app:cron:run');
    }

    protected function configure(): void
    {
        $this
            ->addOption('server', null, InputOption::VALUE_OPTIONAL, 'Filtra jobs vinculados ao servidor informado.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Lista as execucoes devidas sem executar os comandos.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        if (!$this->lock->acquire()) {
            $output->writeln('[app:cron:run] ignored | another centralized cron is running');

            return Command::SUCCESS;
        }

        try {
            return $this->runCommand();
        } finally {
            if ($this->lock->isAcquired()) {
                $this->lock->release();
            }
        }
    }

    protected function runCommand(): int
    {
        $server = $this->resolveServerFilter($this->input);
        $jobs = $this->cronJobService->getDueJobExecutions(
            new \DateTimeImmutable(),
            $server !== '' ? $server : null
        );

        if ($jobs === []) {
            $this->output->writeln('[app:cron:run] no due jobs');

            return Command::SUCCESS;
        }

        $this->output->writeln(sprintf('[app:cron:run] due_jobs=%d', count($jobs)));

        if ((bool) $this->input->getOption('dry-run')) {
            foreach ($jobs as $job) {
                $this->output->writeln($this->formatJobLine($job, 'dry-run'));
            }

            return Command::SUCCESS;
        }

        $exitCode = Command::SUCCESS;
        foreach ($jobs as $job) {
            $this->output->writeln($this->formatJobLine($job, 'running'));
            $result = $this->cronJobRunnerService->runConfiguredJob($job);
            $status = (string) ($result['status'] ?? 'unknown');
            $this->output->writeln($this->formatJobLine($job, $status));

            if (in_array($status, ['failure', 'error'], true)) {
                $exitCode = Command::FAILURE;
            }
        }

        return $exitCode;
    }

    private function resolveServerFilter(InputInterface $input): string
    {
        return trim((string) (
            $input->getOption('server')
            ?: ($_ENV['APP_DOMAIN'] ?? $_SERVER['APP_DOMAIN'] ?? getenv('APP_DOMAIN') ?: '')
        ));
    }

    private function formatJobLine(array $job, string $status): string
    {
        return sprintf(
            '[app:cron:run] status=%s | job=%s | scope=%s | domain=%s | command=%s',
            $status,
            (string) ($job['id'] ?? $job['key'] ?? ''),
            (string) ($job['scope'] ?? ''),
            (string) ($job['domain'] ?? ''),
            (string) ($job['command'] ?? '')
        );
    }
}
