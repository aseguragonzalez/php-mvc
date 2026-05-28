<?php

declare(strict_types=1);

namespace PhpMvc\Commands;

use PhpMvc\Config\MvcConfig;

final class AuthenticationEnableCommand implements Command
{
    public function __construct(
        private readonly ConsoleOutput $output,
    ) {}

    public function getName(): string
    {
        return 'auth:enable';
    }

    public function getDescription(): string
    {
        return 'Enable authentication (and authorization) in mvc.config.json and add default auth migration';
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

        $resolvedPath = $this->resolvePath($this->parsePath($args));

        if (!$this->isAppDirectory($resolvedPath)) {
            $this->output->error("Not a valid app directory (index.php not found): {$resolvedPath}");

            return 1;
        }

        $config = MvcConfig::load($resolvedPath);
        if ($config->isAuthenticationEnabled()) {
            $this->output->info('Authentication is already enabled in '.MvcConfig::CONFIG_FILENAME.'.');

            return 0;
        }

        $skipMigrations = in_array('--skip-migrations', $args, true);

        if (!$skipMigrations) {
            $leaf = MigrationsLeafPathResolver::resolveLeafMigrationsDir($resolvedPath);
            if (null === $leaf) {
                $this->output->error(
                    'Cannot create auth migrations: migrations module not found or disabled. '
                    .'Run: mvc migrations:enable --path='.$this->displayPath($resolvedPath)
                    .' (then re-run this command), or use --skip-migrations if you use custom auth storage.',
                );

                return 1;
            }

            try {
                $migrationDir = AuthDefaultMigrationWriter::createEnableMigration($leaf);
            } catch (\Throwable $e) {
                $this->output->error($e->getMessage());

                return 1;
            }

            $this->output->line("  Created migration: {$migrationDir}");
        }

        MvcConfig::writeMergedToApp($resolvedPath, [
            'authenticationEnabled' => true,
        ]);

        $this->output->success('Authentication enabled in '.MvcConfig::CONFIG_FILENAME.'.');
        if (!$skipMigrations) {
            $this->output->line('  Run pending migrations when ready: mvc migrations:run --app-path=<app-dir>');
        }

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

        $cwd = getcwd();
        if (false === $cwd) {
            throw new \RuntimeException('Could not resolve current working directory.');
        }

        return rtrim($cwd.'/'.$path, '/');
    }

    private function isAppDirectory(string $path): bool
    {
        return is_dir($path) && file_exists($path.'/index.php');
    }

    private function displayPath(string $absolutePath): string
    {
        $cwd = getcwd();
        if (false !== $cwd) {
            $prefix = rtrim($cwd, '/').'/';
            if (str_starts_with($absolutePath, $prefix)) {
                return '.'.substr($absolutePath, strlen($prefix));
            }
        }

        return $absolutePath;
    }

    private function showHelp(): void
    {
        $this->output->line('Usage: mvc auth:enable [--path=<app-path>] [--skip-migrations]');
        $this->output->line();
        $this->output->line('Options:');
        $this->output->line('  --path=<app-path>     Path to the MVC app directory (default: current directory)');
        $this->output->line(
            '  --skip-migrations     Only set authenticationEnabled in mvc.config.json (no SQL files)',
        );
        $this->output->line();
        $this->output->line(
            'Without --skip-migrations, requires an enabled migrations module (see mvc migrations:enable).',
        );
    }
}
