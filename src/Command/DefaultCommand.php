<?php

namespace ControleOnline\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use ControleOnline\Service\DatabaseSwitchService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

abstract class DefaultCommand extends Command
{
    protected $input;
    protected $output;
    protected $lock;
    protected $lockFactory;
    protected $databaseSwitchService;
    protected $loggerService;

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
            $this->addLog(sprintf('Executando migrações para o domínio: %s', $domain));
            $this->databaseSwitchService->switchDatabaseByDomain($domain);
            return $this->runCommand();
        }

        $domains = $this->databaseSwitchService->getAllDomains();

        foreach ($domains as $domain) {
            $this->addLog(sprintf('Executando migrações para o domínio: %s', $domain));
            $this->databaseSwitchService->switchDatabaseByDomain($domain);
            $this->runCommand();
        }

        return Command::SUCCESS;
    }

    public function addLog(string|iterable $messages, int $options = 0, ?string $logName = 'integration')
    {
        $this->output->writeln($messages, $options);
        $this->loggerService->getLogger($logName)->info($messages);
    }

    public function __destruct()
    {
        $this->lock->release();
    }
}
