<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Tools;

use PhpMvc\Tools\JsBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class JsBuilderWatchTickTest extends TestCase
{
    public function testWatchTickBuildsUnminifiedOnlyOnceWhenSourcesStable(): void
    {
        $this->expectOutputString("✓ Built dev.js\n✓ Built dev.js\n");

        $base = sys_get_temp_dir().'/mvc_js_watch_'.bin2hex(random_bytes(4));
        if (!mkdir($base, 0o777, true) && !is_dir($base)) {
            self::fail('Could not create temp dir');
        }

        try {
            file_put_contents($base.'/one.js', 'console.log(1);');
            file_put_contents($base.'/two.js', 'console.log(2);');

            $outDir = $base.'/out';
            mkdir($outDir);

            $builder = new JsBuilder(
                sourceFiles: [$base.'/one.js', $base.'/two.js'],
                outputDir: $outDir,
                outputFile: 'dev.js',
                outputMinFile: 'prod.min.js',
            );

            $state = [];
            $builder->watchTick($state);

            $devPath = $outDir.'/dev.js';
            $minPath = $outDir.'/prod.min.js';
            $this->assertFileExists($devPath);
            $this->assertFileDoesNotExist($minPath);

            $mtimeAfterFirst = filemtime($devPath);
            $this->assertNotFalse($mtimeAfterFirst);

            $builder->watchTick($state);
            clearstatcache(true, $devPath);
            $this->assertSame($mtimeAfterFirst, filemtime($devPath));

            sleep(1);
            file_put_contents($base.'/one.js', 'console.log(9);');
            $builder->watchTick($state);
            clearstatcache(true, $devPath);
            $this->assertGreaterThan($mtimeAfterFirst, filemtime($devPath));
        } finally {
            $this->deleteTree($base);
        }
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
