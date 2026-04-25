<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Commands;

use AlfonsoSG\Mvc\Assets\AssetBundleSourceResolver;
use AlfonsoSG\Mvc\Config\MvcConfig;
use AlfonsoSG\Mvc\Tools\CssBuilder;
use AlfonsoSG\Mvc\Tools\JsBuilder;

final class WatchAssetsCommand implements Command
{
    use ResolvesAppRootTrait;

    public function __construct(
        private readonly ConsoleOutput $output,
    ) {}

    public function getName(): string
    {
        return 'watch-assets';
    }

    public function getDescription(): string
    {
        return 'Watch and rebuild unminified JS/CSS bundles from mvc.config.json assetRoutes';
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

        $appRoot = $this->resolveAppRoot($args);

        if (!$this->isAppDirectory($appRoot)) {
            $this->output->error("Not a valid app directory (index.php not found): {$appRoot}");

            return 1;
        }

        $config = MvcConfig::load($appRoot);

        if ([] === $config->assetRoutes) {
            $this->output->error(
                'No assetRoutes configured in '.MvcConfig::CONFIG_FILENAME.'. '
                .'See src/Web/HowToAssets.md',
            );

            return 1;
        }

        $resolver = new AssetBundleSourceResolver($appRoot, $config);
        $jsSources = $resolver->absoluteJsSourcePaths();
        $cssSources = $resolver->absoluteCssSourcePaths();

        if ([] === $jsSources && [] === $cssSources) {
            $this->output->error(
                'assetRoutes defines no JS or CSS source paths. '
                .'Add "js" and/or "css" entries under each route group. '
                .'See src/Web/HowToAssets.md',
            );

            return 1;
        }

        if ($err = $this->validateSourcesExist($appRoot, $jsSources, $cssSources)) {
            $this->output->error($err);

            return 1;
        }

        $jsOut = $resolver->absoluteJsOutputDir();
        $cssOut = $resolver->absoluteCssOutputDir();

        $jsBuilder = [] !== $jsSources
            ? new JsBuilder(
                sourceFiles: $jsSources,
                outputDir: $jsOut,
                outputFile: $config->devMainJsBundler,
                outputMinFile: $config->mainJsBundler,
            )
            : null;

        $cssBuilder = [] !== $cssSources
            ? new CssBuilder(
                sourceFiles: $cssSources,
                outputDir: $cssOut,
                outputFile: $config->devMainCssBundler,
                outputMinFile: $config->mainCssBundler,
            )
            : null;

        $this->output->info("Watching assets for app: {$appRoot}");
        $this->output->line('Unminified output only. Press Ctrl+C to stop.');
        $this->output->line();

        $jsLast = [];
        $cssLast = [];

        // @phpstan-ignore while.alwaysTrue (watch loop; process exits on SIGINT)
        while (true) {
            if (null !== $jsBuilder) {
                $jsBuilder->watchTick($jsLast);
            }
            if (null !== $cssBuilder) {
                $cssBuilder->watchTick($cssLast);
            }
            usleep(500_000);
        }
    }

    /**
     * @param list<string> $jsSources
     * @param list<string> $cssSources
     */
    private function validateSourcesExist(
        string $appRoot,
        array $jsSources,
        array $cssSources,
    ): ?string {
        $root = rtrim($appRoot, '/');

        foreach ($jsSources as $absolute) {
            if (!is_file($absolute)) {
                return 'JavaScript source not found: '.$this->displayRelative($root, $absolute);
            }
        }
        foreach ($cssSources as $absolute) {
            if (!is_file($absolute)) {
                return 'CSS source not found: '.$this->displayRelative($root, $absolute);
            }
        }

        return null;
    }

    private function displayRelative(string $appRoot, string $absolute): string
    {
        if (str_starts_with($absolute, $appRoot.'/')) {
            return substr($absolute, strlen($appRoot) + 1);
        }

        return $absolute;
    }

    private function showHelp(): void
    {
        $this->output->line('Usage: mvc watch-assets [--app-path=<dir>]');
        $this->output->line();
        $this->output->line('Watches source files listed under assetRoutes in mvc.config.json and rebuilds');
        $this->output->line('unminified bundles (devMainJsBundler / devMainCssBundler) on change.');
        $this->output->line();
        $this->output->line('Options:');
        $this->output->line('  --app-path=<dir>   MVC app root (default: current directory)');
    }
}
