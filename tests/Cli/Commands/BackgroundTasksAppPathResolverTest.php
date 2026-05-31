<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Commands;

use org\bovigo\vfs\vfsStream;
use PhpMvc\Commands\BackgroundTasksAppPathResolver;
use PhpMvc\Config\MvcConfig;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class BackgroundTasksAppPathResolverTest extends TestCase
{
    public function testReturnsIndexPathWhenDefaultFolderExists(): void
    {
        vfsStream::setup('app', null, [
            MvcConfig::CONFIG_FILENAME => json_encode(
                ['backgroundTasksFolderPath' => ''],
                JSON_THROW_ON_ERROR,
            ),
            'BackgroundTasks' => [
                'index.php' => '<?php',
            ],
        ]);
        $appUrl = vfsStream::url('app');

        $result = BackgroundTasksAppPathResolver::resolveIndexPath($appUrl);

        $this->assertSame($appUrl.'/BackgroundTasks/index.php', $result);
    }

    public function testReturnsNullWhenIndexFileDoesNotExist(): void
    {
        vfsStream::setup('app', null, [
            MvcConfig::CONFIG_FILENAME => json_encode(
                ['backgroundTasksFolderPath' => ''],
                JSON_THROW_ON_ERROR,
            ),
        ]);

        $result = BackgroundTasksAppPathResolver::resolveIndexPath(vfsStream::url('app'));

        $this->assertNull($result);
    }

    public function testUsesCustomFolderFromConfig(): void
    {
        vfsStream::setup('app', null, [
            MvcConfig::CONFIG_FILENAME => json_encode(
                ['backgroundTasksFolderPath' => './Workers'],
                JSON_THROW_ON_ERROR,
            ),
            'Workers' => [
                'index.php' => '<?php',
            ],
        ]);
        $appUrl = vfsStream::url('app');

        $result = BackgroundTasksAppPathResolver::resolveIndexPath($appUrl);

        $this->assertSame($appUrl.'/Workers/index.php', $result);
    }

    public function testReturnsNullWhenCustomFolderHasNoIndex(): void
    {
        vfsStream::setup('app', null, [
            MvcConfig::CONFIG_FILENAME => json_encode(
                ['backgroundTasksFolderPath' => './Workers'],
                JSON_THROW_ON_ERROR,
            ),
            'Workers' => [],
        ]);

        $result = BackgroundTasksAppPathResolver::resolveIndexPath(vfsStream::url('app'));

        $this->assertNull($result);
    }

    public function testFallsBackToDefaultFolderWhenConfigAbsent(): void
    {
        vfsStream::setup('app', null, [
            'BackgroundTasks' => [
                'index.php' => '<?php',
            ],
        ]);
        $appUrl = vfsStream::url('app');

        $result = BackgroundTasksAppPathResolver::resolveIndexPath($appUrl);

        $this->assertSame($appUrl.'/BackgroundTasks/index.php', $result);
    }
}
