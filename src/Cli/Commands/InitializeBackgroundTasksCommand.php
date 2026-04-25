<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Commands;

use AlfonsoSG\Mvc\Config\MvcConfig;

final class InitializeBackgroundTasksCommand implements Command
{
    public function __construct(
        private readonly ConsoleOutput $output,
        private readonly StubGenerator $stubGenerator,
    ) {}

    public function getName(): string
    {
        return 'initialize-background-tasks';
    }

    public function getDescription(): string
    {
        return 'Add the BackgroundTasks feature to an existing MVC app';
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

        $parsed = $this->parseArgs($args);

        if (null === $parsed) {
            return 1;
        }

        /** @var string $appPath */
        $appPath = $parsed['path'];

        /** @var string $name */
        $name = $parsed['name'];

        /** @var string $namespace */
        $namespace = $parsed['namespace'];

        $resolvedPath = $this->resolvePath($appPath);

        if (!$this->isAppDirectory($resolvedPath)) {
            $this->output->error("Not a valid app directory (index.php not found): {$resolvedPath}");

            return 1;
        }

        $bgTasksDir = $resolvedPath.'/BackgroundTasks';
        if (is_dir($bgTasksDir)) {
            $this->output->error("BackgroundTasks directory already exists: {$bgTasksDir}");

            return 1;
        }

        $projectRoot = $this->findProjectRoot($resolvedPath);
        if (null === $projectRoot) {
            $this->output->error('Could not find project root (no composer.json found in parent directories).');

            return 1;
        }

        $autoloadPath = $this->computeAutoloadPath($bgTasksDir, $projectRoot);

        $replacements = [
            'name' => $name,
            'namespace' => $namespace,
            'autoloadPath' => $autoloadPath,
            'nameKebab' => $this->toKebabCase($name),
            'nameSnake' => $this->toSnakeCase($name),
            'envPrefix' => strtoupper($this->toSnakeCase($name)),
        ];

        $this->output->info("Initializing BackgroundTasks in {$resolvedPath}");

        mkdir($bgTasksDir, 0o755, true);
        mkdir($bgTasksDir.'/Handlers', 0o755, true);
        mkdir($bgTasksDir.'/Tasks', 0o755, true);

        MvcConfig::writeMergedToApp($resolvedPath, [
            'backgroundTasksFolderPath' => './BackgroundTasks',
            'backgroundTasksEnabled' => false,
        ]);

        $indexContent = $this->stubGenerator->generate('background-tasks-index.stub', $replacements);
        file_put_contents($bgTasksDir.'/index.php', $indexContent);
        $this->output->line('  Created BackgroundTasks/index.php');

        $bootstrapContent = $this->stubGenerator->generate('background-tasks-bootstrap.stub', $replacements);
        file_put_contents($bgTasksDir.'/'.$name.'BackgroundTasksBootstrap.php', $bootstrapContent);
        $this->output->line("  Created BackgroundTasks/{$name}BackgroundTasksBootstrap.php");

        $this->output->line('  Created BackgroundTasks/Handlers/');
        $this->output->line('  Created BackgroundTasks/Tasks/');

        $this->output->success('BackgroundTasks feature initialized successfully.');

        return 0;
    }

    /**
     * @param array<string> $args
     *
     * @return null|array<string, string>
     */
    private function parseArgs(array $args): ?array
    {
        $path = '.';
        $name = null;
        $namespace = null;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--path=')) {
                $path = substr($arg, 7);
            } elseif (str_starts_with($arg, '--name=')) {
                $name = substr($arg, 7);
            } elseif (str_starts_with($arg, '--namespace=')) {
                $namespace = substr($arg, 12);
            }
        }

        if (null === $name || '' === $name) {
            $this->output->error('Missing required option: --name=<AppName>');
            $this->showHelp();

            return null;
        }

        if (null === $namespace || '' === $namespace) {
            $this->output->error('Missing required option: --namespace=<Namespace>');
            $this->showHelp();

            return null;
        }

        return ['path' => $path, 'name' => $name, 'namespace' => $namespace];
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

    private function toKebabCase(string $name): string
    {
        $result = preg_replace('/([a-z])([A-Z])/', '$1-$2', $name);

        return strtolower($result ?? $name);
    }

    private function toSnakeCase(string $name): string
    {
        $result = preg_replace('/([a-z])([A-Z])/', '$1_$2', $name);

        return strtolower($result ?? $name);
    }

    private function showHelp(): void
    {
        $this->output->line(
            'Usage: mvc initialize-background-tasks [--path=<app-path>] --name=<AppName> --namespace=<Namespace>'
        );
        $this->output->line();
        $this->output->line('Options:');
        $this->output->line('  --path=<app-path>       Path to the MVC app directory (default: current directory)');
        $this->output->line('  --name=<AppName>        Class name prefix (e.g. MyApp)');
        $this->output->line('  --namespace=<Namespace> PSR-4 namespace (e.g. App\Ports\MyApp)');
    }
}
