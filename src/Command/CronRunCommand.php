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
class CronRunCommand extends Command
{
    public function __construct(
        private LockFactory $lockFactory,
        private CronJobService $cronJobService,
        private CronJobRunnerService $cronJobRunnerService,
    ) {
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
        $lock = $this->lockFactory->createLock('app:cron:run', 900.0);
        if (!$lock->acquire()) {
            $output->writeln('[app:cron:run] ignored | another centralized cron is running');

            return Command::SUCCESS;
        }

        try {
            $server = $this->resolveServerFilter($input);
            $jobs = $this->cronJobService->getDueJobExecutions(
                new \DateTimeImmutable(),
                $server !== '' ? $server : null
            );

            if ($jobs === []) {
                $output->writeln('[app:cron:run] no due jobs');

                return Command::SUCCESS;
            }

            $output->writeln(sprintf('[app:cron:run] due_jobs=%d', count($jobs)));

            if ((bool) $input->getOption('dry-run')) {
                foreach ($jobs as $job) {
                    $output->writeln($this->formatJobLine($job, 'dry-run'));
                }

                return Command::SUCCESS;
            }

            $exitCode = Command::SUCCESS;
            foreach ($jobs as $job) {
                $output->writeln($this->formatJobLine($job, 'running'));
                $result = $this->cronJobRunnerService->runConfiguredJob($job);
                $status = (string) ($result['status'] ?? 'unknown');
                $output->writeln($this->formatJobLine($job, $status));

                if (in_array($status, ['failure', 'error'], true)) {
                    $exitCode = Command::FAILURE;
                }
            }

            return $exitCode;
        } finally {
            $lock->release();
        }
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
