<?php

declare(strict_types=1);

namespace PhpMvc\Commands;

use PhpMvc\Config\MvcConfig;

/**
 * Resolves BackgroundTasks/index.php from app root and mvc.config.json.
 */
final class BackgroundTasksAppPathResolver
{
    /**
     * Absolute path to index.php, or null when missing.
     */
    public static function resolveIndexPath(string $appPath): ?string
    {
        $appPath = rtrim($appPath, '/');
        $config = MvcConfig::load($appPath);
        $folder = $config->normalizedBackgroundTasksFolderPath();
        $folder = '' !== $folder ? $folder : 'BackgroundTasks';
        $path = $appPath.'/'.$folder.'/index.php';

        return is_file($path) ? $path : null;
    }
}
