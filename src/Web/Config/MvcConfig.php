<?php

declare(strict_types=1);

namespace PhpMvc\Config;

final readonly class MvcConfig
{
    public const CONFIG_FILENAME = 'mvc.config.json';

    /**
     * @param list<AssetRouteGroup> $assetRoutes
     */
    public function __construct(
        public string $jsAssetsPath,
        public string $mainJsBundler,
        public string $cssAssetsPath,
        public string $mainCssBundler,
        public string $i18nPath,
        public string $migrationsFolderPath,
        public ?bool $migrationsEnabled,
        public string $backgroundTasksFolderPath,
        public ?bool $backgroundTasksEnabled,
        public int $backgroundTasksPollIntervalSeconds,
        public ?bool $authenticationEnabled,
        public array $assetRoutes,
        public string $devMainJsBundler,
        public string $devMainCssBundler,
        public bool $useDevAssets,
    ) {}

    public static function defaults(): self
    {
        return new self(
            jsAssetsPath: './assets/scripts',
            mainJsBundler: 'main.min.js',
            cssAssetsPath: './assets/styles',
            mainCssBundler: 'main.min.css',
            i18nPath: './assets/i18n',
            migrationsFolderPath: '',
            migrationsEnabled: null,
            backgroundTasksFolderPath: '',
            backgroundTasksEnabled: null,
            backgroundTasksPollIntervalSeconds: 0,
            authenticationEnabled: null,
            assetRoutes: [],
            devMainJsBundler: 'main.js',
            devMainCssBundler: 'main.css',
            useDevAssets: false,
        );
    }

    public static function load(string $basePath, string $configFilename = self::CONFIG_FILENAME): self
    {
        $configPath = rtrim($basePath, '/').'/'.ltrim($configFilename, '/');
        if (!is_file($configPath)) {
            return self::defaults();
        }

        $content = file_get_contents($configPath);
        if (false === $content) {
            throw new \RuntimeException("Failed to read config: {$configPath}");
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException("Failed to decode JSON config: {$configPath}: {$e->getMessage()}");
        }

        return self::fromArray($data);
    }

    /**
     * Explicitly disabled via `migrationsEnabled: false` in mvc.config.json.
     * When the key is absent (null), legacy apps keep migrations usable (implicit module folder name).
     */
    public function isMigrationsEnabled(): bool
    {
        return false !== $this->migrationsEnabled;
    }

    /**
     * True only when `authenticationEnabled` is explicitly `true` in mvc.config.json.
     */
    public function isAuthenticationEnabled(): bool
    {
        return true === $this->authenticationEnabled;
    }

    /**
     * True only when `backgroundTasksEnabled` is explicitly `true` in mvc.config.json.
     */
    public function isBackgroundTasksEnabled(): bool
    {
        return true === $this->backgroundTasksEnabled;
    }

    /**
     * Poll interval from config when > 0; otherwise no default loop (single batch unless CLI passes --interval).
     */
    public function effectiveBackgroundTasksPollIntervalSeconds(): int
    {
        return $this->backgroundTasksPollIntervalSeconds > 0
            ? $this->backgroundTasksPollIntervalSeconds
            : 0;
    }

    /**
     * Relative path from app root to the migration module directory (contains index.php and migrations/).
     * When `migrationsFolderPath` is empty, uses the legacy default folder name `Migrations`.
     */
    public function effectiveMigrationsModuleRelativePath(): string
    {
        $normalized = $this->normalizedMigrationsFolderPath();
        if ('' !== $normalized) {
            return $normalized;
        }

        return 'Migrations';
    }

    /**
     * @param array<string, mixed> $changes
     */
    public static function writeMergedToApp(string $appPath, array $changes): void
    {
        $configPath = rtrim($appPath, '/').'/'.self::CONFIG_FILENAME;

        $d = self::defaults();

        /** @var array<string, mixed> $data */
        $data = [
            'jsAssetsPath' => $d->jsAssetsPath,
            'mainJsBundler' => $d->mainJsBundler,
            'cssAssetsPath' => $d->cssAssetsPath,
            'mainCssBundler' => $d->mainCssBundler,
            'i18nPath' => $d->i18nPath,
            'migrationsFolderPath' => $d->migrationsFolderPath,
            'migrationsEnabled' => $d->migrationsEnabled,
            'backgroundTasksFolderPath' => $d->backgroundTasksFolderPath,
            'backgroundTasksEnabled' => $d->backgroundTasksEnabled,
            'backgroundTasksPollIntervalSeconds' => $d->backgroundTasksPollIntervalSeconds,
            'authenticationEnabled' => $d->authenticationEnabled,
            'assetRoutes' => self::assetRoutesToJsonArray($d->assetRoutes),
            'devMainJsBundler' => $d->devMainJsBundler,
            'devMainCssBundler' => $d->devMainCssBundler,
            'useDevAssets' => $d->useDevAssets,
        ];

        if (is_file($configPath)) {
            $content = file_get_contents($configPath);
            if (false !== $content) {
                try {
                    /** @var array<string, mixed> $decoded */
                    $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                    foreach ($data as $key => $defaultValue) {
                        if (!array_key_exists($key, $decoded)) {
                            continue;
                        }
                        if ('assetRoutes' === $key && is_array($decoded[$key])) {
                            $data[$key] = $decoded[$key];

                            continue;
                        }
                        $isBoolConfigKey = 'migrationsEnabled' === $key
                            || 'backgroundTasksEnabled' === $key
                            || 'authenticationEnabled' === $key
                            || 'useDevAssets' === $key;
                        if ($isBoolConfigKey && is_bool($decoded[$key])) {
                            $data[$key] = $decoded[$key];

                            continue;
                        }
                        if ('backgroundTasksPollIntervalSeconds' === $key && is_int($decoded[$key])) {
                            $data[$key] = $decoded[$key];

                            continue;
                        }
                        if (is_string($defaultValue) && is_string($decoded[$key])) {
                            $data[$key] = $decoded[$key];

                            continue;
                        }
                        if (is_array($defaultValue) && is_array($decoded[$key])) {
                            $data[$key] = $decoded[$key];
                        }
                    }
                } catch (\JsonException) {
                    // Keep defaults when config is invalid.
                }
            }
        }

        foreach ($changes as $key => $value) {
            $data[$key] = $value;
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (false === $json) {
            throw new \RuntimeException('Failed to encode mvc.config.json');
        }
        file_put_contents($configPath, $json.PHP_EOL);
    }

    /**
     * @param list<AssetRouteGroup> $routes
     *
     * @return list<array<string, mixed>>
     */
    public static function assetRoutesToJsonArray(array $routes): array
    {
        return array_map(
            static fn (AssetRouteGroup $g): array => [
                'label' => $g->label,
                'js' => $g->js,
                'css' => $g->css,
            ],
            $routes,
        );
    }

    /**
     * Normalizes a config value like `./assets/i18n` into `assets/i18n/` for the LanguageSettings constructor.
     */
    public function normalizedI18nAssetsPathForLanguageSettings(): string
    {
        $normalized = self::normalizeRelativePath($this->i18nPath);
        if ('' === $normalized) {
            return 'assets/i18n/';
        }

        return rtrim($normalized, '/').'/';
    }

    public function normalizedMigrationsFolderPath(): string
    {
        return self::normalizeRelativePath($this->migrationsFolderPath);
    }

    public function normalizedBackgroundTasksFolderPath(): string
    {
        return self::normalizeRelativePath($this->backgroundTasksFolderPath);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function fromArray(array $data): self
    {
        $defaults = self::defaults();

        return new self(
            jsAssetsPath: self::getString($data, 'jsAssetsPath', $defaults->jsAssetsPath),
            mainJsBundler: self::getString($data, 'mainJsBundler', $defaults->mainJsBundler),
            cssAssetsPath: self::getString($data, 'cssAssetsPath', $defaults->cssAssetsPath),
            mainCssBundler: self::getString($data, 'mainCssBundler', $defaults->mainCssBundler),
            i18nPath: self::getString($data, 'i18nPath', $defaults->i18nPath),
            migrationsFolderPath: self::getString($data, 'migrationsFolderPath', $defaults->migrationsFolderPath),
            migrationsEnabled: self::getBoolOrNull($data, 'migrationsEnabled'),
            backgroundTasksFolderPath: self::getString(
                data: $data,
                key: 'backgroundTasksFolderPath',
                default: $defaults->backgroundTasksFolderPath
            ),
            backgroundTasksEnabled: self::getBoolOrNull($data, 'backgroundTasksEnabled'),
            backgroundTasksPollIntervalSeconds: self::getInt(
                $data,
                'backgroundTasksPollIntervalSeconds',
                $defaults->backgroundTasksPollIntervalSeconds
            ),
            authenticationEnabled: self::getBoolOrNull($data, 'authenticationEnabled'),
            assetRoutes: self::parseAssetRoutes($data),
            devMainJsBundler: self::getString($data, 'devMainJsBundler', $defaults->devMainJsBundler),
            devMainCssBundler: self::getString($data, 'devMainCssBundler', $defaults->devMainCssBundler),
            useDevAssets: self::getBool($data, 'useDevAssets', $defaults->useDevAssets),
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<AssetRouteGroup>
     */
    private static function parseAssetRoutes(array $data): array
    {
        $raw = $data['assetRoutes'] ?? null;
        if (!is_array($raw)) {
            return [];
        }

        $groups = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            // @var array<string, mixed> $item
            $groups[] = new AssetRouteGroup(
                label: self::getString($item, 'label', ''),
                js: self::getStringList($item, 'js'),
                css: self::getStringList($item, 'css'),
            );
        }

        return $groups;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<string>
     */
    private static function getStringList(array $data, string $key): array
    {
        $value = $data[$key] ?? [];
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && '' !== $item) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function getString(array $data, string $key, string $default): string
    {
        $value = $data[$key] ?? $default;
        if (!is_string($value)) {
            return $default;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function getBoolOrNull(array $data, string $key): ?bool
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }
        $value = $data[$key];
        if (!is_bool($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function getBool(array $data, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $data)) {
            return $default;
        }
        $value = $data[$key];
        if (!is_bool($value)) {
            return $default;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function getInt(array $data, string $key, int $default): int
    {
        if (!array_key_exists($key, $data)) {
            return $default;
        }
        $value = $data[$key];
        if (!is_int($value)) {
            return $default;
        }

        return $value;
    }

    /**
     * @return string path without leading `./` or `/`, without trailing `/`.
     */
    private static function normalizeRelativePath(string $path): string
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
}
