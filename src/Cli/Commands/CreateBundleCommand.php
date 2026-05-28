<?php

declare(strict_types=1);

namespace PhpMvc\Commands;

use PhpMvc\Assets\AssetBundleSourceResolver;
use PhpMvc\Config\MvcConfig;
use PhpMvc\Tools\CssBuilder;
use PhpMvc\Tools\JsBuilder;

final class CreateBundleCommand implements Command
{
    use ResolvesAppRootTrait;

    public function __construct(
        private readonly ConsoleOutput $output,
    ) {}

    public function getName(): string
    {
        return 'create-bundle';
    }

    public function getDescription(): string
    {
        return 'Build minified JS/CSS production bundles from mvc.config.json assetRoutes';
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

        try {
            if ([] !== $jsSources) {
                $jsBuilder = new JsBuilder(
                    sourceFiles: $jsSources,
                    outputDir: $jsOut,
                    outputFile: $config->devMainJsBundler,
                    outputMinFile: $config->mainJsBundler,
                );
                $jsBuilder->build(minify: true);
            }
            if ([] !== $cssSources) {
                $cssBuilder = new CssBuilder(
                    sourceFiles: $cssSources,
                    outputDir: $cssOut,
                    outputFile: $config->devMainCssBundler,
                    outputMinFile: $config->mainCssBundler,
                );
                $cssBuilder->build(minify: true);
            }
        } catch (\Throwable $e) {
            $this->output->error($e->getMessage());

            return 1;
        }

        $this->output->success('Minified bundles written.');

        return 0;
    }

    /**
     * @param list<string> $jsSources
     * @param list<string> $cssSources
     */
    private function validateSourcesExist(string $appRoot, array $jsSources, array $cssSources): ?string
    {
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
        $this->output->line('Usage: mvc create-bundle [--app-path=<dir>]');
        $this->output->line();
        $this->output->line('Merges and minifies sources from assetRoutes into mainJsBundler and mainCssBundler.');
        $this->output->line();
        $this->output->line('Options:');
        $this->output->line('  --app-path=<dir>   MVC app root (default: current directory)');
    }
}
