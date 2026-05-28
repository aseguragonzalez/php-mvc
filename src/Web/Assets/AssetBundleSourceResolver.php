<?php

declare(strict_types=1);

namespace PhpMvc\Assets;

use PhpMvc\Config\AssetRouteGroup;
use PhpMvc\Config\MvcConfig;

/**
 * Resolves merged, deduplicated source paths and output directories for asset bundling CLI.
 */
final readonly class AssetBundleSourceResolver
{
    public function __construct(
        private string $appRoot,
        private MvcConfig $config,
    ) {}

    /**
     * @return list<string> absolute filesystem paths
     */
    public function absoluteJsSourcePaths(): array
    {
        return $this->toAbsolute(self::mergedRelativeJsPaths($this->config->assetRoutes));
    }

    /**
     * @return list<string> absolute filesystem paths
     */
    public function absoluteCssSourcePaths(): array
    {
        return $this->toAbsolute(self::mergedRelativeCssPaths($this->config->assetRoutes));
    }

    /**
     * @param list<AssetRouteGroup> $groups
     *
     * @return list<string> normalized relative paths (no leading ./ or /)
     */
    public static function mergedRelativeJsPaths(array $groups): array
    {
        return self::mergePaths($groups, static fn (AssetRouteGroup $g): array => $g->js);
    }

    /**
     * @param list<AssetRouteGroup> $groups
     *
     * @return list<string> normalized relative paths
     */
    public static function mergedRelativeCssPaths(array $groups): array
    {
        return self::mergePaths($groups, static fn (AssetRouteGroup $g): array => $g->css);
    }

    public function absoluteJsOutputDir(): string
    {
        return $this->absoluteDirFromConfigPath($this->config->jsAssetsPath);
    }

    public function absoluteCssOutputDir(): string
    {
        return $this->absoluteDirFromConfigPath($this->config->cssAssetsPath);
    }

    public static function normalizeRelativeAssetPath(string $path): string
    {
        $path = trim($path);
        if ('' === $path) {
            return '';
        }
        if (str_starts_with($path, './')) {
            $path = substr($path, 2);
        }
        $path = ltrim($path, '/');

        return rtrim($path, '/');
    }

    /**
     * @param list<AssetRouteGroup>                   $groups
     * @param callable(AssetRouteGroup): list<string> $pathsOf
     *
     * @return list<string>
     */
    private static function mergePaths(array $groups, callable $pathsOf): array
    {
        $seen = [];
        $out = [];
        foreach ($groups as $group) {
            foreach ($pathsOf($group) as $rel) {
                $n = self::normalizeRelativeAssetPath($rel);
                if ('' === $n) {
                    continue;
                }
                if (isset($seen[$n])) {
                    continue;
                }
                $seen[$n] = true;
                $out[] = $n;
            }
        }

        return $out;
    }

    /**
     * @param list<string> $relative
     *
     * @return list<string>
     */
    private function toAbsolute(array $relative): array
    {
        $root = rtrim($this->appRoot, '/');

        return array_map(static fn (string $p): string => $root.'/'.$p, $relative);
    }

    private function absoluteDirFromConfigPath(string $relativePath): string
    {
        $rel = self::normalizeRelativeAssetPath($relativePath);
        $root = rtrim($this->appRoot, '/');
        if ('' === $rel) {
            return $root;
        }

        return $root.'/'.$rel;
    }
}
