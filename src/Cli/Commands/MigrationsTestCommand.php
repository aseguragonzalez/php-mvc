<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Commands;

use AlfonsoSG\Mvc\Migrations\MigrationApp;
use AlfonsoSG\Mvc\Migrations\MigrationBootstrap;
use DI\Container;

final class MigrationsTestCommand implements Command
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
            $container = new Container();
            MigrationBootstrap::registerFromEnvironment($container);
            $app = new MigrationApp(
                container: $container,
                basePath: $basePath,
            );

            // @var array<string> $argv
            return $app->run(count($argv), $argv);
        };
    }

    public function getName(): string
    {
        return 'migrations:test';
    }

    public function getDescription(): string
    {
        return 'Test a specific migration (run + rollback + schema comparison)';
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

        $migrationName = $this->parseMigrationName($args);

        if (null === $migrationName) {
            $this->output->error('The --migration option is required.');
            $this->output->line();
            $this->showHelp();

            return 1;
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

        $this->output->info("Testing migration '{$migrationName}' from: {$resolvedPath}");

        return ($this->migrationRunner)($resolvedPath, ["--test={$migrationName}"]);
    }

    /**
     * @param array<string> $args
     */
    private function parseMigrationName(array $args): ?string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--migration=')) {
                return substr($arg, 12);
            }
        }

        return null;
    }

    private function showHelp(): void
    {
        $this->output->line(
            'Usage: mvc migrations:test [--app-path=<app-dir>] [--path=<migrations-dir>] --migration=<name>',
        );
        $this->output->line();
        $this->output->line('Options:');
        $this->output->line(
            '  --app-path=<app-dir>     MVC app root (default: current directory); uses mvc.config.json',
        );
        $this->output->line('  --path=<migrations-dir>  Override path to the leaf migrations directory');
        $this->output->line('  --migration=<name>       Name of the migration to test (required)');
        $this->output->line();
        $this->output->line('Tests a migration by running it, then rolling back, and comparing schemas.');
    }
}
