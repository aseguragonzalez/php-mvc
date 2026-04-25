<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Commands;

/**
 * Resolves MVC app root from `--app-path` (default: current directory).
 *
 * @internal
 */
trait ResolvesAppRootTrait
{
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
        if (false === $cwd) {
            throw new \RuntimeException('Could not resolve current working directory.');
        }

        return rtrim($cwd.'/'.$path, '/');
    }

    /**
     * @param array<string> $args
     */
    private function resolveAppRoot(array $args): string
    {
        return $this->resolveShellPath($this->parseAppPathArg($args));
    }

    private function isAppDirectory(string $path): bool
    {
        return is_file(rtrim($path, '/').'/index.php');
    }
}
