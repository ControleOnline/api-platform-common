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

        return $this->runConfiguredJob($job);
    }

    public function runConfiguredJob(array $job): array
    {
        $jobKey = (string) ($job['key'] ?? $job['id'] ?? '');

        if (!($job['isValid'] ?? false)) {
            return $this->finishIgnoredJob($job, $jobKey, 'Cron expression is invalid.');
        }

        $command = trim((string) ($job['command'] ?? ''));
        if ($command === '') {
            return $this->finishIgnoredJob($job, $jobKey, 'Cron job command is empty.');
        }

        if (!($job['enabled'] ?? false)) {
            return $this->finishIgnoredJob($job, $jobKey, 'Cron job is disabled.');
        }

        $arguments = $this->buildExecutionArguments($job);
        $startedAt = new \DateTimeImmutable();

        $process = new Process(
            array_merge(
                [PHP_BINARY, $this->kernel->getProjectDir() . '/bin/console', $command],
                $arguments
            ),
            $this->kernel->getProjectDir()
        );
        $process->setTimeout(null);

        try {
            // Messenger consumers and websocket servers are long-lived services.
            // The central scheduler must start them without waiting, and must
            // not create a duplicate process on the next one-minute tick.
            if ($this->isLongLivedCommand($command)) {
                if ($this->isProcessAlreadyRunning($command, $arguments)) {
                    $finishedAt = new \DateTimeImmutable();
                    $summary = [
                        'exitCode' => 0,
                        'commandLine' => $process->getCommandLine(),
                        'message' => 'Long-lived process is already running.',
                    ];
                    $this->cronJobService->recordExecution($job, 'success', $startedAt, $finishedAt, 0, $summary);

                    return [
                        'key' => $jobKey,
                        'status' => 'success',
                        'summary' => $summary,
                    ];
                }

                $detachedCommand = implode(' ', array_map(
                    static fn(string $part): string => escapeshellarg($part),
                    array_merge(
                        [PHP_BINARY, $this->kernel->getProjectDir() . '/bin/console', $command],
                        $arguments
                    )
                ));
                $launcher = Process::fromShellCommandline(
                    'nohup ' . $detachedCommand . ' >/dev/null 2>&1 &',
                    $this->kernel->getProjectDir()
                );
                $launcher->run();
                $finishedAt = new \DateTimeImmutable();
                $summary = [
                    'exitCode' => 0,
                    'commandLine' => $detachedCommand,
                    'message' => 'Long-lived process started by the central scheduler.',
                ];
                $this->cronJobService->recordExecution($job, 'success', $startedAt, $finishedAt, 0, $summary);

                return [
                    'key' => $jobKey,
                    'status' => 'success',
                    'summary' => $summary,
                ];
            }

            $process->run();

            $finishedAt = new \DateTimeImmutable();
            $status = $process->isSuccessful() ? 'success' : 'failure';
            $summary = [
                'exitCode' => $process->getExitCode(),
                'commandLine' => $process->getCommandLine(),
                'output' => $process->getOutput(),
                'errorOutput' => $process->getErrorOutput(),
            ];

            $this->cronJobService->recordExecution(
                $job,
                $status,
                $startedAt,
                $finishedAt,
                $process->getExitCode(),
                $summary
            );

            $this->logInfo(
                sprintf(
                    '[cron:%s] finished | status=%s | command=%s',
                    (string) ($job['id'] ?? $jobKey),
                    $status,
                    $process->getCommandLine()
                ),
                $this->buildLogContext($job, [
                    'status' => $status,
                    'exitCode' => $process->getExitCode(),
                    'commandLine' => $process->getCommandLine(),
                ])
            );

            return [
                'key' => $jobKey,
                'status' => $status,
                'summary' => $summary,
            ];
        } catch (\Throwable $exception) {
            $finishedAt = new \DateTimeImmutable();
            $summary = [
                'message' => $exception->getMessage(),
                'commandLine' => $process->getCommandLine(),
            ];

            $this->cronJobService->recordExecution(
                $job,
                'error',
                $startedAt,
                $finishedAt,
                null,
                $summary
            );

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

    private function finishIgnoredJob(array $job, string $jobKey, string $message): array
    {
        $now = new \DateTimeImmutable();
        $summary = ['message' => $message];

        $this->cronJobService->recordExecution($job, 'ignored', $now, $now, null, $summary);

        return [
            'key' => $jobKey,
            'status' => 'ignored',
            'summary' => $summary,
        ];
    }

    private function buildExecutionArguments(array $job): array
    {
        $arguments = array_values(array_filter(
            array_map(
                static fn(mixed $argument): string => trim((string) $argument),
                $job['arguments'] ?? []
            ),
            static fn(string $argument): bool => $argument !== ''
        ));

        $domain = trim((string) ($job['domain'] ?? ''));
        if (strtolower($command) === 'tenant:messenger:consume') {
            // TenantConsumeCommand is the single rotating worker. The cron
            // job is expanded per tenant for reporting, but the process must
            // not receive a tenant domain and must be started only once.
            return $arguments;
        }

        if ($domain === '') {
            return $arguments;
        }

        $arguments = array_values(array_filter(
            $arguments,
            static fn(string $argument): bool => !str_starts_with($argument, '--domain')
        ));
        $arguments[] = '--domain=' . $domain;

        return $arguments;
    }

    private function isLongLivedCommand(string $command): bool
    {
        return in_array(strtolower($command), [
            'tenant:messenger:consume',
            'websocket:start',
        ], true);
    }

    private function isProcessAlreadyRunning(string $command, array $arguments): bool
    {
        $process = new Process(['ps', '-eo', 'args=']);
        $process->run();
        if (!$process->isSuccessful()) {
            return false;
        }

        $needle = 'bin/console ' . $command;
        $domain = '';
        foreach ($arguments as $argument) {
            if (str_starts_with($argument, '--domain=')) {
                $domain = substr($argument, strlen('--domain='));
                break;
            }
        }

        foreach (preg_split('/\R/', $process->getOutput()) ?: [] as $line) {
            if (!str_contains($line, $needle)) {
                continue;
            }

            if ($command === 'websocket:start' || $domain === '' || str_contains($line, '--domain=' . $domain)) {
                return true;
            }
        }

        return false;
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
