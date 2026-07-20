<?php

namespace ControleOnline\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use ControleOnline\Service\DatabaseSwitchService;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;


abstract class DefaultCommand extends Command
{
    protected $input;
    protected $output;
    protected $lock;
    protected $lockFactory;
    protected $databaseSwitchService;
    protected $loggerService;
    protected $skyNetService;
    protected ?EntityManagerInterface $cronTrackingEntityManager = null;
    protected MessageBusInterface $bus;
    protected EventDispatcherInterface $eventDispatcher;

    abstract protected function runCommand(): int;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->lock = $this->lockFactory->createLock($name);
        $this->addOption('domain', ['d'], InputOption::VALUE_OPTIONAL,  'Database domain identifier');
    }

    #[Required]
    public function setCronTrackingEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->cronTrackingEntityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $domain = $input->getOption('domain');

        if ($domain) {
            $this->addLog(sprintf('Executando worker para o domínio: %s', $domain));
            $this->setExecutionDomainContext((string) $domain);
            if ($_ENV['MULTI_TENANCY'])
                $this->databaseSwitchService->switchDatabaseByDomain($domain);
            $this->discoveryBotUser();
            return $this->runTrackedCommand();
        }

        $domains = $this->databaseSwitchService->getAllDomains();

        foreach ($domains as $domain) {
            $this->addLog(sprintf('Executando migrações para o domínio: %s', $domain));
            $this->setExecutionDomainContext((string) $domain);
            if ($_ENV['MULTI_TENANCY'])
                $this->databaseSwitchService->switchDatabaseByDomain($domain);
            $this->discoveryBotUser();
            $this->runCommand();
        }

        return Command::SUCCESS;
    }

    private function runTrackedCommand(): int
    {
        $status = 'success';

        try {
            $exitCode = $this->runCommand();
            $status = $exitCode === Command::SUCCESS ? 'success' : 'failure';

            return $exitCode;
        } catch (\Throwable $exception) {
            $status = 'failure';

            throw $exception;
        } finally {
            $this->recordCronJobExecution($status);
        }
    }

    private function recordCronJobExecution(string $status): void
    {
        if (!$this->cronTrackingEntityManager) {
            return;
        }

        $commandName = trim((string) $this->getName());
        if ($commandName === '') {
            return;
        }

        $normalizedStatus = strtolower(trim($status));
        if (!in_array($normalizedStatus, ['success', 'failure'], true)) {
            $normalizedStatus = 'failure';
        }

        try {
            $this->cronTrackingEntityManager->getConnection()->executeStatement(
                'UPDATE cron_jobs SET last_execution_at = ?, last_status = ? WHERE command = ?',
                [
                    new \DateTimeImmutable(),
                    $normalizedStatus,
                    $commandName,
                ],
                [
                    Types::DATETIME_IMMUTABLE,
                    ParameterType::STRING,
                    ParameterType::STRING,
                ]
            );
        } catch (\Throwable) {
            // Cron tracking must never fail the command itself.
        }
    }

    public function addLog(string|iterable $messages, int $options = 0, ?string $logName = 'integration')
    {
        $this->output->writeln($messages, $options);

        if (!$this->loggerService) {
            return;
        }

        try {
            $this->loggerService->getLogger($logName)->info($this->normalizeLogMessage($messages));
        } catch (\Throwable) {
            // Console output must not fail because database logging is unavailable.
        }
    }

    private function normalizeLogMessage(string|iterable $messages): string
    {
        if (is_string($messages)) {
            return $messages;
        }

        $normalized = [];
        foreach ($messages as $key => $message) {
            $normalized[$key] = (string) $message;
        }

        return json_encode(
            $normalized,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
        ) ?: '[]';
    }

    private function discoveryBotUser(): void
    {
        if (!$this->skyNetService) {
            return;
        }

        try {
            $this->skyNetService->discoveryBotUser();
        } catch (\Throwable $exception) {
            $this->addLog(
                sprintf('Bot user discovery skipped: %s', $exception->getMessage())
            );
        }
    }

    private function setExecutionDomainContext(string $domain): void
    {
        $domain = trim($domain);

        if ($domain === '') {
            return;
        }

        $_ENV['APP_DOMAIN'] = $domain;
        $_SERVER['APP_DOMAIN'] = $domain;
        $_SERVER['HTTP_HOST'] = $domain;
        putenv(sprintf('APP_DOMAIN=%s', $domain));
    }

    public function __destruct()
    {
        $this->lock->release();
    }
}
