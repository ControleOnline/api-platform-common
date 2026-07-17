<?php

namespace ControleOnline\Command;

use ControleOnline\Service\CronJobRunnerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:cron:run-job',
    description: 'Executa um job cron configurado no banco.',
)]
class CronRunJobCommand extends Command
{
    public function __construct(
        private readonly CronJobRunnerService $cronJobRunnerService,
    ) {
        parent::__construct('app:cron:run-job');
    }

    protected function configure(): void
    {
        $this->addArgument(
            'jobKey',
            InputArgument::REQUIRED,
            'Chave do job cron configurado no banco.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jobKey = trim((string) $input->getArgument('jobKey'));
        if ($jobKey === '') {
            $output->writeln('<error>Job key nao informada.</error>');

            return Command::FAILURE;
        }

        $result = $this->cronJobRunnerService->run($jobKey);
        $status = (string) ($result['status'] ?? 'unknown');

        $output->writeln(sprintf(
            '[app:cron:run-job] key=%s | status=%s',
            $jobKey,
            $status
        ));

        return 'error' === $status
            ? Command::FAILURE
            : Command::SUCCESS;
    }
}
