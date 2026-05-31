<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Tools;

use PhpMvc\Tools\CssBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CssBuilderTest extends TestCase
{
    private string $base;

    protected function setUp(): void
    {
        $this->base = sys_get_temp_dir().'/mvc_css_test_'.bin2hex(random_bytes(4));
        if (!mkdir($this->base, 0o777, true) && !is_dir($this->base)) {
            self::fail('Could not create temp dir');
        }
        file_put_contents($this->base.'/a.css', 'body { color: red; }');
        file_put_contents($this->base.'/b.css', '.btn { padding: 8px; }');
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->base);
    }

    public function testBuildDevMergesSourceFilesIntoSingleOutput(): void
    {
        $outDir = $this->base.'/out';
        mkdir($outDir);
        $builder = new CssBuilder(
            sourceFiles: [$this->base.'/a.css', $this->base.'/b.css'],
            outputDir: $outDir,
            outputFile: 'app.css',
            outputMinFile: 'app.min.css',
        );

        $this->expectOutputString("✓ Built app.css\n");
        $builder->build();

        $content = file_get_contents($outDir.'/app.css');
        $this->assertIsString($content);
        $this->assertStringContainsString('body { color: red; }', $content);
        $this->assertStringContainsString('.btn { padding: 8px; }', $content);
        $this->assertFileDoesNotExist($outDir.'/app.min.css');
    }

    public function testBuildMinifyWritesMinifiedFile(): void
    {
        $outDir = $this->base.'/out';
        mkdir($outDir);
        $builder = new CssBuilder(
            sourceFiles: [$this->base.'/a.css'],
            outputDir: $outDir,
            outputFile: 'app.css',
            outputMinFile: 'app.min.css',
        );

        $this->expectOutputString("✓ Built app.min.css\n");
        $builder->build(minify: true);

        $this->assertFileExists($outDir.'/app.min.css');
        $this->assertFileDoesNotExist($outDir.'/app.css');
        $content = file_get_contents($outDir.'/app.min.css');
        $this->assertIsString($content);
        $this->assertStringNotContainsString('  ', $content);
    }

    public function testBuildCreatesOutputDirectoryWhenMissing(): void
    {
        $outDir = $this->base.'/auto-created';
        $builder = new CssBuilder(
            sourceFiles: [$this->base.'/a.css'],
            outputDir: $outDir,
            outputFile: 'app.css',
            outputMinFile: 'app.min.css',
        );

        $this->expectOutputString("✓ Built app.css\n");
        $builder->build();

        $this->assertDirectoryExists($outDir);
        $this->assertFileExists($outDir.'/app.css');
    }

    public function testBuildThrowsWhenSourceFileMissing(): void
    {
        $outDir = $this->base.'/out';
        mkdir($outDir);
        $builder = new CssBuilder(
            sourceFiles: [$this->base.'/does-not-exist.css'],
            outputDir: $outDir,
            outputFile: 'app.css',
            outputMinFile: 'app.min.css',
        );

        $this->expectException(\RuntimeException::class);
        $builder->build();
    }

    public function testWatchTickReturnsTrueAndBuildsOnFirstSeen(): void
    {
        $outDir = $this->base.'/out';
        mkdir($outDir);
        $builder = new CssBuilder(
            sourceFiles: [$this->base.'/a.css'],
            outputDir: $outDir,
            outputFile: 'app.css',
            outputMinFile: 'app.min.css',
        );

        $this->expectOutputString("✓ Built app.css\n");
        $state = [];
        $changed = $builder->watchTick($state);

        $this->assertTrue($changed);
        $this->assertFileExists($outDir.'/app.css');
    }

    public function testWatchTickReturnsFalseWhenSourceUnchanged(): void
    {
        $outDir = $this->base.'/out';
        mkdir($outDir);
        $builder = new CssBuilder(
            sourceFiles: [$this->base.'/a.css'],
            outputDir: $outDir,
            outputFile: 'app.css',
            outputMinFile: 'app.min.css',
        );

        $this->expectOutputString("✓ Built app.css\n");
        $state = [];
        $builder->watchTick($state);

        $unchanged = $builder->watchTick($state);
        $this->assertFalse($unchanged);
    }

    public function testWatchTickSkipsMissingSourceFiles(): void
    {
        $outDir = $this->base.'/out';
        mkdir($outDir);
        $builder = new CssBuilder(
            sourceFiles: [$this->base.'/missing.css'],
            outputDir: $outDir,
            outputFile: 'app.css',
            outputMinFile: 'app.min.css',
        );

        $state = [];
        $changed = $builder->watchTick($state);

        $this->assertFalse($changed);
        $this->assertFileDoesNotExist($outDir.'/app.css');
    }

    private function deleteTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = scandir($path);
        if (false === $items) {
            return;
        }
        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $full = $path.'/'.$item;
            if (is_dir($full)) {
                $this->deleteTree($full);
            } else {
                @unlink($full);
            }
        }
        @rmdir($path);
    }
}
