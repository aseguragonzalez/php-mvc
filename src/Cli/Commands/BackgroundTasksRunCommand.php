<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Commands;

use AlfonsoSG\Mvc\Config\MvcConfig;

final class BackgroundTasksRunCommand implements Command
{
    /** @var \Closure(string, list<string>): int */
    private \Closure $runner;

    /**
     * @param null|\Closure(string, list<string>): int $runner
     */
    public function __construct(
        private readonly ConsoleOutput $output,
        ?\Closure $runner = null,
    ) {
        $this->runner = $runner ?? static function (string $indexPath, array $forwardArgs): int {
            /** @var list<string> $forwardArgs */
            /** @var list<string> $cmd */
            $cmd = [PHP_BINARY, $indexPath, ...$forwardArgs];
            $descriptorSpec = [0 => STDIN, 1 => STDOUT, 2 => STDERR];
            $process = proc_open($cmd, $descriptorSpec, $pipes, null, null, ['bypass_shell' => true]);
            if (!is_resource($process)) {
                return 1;
            }

            return proc_close($process);
        };
    }

    public function getName(): string
    {
        return 'background-tasks:run';
    }

    public function getDescription(): string
    {
        return 'Run the BackgroundTasks entrypoint via mvc.config.json (subprocess)';
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

        $doubleIdx = array_search('--', $args, true);

        /** @var list<string> $optionArgs */
        $optionArgs = false !== $doubleIdx ? array_slice($args, 0, (int) $doubleIdx) : $args;

        /** @var list<string> $forwardArgs */
        $forwardArgs = false !== $doubleIdx ? array_slice($args, (int) $doubleIdx + 1) : [];

        $appPath = '.';
        $force = false;
        foreach ($optionArgs as $arg) {
            if (str_starts_with($arg, '--app-path=')) {
                $appPath = substr($arg, 11);

                continue;
            }
            if ('--force' === $arg) {
                $force = true;
            }
        }

        $resolvedPath = $this->resolvePath($appPath);

        if (!$this->isAppDirectory($resolvedPath)) {
            $this->output->error("Not a valid app directory (index.php not found): {$resolvedPath}");

            return 1;
        }

        $config = MvcConfig::load($resolvedPath);
        if (!$config->isBackgroundTasksEnabled() && !$force) {
            $this->output->error(
                'Background tasks are disabled in '.MvcConfig::CONFIG_FILENAME
                .'. Run: mvc background-tasks:enable --path='.$this->displayPath($resolvedPath)
                .' (or pass --force to run anyway).',
            );

            return 1;
        }

        $indexPath = BackgroundTasksAppPathResolver::resolveIndexPath($resolvedPath);
        if (null === $indexPath) {
            $this->output->error(
                'Background tasks entry not found. Run: mvc initialize-background-tasks '
                .'--path='.$this->displayPath($resolvedPath).' --name=<AppName> --namespace=<Namespace>',
            );

            return 1;
        }

        $this->output->info("Running: {$indexPath}");

        return ($this->runner)($indexPath, $forwardArgs);
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
        $this->output->line('Usage: mvc background-tasks:run [--app-path=<app-dir>] [--force] [--] [<args>...]');
        $this->output->line();
        $this->output->line('Options:');
        $this->output->line(
            '  --app-path=<app-dir>  MVC app root (default: current directory); uses mvc.config.json',
        );
        $this->output->line(
            '  --force               Run even when backgroundTasksEnabled is false (operators only)',
        );
        $this->output->line();
        $this->output->line(
            'Arguments after -- are passed to BackgroundTasks/index.php (e.g. -- --interval=60).',
        );
    }
}
