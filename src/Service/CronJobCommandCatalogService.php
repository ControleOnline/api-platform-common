<?php

namespace ControleOnline\Service;

use Symfony\Bundle\FrameworkBundle\Console\Application as FrameworkConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\HttpKernel\KernelInterface;

class CronJobCommandCatalogService
{
    public function __construct(
        private KernelInterface $kernel,
    ) {}

    /**
     * @return array<int, array{
     *     name: string,
     *     label: string,
     *     description: string,
     *     aliases: array<int, string>,
     *     class: string,
     *     group: string
     * }>
     */
    public function getAvailableCommands(): array
    {
        $application = $this->createApplication();
        $catalog = [];

        foreach ($application->all() as $name => $command) {
            $resolvedCommand = $this->resolveCommand($command);
            if (!$this->shouldExposeCommand($resolvedCommand)) {
                continue;
            }

            $resolvedName = trim($resolvedCommand->getName() ?: (string) $name);
            if ($resolvedName === '' || str_starts_with($resolvedName, 'app:cron:')) {
                continue;
            }

            $catalog[$resolvedName] = [
                'name' => $resolvedName,
                'label' => $this->resolveCommandLabel($resolvedName, $resolvedCommand),
                'description' => trim((string) $resolvedCommand->getDescription()),
                'aliases' => array_values(array_filter(
                    array_map(
                        static fn($alias): string => trim((string) $alias),
                        $resolvedCommand->getAliases()
                    ),
                    static fn($alias): bool => trim((string) $alias) !== ''
                )),
                'class' => $this->resolveCommandClass($resolvedCommand),
                'group' => $this->resolveCommandGroup($resolvedName),
            ];
        }

        $commands = array_values($catalog);
        usort(
            $commands,
            static function (array $left, array $right): int {
                return [$left['group'], $left['name']] <=> [$right['group'], $right['name']];
            }
        );

        return $commands;
    }

    protected function createApplication(): FrameworkConsoleApplication
    {
        $application = new FrameworkConsoleApplication($this->kernel);
        $application->setAutoExit(false);

        return $application;
    }

    protected function resolveCommand(Command $command): Command
    {
        if ($command instanceof LazyCommand) {
            return $command->getCommand();
        }

        return $command;
    }

    protected function resolveCommandClass(Command $command): string
    {
        return get_class($this->resolveCommand($command));
    }

    protected function shouldExposeCommand(Command $command): bool
    {
        $resolvedCommand = $this->resolveCommand($command);
        $commandName = trim((string) $resolvedCommand->getName());

        if ($commandName === '' || str_starts_with($commandName, 'app:cron:')) {
            return false;
        }

        if (!$resolvedCommand->isEnabled() || $resolvedCommand->isHidden()) {
            return false;
        }

        $commandClass = $this->resolveCommandClass($resolvedCommand);

        return str_starts_with($commandClass, 'App\\')
            || str_starts_with($commandClass, 'ControleOnline\\');
    }

    protected function resolveCommandLabel(string $name, Command $command): string
    {
        return $name;
    }

    protected function resolveCommandGroup(string $name): string
    {
        $group = explode(':', $name)[0] ?? '';

        return trim((string) $group);
    }
}
