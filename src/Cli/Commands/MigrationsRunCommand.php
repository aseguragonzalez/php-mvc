<?php

declare(strict_types=1);

namespace PhpMvc\Commands;

final class MigrationsRunCommand implements Command
{
    use MigrationsCommandPathTrait;

    /** @var \Closure(string, array<string>): int */
    private \Closure $migrationRunner;

    /**
     * @param null|\Closure(string, array<string>): int $migrationRunner
     */
    public function __construct(
        private readonly ConsoleOutput $output,
        ?\Closure $migrationRunner = null,
    ) {
        $this->migrationRunner = $migrationRunner ?? static function (string $basePath, array $argv): int {
            throw new \LogicException(
                'No migration runner provided. Inject a \Closure(string, array<string>): int when constructing '
                .MigrationsRunCommand::class.'. See the framework documentation for an example bootstrap.'
            );
        };
    }

    public function getName(): string
    {
        return 'migrations:run';
    }

    public function getDescription(): string
    {
        return 'Run all pending migrations';
    }

    /**
     * @param array<string> $args
     */
    public function execute(array $args): int
    {
        if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
            $this->showHelp();

            return 0;
        }

        $resolvedPath = $this->resolveLeafMigrationsDirFromArgs($args);

        if (null === $resolvedPath || !is_dir($resolvedPath)) {
            if ($this->hasExplicitLeafPath($args)) {
                $this->output->error(
                    'Migrations directory does not exist: '.($resolvedPath ?? '(invalid path)'),
                );
            } else {
                $this->output->error(
                    'Could not resolve migrations directory. Use --path=<migrations-dir> or --app-path=<app-dir> '
                    .'where mvc.config.json enables migrations and the module exists.',
                );
            }
            $this->output->line();
            $this->showHelp();

            return 1;
        }

        $this->output->info("Running pending migrations from: {$resolvedPath}");

        return ($this->migrationRunner)($resolvedPath, []);
    }

    private function showHelp(): void
    {
        $this->output->line('Usage: mvc migrations:run [--app-path=<app-dir>] [--path=<migrations-dir>]');
        $this->output->line();
        $this->output->line('Options:');
        $this->output->line(
            '  --app-path=<app-dir>     MVC app root (default: current directory); uses mvc.config.json',
        );
        $this->output->line('  --path=<migrations-dir>  Override path to the leaf migrations directory');
        $this->output->line();
        $this->output->line('Runs all pending migrations that have not yet been executed.');
    }
}
