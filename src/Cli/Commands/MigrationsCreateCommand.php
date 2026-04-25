<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Commands;

final class MigrationsCreateCommand implements Command
{
    use MigrationsCommandPathTrait;

    public function __construct(
        private readonly ConsoleOutput $output,
    ) {}

    public function getName(): string
    {
        return 'migrations:create';
    }

    public function getDescription(): string
    {
        return 'Create a new migration with timestamped folder and SQL stubs';
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

        $timestamp = date('YmdHis');
        $migrationDir = $resolvedPath.'/'.$timestamp;

        if (!mkdir($migrationDir, 0o755, true)) {
            $this->output->error("Failed to create migration directory: {$migrationDir}");

            return 1;
        }

        $migrationFile = $migrationDir.'/0001_migration.sql';
        $rollbackFile = $migrationDir.'/0001_migration.rollback.sql';

        file_put_contents($migrationFile, "-- Migration file\n");
        file_put_contents($rollbackFile, "-- Rollback file\n");

        $this->output->success('Migration created successfully!');
        $this->output->line("  Directory: {$migrationDir}");
        $this->output->line("  Migration file: {$migrationFile}");
        $this->output->line("  Rollback file: {$rollbackFile}");

        return 0;
    }

    private function showHelp(): void
    {
        $this->output->line('Usage: mvc migrations:create [--app-path=<app-dir>] [--path=<migrations-dir>]');
        $this->output->line();
        $this->output->line('Options:');
        $this->output->line(
            '  --app-path=<app-dir>     MVC app root (default: current directory); uses mvc.config.json',
        );
        $this->output->line('  --path=<migrations-dir>  Override path to the leaf migrations directory');
        $this->output->line();
        $this->output->line('Creates a new timestamped migration folder with empty SQL stubs.');
    }
}
