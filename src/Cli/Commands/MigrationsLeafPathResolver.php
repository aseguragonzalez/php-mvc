<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Commands;

use AlfonsoSG\Mvc\Config\MvcConfig;

final class MigrationsLeafPathResolver
{
    /**
     * Resolves the absolute path to the leaf `migrations` directory (timestamped migration folders).
     * Returns null when the feature is disabled, config is missing, or the directory does not exist.
     */
    public static function resolveLeafMigrationsDir(string $appPath): ?string
    {
        $appPath = rtrim($appPath, '/');
        $configPath = $appPath.'/'.MvcConfig::CONFIG_FILENAME;
        if (!is_file($configPath)) {
            return null;
        }

        $config = MvcConfig::load($appPath);
        if (!$config->isMigrationsEnabled()) {
            return null;
        }

        $moduleRelative = $config->effectiveMigrationsModuleRelativePath();
        $leaf = $appPath.'/'.$moduleRelative.'/migrations';
        if (!is_dir($leaf)) {
            return null;
        }

        return $leaf;
    }
}
