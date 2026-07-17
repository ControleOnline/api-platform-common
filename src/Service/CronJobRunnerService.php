<?php

namespace ControleOnline\Service;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class CronJobRunnerService
{
    public function __construct(
        private CronJobService $cronJobService,
        private KernelInterface $kernel,
        private LoggerService $loggerService,
    ) {}

    public function run(string $jobKey): array
    {
        $job = $this->cronJobService->getConfiguredJob($jobKey);
        if (!is_array($job)) {
            return [
                'key' => $jobKey,
                'status' => 'ignored',
                'summary' => ['message' => 'Cron job not found.'],
            ];
        }

        if (!($job['enabled'] ?? false)) {
            return [
                'key' => $jobKey,
                'status' => 'ignored',
                'summary' => ['message' => 'Cron job is disabled.'],
            ];
        }

        if (!($job['isValid'] ?? false)) {
            return [
                'key' => $jobKey,
                'status' => 'ignored',
                'summary' => ['message' => 'Cron expression is invalid.'],
            ];
        }

        $command = trim((string) ($job['command'] ?? ''));
        if ($command === '') {
            return [
                'key' => $jobKey,
                'status' => 'ignored',
                'summary' => ['message' => 'Cron job command is empty.'],
            ];
        }

        $arguments = array_map(
            static fn(string $argument): string => trim($argument),
            $job['arguments'] ?? []
        );

        $process = new Process(
            array_merge(
                [PHP_BINARY, $this->kernel->getProjectDir() . '/bin/console', $command],
                $arguments
            ),
            $this->kernel->getProjectDir()
        );
        $process->setTimeout(null);

        if ($this->isBackgroundJob($job)) {
            $process->disableOutput();
            $process->setOptions(['create_new_console' => true]);
            $process->start();

            $this->logInfo(sprintf(
                '[cron:%s] started | pid=%s | command=%s',
                $jobKey,
                (string) ($process->getPid() ?? ''),
                $process->getCommandLine()
            ));

            return [
                'key' => $jobKey,
                'status' => 'started',
                'summary' => [
                    'pid' => $process->getPid(),
                    'commandLine' => $process->getCommandLine(),
                ],
            ];
        }

        try {
            $process->mustRun();

            $this->logInfo(sprintf(
                '[cron:%s] completed | exitCode=%d',
                $jobKey,
                (int) ($process->getExitCode() ?? 0)
            ));

            return [
                'key' => $jobKey,
                'status' => 'success',
                'summary' => [
                    'exitCode' => $process->getExitCode(),
                    'output' => $process->getOutput(),
                ],
            ];
        } catch (\Throwable $exception) {
            $this->logError(sprintf(
                '[cron:%s] failed | %s',
                $jobKey,
                $exception->getMessage()
            ));

            return [
                'key' => $jobKey,
                'status' => 'error',
                'summary' => [
                    'message' => $exception->getMessage(),
                ],
            ];
        }
    }

    private function isBackgroundJob(array $job): bool
    {
        return (bool) ($job['background'] ?? false);
    }

    private function logInfo(string $message): void
    {
        try {
            $this->loggerService->getLogger('cron-jobs')->info($message);
        } catch (\Throwable) {
        }
    }

    private function logError(string $message): void
    {
        try {
            $this->loggerService->getLogger('cron-jobs')->error($message);
        } catch (\Throwable) {
        }
    }
}
