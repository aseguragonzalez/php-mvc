<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Commands;

/**
 * Resolves the leaf `migrations` directory from --path or from mvc.config.json via --app-path.
 *
 * @internal
 */
trait MigrationsCommandPathTrait
{
    /**
     * @param array<string> $args
     */
    private function hasExplicitLeafPath(array $args): bool
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--path=')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string> $args
     */
    private function resolveLeafMigrationsDirFromArgs(array $args): ?string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--path=')) {
                return $this->resolveShellPath(substr($arg, 7));
            }
        }

        $appPath = $this->parseAppPathArg($args);
        $resolvedApp = $this->resolveShellPath($appPath);

        return MigrationsLeafPathResolver::resolveLeafMigrationsDir($resolvedApp);
    }

    /**
     * @param array<string> $args
     */
    private function parseAppPathArg(array $args): string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--app-path=')) {
                return substr($arg, 11);
            }
        }

        return '.';
    }

    private function resolveShellPath(string $path): string
    {
        if (str_starts_with($path, '/') || str_contains($path, '://')) {
            return rtrim($path, '/');
        }

        $cwd = getcwd();
        if ($cwd === false) {
            throw new \RuntimeException('Unable to resolve current working directory.');
        }

        return rtrim($cwd.'/'.$path, '/');
    }
}
