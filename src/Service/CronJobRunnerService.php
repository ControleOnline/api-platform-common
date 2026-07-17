<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\CronJob;
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

        try {
            $process->disableOutput();
            $process->setOptions(['create_new_console' => true]);
            $process->start();

            $this->logInfo(
                sprintf(
                    '[cron:%s] started | pid=%s | command=%s',
                    (string) ($job['id'] ?? $jobKey),
                    (string) ($process->getPid() ?? ''),
                    $process->getCommandLine()
                ),
                $this->buildLogContext($job, [
                    'pid' => $process->getPid(),
                    'commandLine' => $process->getCommandLine(),
                ])
            );

            return [
                'key' => $jobKey,
                'status' => 'started',
                'summary' => [
                    'pid' => $process->getPid(),
                    'commandLine' => $process->getCommandLine(),
                ],
            ];
        } catch (\Throwable $exception) {
            $this->logError(
                sprintf(
                    '[cron:%s] failed | %s',
                    (string) ($job['id'] ?? $jobKey),
                    $exception->getMessage()
                ),
                $this->buildLogContext($job, [
                    'error' => $exception->getMessage(),
                ])
            );

            return [
                'key' => $jobKey,
                'status' => 'error',
                'summary' => [
                    'message' => $exception->getMessage(),
                ],
            ];
        }
    }

    private function buildLogContext(array $job, array $context = []): array
    {
        return [
            'entityClass' => CronJob::class,
            'entityRow' => isset($job['id']) ? (int) $job['id'] : null,
            'cronJobId' => isset($job['id']) ? (int) $job['id'] : null,
            'cronJobTitle' => trim((string) ($job['title'] ?? '')),
            'cronJobCommand' => trim((string) ($job['command'] ?? '')),
            ...$context,
        ];
    }

    private function logInfo(string $message, array $context = []): void
    {
        try {
            $this->loggerService->getLogger('cron-jobs')->info($message, $context);
        } catch (\Throwable) {
        }
    }

    private function logError(string $message, array $context = []): void
    {
        try {
            $this->loggerService->getLogger('cron-jobs')->error($message, $context);
        } catch (\Throwable) {
        }
    }
}
