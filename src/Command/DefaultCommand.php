<?php

namespace ControleOnline\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use ControleOnline\Service\DatabaseSwitchService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;


abstract class DefaultCommand extends Command
{
    protected $input;
    protected $output;
    protected $lock;
    protected $lockFactory;
    protected $databaseSwitchService;
    protected $loggerService;
    protected $skyNetService;
    protected MessageBusInterface $bus;
    protected EventDispatcherInterface $eventDispatcher;

    abstract protected function runCommand(): int;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->lock = $this->lockFactory->createLock($name);
        $this->addOption('domain', ['d'], InputOption::VALUE_OPTIONAL,  'Database domain identifier');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $domain = $input->getOption('domain');

        if ($domain) {
            $this->addLog(sprintf('Executando worker para o domínio: %s', $domain));
            if ($_ENV['MULTI_TENANCY'])
                $this->databaseSwitchService->switchDatabaseByDomain($domain);
            $this->skyNetService->discoveryBotUser();
            return $this->runCommand();
        }

        $domains = $this->databaseSwitchService->getAllDomains();

        foreach ($domains as $domain) {
            $this->addLog(sprintf('Executando migrações para o domínio: %s', $domain));
            if ($_ENV['MULTI_TENANCY'])
                $this->databaseSwitchService->switchDatabaseByDomain($domain);
            $this->skyNetService->discoveryBotUser();
            $this->runCommand();
        }

        return Command::SUCCESS;
    }

    public function addLog(string|iterable $messages, int $options = 0, ?string $logName = 'integration')
    {
        $this->output->writeln($messages, $options);
        $this->loggerService->getLogger($logName)->info($this->normalizeLogMessage($messages));
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

    public function __destruct()
    {
        $this->lock->release();
    }
}
