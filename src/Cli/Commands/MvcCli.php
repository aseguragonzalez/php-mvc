<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Commands;

final class MvcCli
{
    /** @var array<string, Command> */
    private array $commands = [];

    private ConsoleOutput $output;

    public function __construct(?ConsoleOutput $output = null, ?StubGenerator $stubGenerator = null)
    {
        $this->output = $output ?? new ConsoleOutput();
        $stubGenerator ??= new StubGenerator();

        $this->register(new CreateAppCommand($this->output, $stubGenerator));
        $enableMigrations = new MigrationsEnableCommand($this->output, $stubGenerator);
        $this->register($enableMigrations);
        $this->register(new InitializeMigrationsCommand($enableMigrations));
        $this->register(new MigrationsDisableCommand($this->output));
        $this->register(new InitializeBackgroundTasksCommand($this->output, $stubGenerator));
        $this->register(new BackgroundTasksEnableCommand($this->output));
        $this->register(new BackgroundTasksDisableCommand($this->output));
        $this->register(new BackgroundTasksRunCommand($this->output));
        $this->register(new MigrationsCreateCommand($this->output));
        $this->register(new MigrationsRunCommand($this->output));
        $this->register(new MigrationsTestCommand($this->output));
        $this->register(new AuthenticationEnableCommand($this->output));
        $this->register(new AuthenticationDisableCommand($this->output));
        $this->register(new WatchAssetsCommand($this->output));
        $this->register(new CreateBundleCommand($this->output));
    }

    /**
     * @param array<string> $argv
     */
    public function run(array $argv): int
    {
        $args = array_slice($argv, 1);

        if (empty($args) || '--help' === $args[0] || '-h' === $args[0]) {
            $this->showHelp();

            return 0;
        }

        $commandName = $args[0];
        $commandArgs = array_slice($args, 1);

        if (!isset($this->commands[$commandName])) {
            $this->output->error("Unknown command: {$commandName}");
            $this->output->line();
            $this->showHelp();

            return 1;
        }

        return $this->commands[$commandName]->execute($commandArgs);
    }

    private function register(Command $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    private function showHelp(): void
    {
        $this->output->info('MVC CLI - Scaffolding tool');
        $this->output->line();
        $this->output->line('Usage: mvc <command> [options]');
        $this->output->line();
        $this->output->line('Available commands:');

        foreach ($this->commands as $command) {
            $name = str_pad($command->getName(), 30);
            $this->output->line("  {$name} {$command->getDescription()}");
        }

        $this->output->line();
        $this->output->line('Run mvc <command> --help for command-specific usage.');
    }
}
