<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Commands;

use AlfonsoSG\Mvc\Config\MvcConfig;

final class MigrationsDisableCommand implements Command
{
    public function __construct(
        private readonly ConsoleOutput $output,
    ) {}

    public function getName(): string
    {
        return 'migrations:disable';
    }

    public function getDescription(): string
    {
        return 'Disable the Migrations feature in mvc.config.json';
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

        $appPath = $this->parsePath($args);
        $resolvedPath = $this->resolvePath($appPath);

        if (!$this->isAppDirectory($resolvedPath)) {
            $this->output->error("Not a valid app directory (index.php not found): {$resolvedPath}");

            return 1;
        }

        $configPath = $resolvedPath.'/'.MvcConfig::CONFIG_FILENAME;
        if (!is_file($configPath)) {
            $this->output->error('No '.MvcConfig::CONFIG_FILENAME." found in: {$resolvedPath}");

            return 1;
        }

        $removeFiles = in_array('--remove-files', $args, true);
        $force = in_array('--force', $args, true);

        if ($removeFiles && !$force) {
            $this->output->error(
                'Refusing to delete files without --force. '
                .'Use: mvc migrations:disable --remove-files --force ...',
            );

            return 1;
        }

        $config = MvcConfig::load($resolvedPath);
        $moduleRelative = $config->effectiveMigrationsModuleRelativePath();
        $moduleDir = $resolvedPath.'/'.$moduleRelative;

        MvcConfig::writeMergedToApp($resolvedPath, [
            'migrationsEnabled' => false,
            'migrationsFolderPath' => '',
        ]);

        $this->output->info("Migrations disabled in {$resolvedPath} (".MvcConfig::CONFIG_FILENAME.')');

        if ($removeFiles) {
            if (is_dir($moduleDir)) {
                $this->removeDirectoryRecursive($moduleDir);
                $this->output->line("  Removed directory: {$moduleDir}");
            } else {
                $this->output->line("  No module directory to remove: {$moduleDir}");
            }
        }

        $this->output->success('Migrations feature disabled.');

        return 0;
    }

    /**
     * @param array<string> $args
     */
    private function parsePath(array $args): string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--path=')) {
                return substr($arg, 7);
            }
        }

        return '.';
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/') || str_contains($path, '://')) {
            return rtrim($path, '/');
        }

        return rtrim(getcwd().'/'.$path, '/');
    }

    private function isAppDirectory(string $path): bool
    {
        return is_dir($path) && file_exists($path.'/index.php');
    }

    private function removeDirectoryRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if (false === $items) {
            return;
        }
        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $path = $dir.'/'.$item;
            if (is_dir($path)) {
                $this->removeDirectoryRecursive($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    private function showHelp(): void
    {
        $this->output->line('Usage: mvc migrations:disable [--path=<app-path>] [--remove-files] [--force]');
        $this->output->line();
        $this->output->line('Options:');
        $this->output->line('  --path=<app-path>  Path to the MVC app directory (default: current directory)');
        $this->output->line('  --remove-files     Delete the migration module directory from disk');
        $this->output->line('  --force            Required with --remove-files to confirm deletion');
    }
}
