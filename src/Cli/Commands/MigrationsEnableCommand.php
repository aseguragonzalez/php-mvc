<?php

declare(strict_types=1);

namespace PhpMvc\Commands;

use PhpMvc\Config\MvcConfig;

final class MigrationsEnableCommand implements Command
{
    public function __construct(
        private readonly ConsoleOutput $output,
        private readonly StubGenerator $stubGenerator,
    ) {}

    public function getName(): string
    {
        return 'migrations:enable';
    }

    public function getDescription(): string
    {
        return 'Enable the Migrations feature and scaffold the migration module';
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

        try {
            $folderName = $this->parseFolder($args);
        } catch (\InvalidArgumentException $e) {
            $this->output->error($e->getMessage());

            return 1;
        }
        $migrationsDir = $resolvedPath.'/'.$folderName;
        if (is_dir($migrationsDir)) {
            $this->output->error("Migrations module directory already exists: {$migrationsDir}");

            return 1;
        }

        $projectRoot = $this->findProjectRoot($resolvedPath);
        if (null === $projectRoot) {
            $this->output->error('Could not find project root (no composer.json found in parent directories).');

            return 1;
        }

        $autoloadPath = $this->computeAutoloadPath($migrationsDir, $projectRoot);

        $this->output->info("Enabling Migrations in {$resolvedPath} (module folder: {$folderName})");

        mkdir($migrationsDir, 0o755, true);
        mkdir($migrationsDir.'/migrations', 0o755, true);

        $configFolderPath = './'.$folderName;
        MvcConfig::writeMergedToApp($resolvedPath, [
            'migrationsFolderPath' => $configFolderPath,
            'migrationsEnabled' => true,
        ]);

        $content = $this->stubGenerator->generate('migrations-index.stub', [
            'autoloadPath' => $autoloadPath,
        ]);
        file_put_contents($migrationsDir.'/index.php', $content);
        $this->output->line("  Created {$folderName}/index.php");
        $this->output->line("  Created {$folderName}/migrations/");

        $this->output->success('Migrations feature enabled successfully.');

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

    /**
     * @param array<string> $args
     */
    private function parseFolder(array $args): string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--folder=')) {
                return $this->normalizeFolderName(substr($arg, 9));
            }
        }

        return 'Migrations';
    }

    private function normalizeFolderName(string $folder): string
    {
        $folder = trim(str_replace('\\', '/', $folder));
        $folder = trim($folder, '/');
        if ('' === $folder) {
            return 'Migrations';
        }
        if (str_contains($folder, '..')) {
            throw new \InvalidArgumentException('Invalid --folder: path segments ".." are not allowed.');
        }

        return $folder;
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

    private function findProjectRoot(string $fromPath): ?string
    {
        $current = $fromPath;
        while ($current !== dirname($current)) {
            if (file_exists($current.'/composer.json')) {
                return $current;
            }
            $current = dirname($current);
        }

        return null;
    }

    private function computeAutoloadPath(string $targetDir, string $projectRoot): string
    {
        $targetDir = rtrim($targetDir, '/');
        $projectRoot = rtrim($projectRoot, '/');

        return $this->getRelativePath($targetDir, $projectRoot).'/vendor/autoload.php';
    }

    private function getRelativePath(string $from, string $to): string
    {
        $fromParts = explode('/', $from);
        $toParts = explode('/', $to);

        $commonLength = 0;
        $maxLength = min(count($fromParts), count($toParts));
        for ($i = 0; $i < $maxLength; ++$i) {
            if ($fromParts[$i] !== $toParts[$i]) {
                break;
            }
            ++$commonLength;
        }

        $upCount = count($fromParts) - $commonLength;
        $downParts = array_slice($toParts, $commonLength);

        return implode('/', array_merge(array_fill(0, $upCount, '..'), $downParts));
    }

    private function showHelp(): void
    {
        $this->output->line('Usage: mvc migrations:enable [--path=<app-path>] [--folder=<module-folder>]');
        $this->output->line();
        $this->output->line('Options:');
        $this->output->line('  --path=<app-path>          Path to the MVC app directory (default: current directory)');
        $this->output->line(
            '  --folder=<module-folder>   Migration module folder under the app root (default: Migrations)',
        );
    }
}
