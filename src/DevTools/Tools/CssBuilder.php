<?php

declare(strict_types=1);

namespace PhpMvc\Tools;

final readonly class CssBuilder
{
    /**
     * @param array<string> $sourceFiles   Array of source file paths (absolute or relative)
     * @param string        $outputDir     Directory where output files will be written
     * @param string        $outputFile    Name of development output file (e.g., 'app.css')
     * @param string        $outputMinFile Name of production output file (e.g., 'app.min.css')
     */
    public function __construct(
        private array $sourceFiles,
        private string $outputDir,
        private string $outputFile,
        private string $outputMinFile
    ) {}

    public function build(bool $minify = false): void
    {
        $merged = '';

        foreach ($this->sourceFiles as $filePath) {
            if (!file_exists($filePath)) {
                throw new \RuntimeException("CSS file not found: {$filePath}");
            }

            $content = file_get_contents($filePath);
            $fileName = basename($filePath);
            $merged .= "/* ============================================\n";
            $merged .= "   {$fileName}\n";
            $merged .= "   ============================================ */\n\n";
            $merged .= $content."\n\n";
        }

        $outputFileName = $minify ? $this->outputMinFile : $this->outputFile;
        $outputPath = rtrim($this->outputDir, '/').'/'.$outputFileName;

        if ($minify) {
            $merged = $this->minify($merged);
        }

        // Ensure output directory exists
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0o755, true);
        }

        file_put_contents($outputPath, $merged);
        echo "✓ Built {$outputFileName}\n";
    }

    /**
     * One polling step for watch mode: detect source changes and rebuild the unminified bundle only.
     *
     * @param array<string, int> $lastModified path => mtime
     */
    public function watchTick(array &$lastModified): bool
    {
        $changed = false;

        foreach ($this->sourceFiles as $filePath) {
            if (!file_exists($filePath)) {
                continue;
            }

            $currentModified = filemtime($filePath);
            if (false === $currentModified) {
                continue;
            }

            if (!isset($lastModified[$filePath]) || $lastModified[$filePath] !== $currentModified) {
                $lastModified[$filePath] = $currentModified;
                $changed = true;
            }
        }

        if ($changed) {
            $this->build(minify: false);
        }

        return $changed;
    }

    public function watch(): void
    {
        echo "👀 Watching CSS files...\n";
        echo "Press Ctrl+C to stop\n\n";

        $lastModified = [];

        // @phpstan-ignore while.alwaysTrue (watch loop; process exits on SIGINT)
        while (true) {
            $this->watchTick($lastModified);
            usleep(500000); // Check every 500ms
        }
    }

    private function minify(string $css): string
    {
        // Remove comments
        /** @var string $css */
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);

        // Remove whitespace
        /** @var string $css */
        $css = preg_replace('/\s+/', ' ', $css);

        /** @var string $css */
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);

        /** @var string $css */
        $css = preg_replace('/;}/', '}', $css);

        // Remove leading/trailing whitespace
        return trim($css);
    }
}
