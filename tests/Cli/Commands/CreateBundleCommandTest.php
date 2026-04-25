<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Commands;

use AlfonsoSG\Mvc\Commands\ConsoleOutput;
use AlfonsoSG\Mvc\Commands\CreateBundleCommand;
use AlfonsoSG\Mvc\Config\MvcConfig;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CreateBundleCommandTest extends TestCase
{
    /** @var resource */
    private mixed $stdout;

    /** @var resource */
    private mixed $stderr;

    protected function setUp(): void
    {
        $stdout = fopen('php://memory', 'r+');
        $stderr = fopen('php://memory', 'r+');
        \assert(\is_resource($stdout));
        \assert(\is_resource($stderr));
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    protected function tearDown(): void
    {
        if (is_resource($this->stdout)) {
            fclose($this->stdout);
        }
        if (is_resource($this->stderr)) {
            fclose($this->stderr);
        }
    }

    public function testCreateBundleWritesMinifiedFiles(): void
    {
        $this->expectOutputString("✓ Built bundle.min.js\n✓ Built bundle.min.css\n");

        vfsStream::setup('bundleapp', null, [
            'index.php' => '<?php',
            'assets' => [
                'scripts' => [
                    'a.js' => '/* a */ console.log(1);',
                ],
                'styles' => [
                    'a.css' => '/* x */ body { color: red; }',
                ],
            ],
            MvcConfig::CONFIG_FILENAME => json_encode([
                'jsAssetsPath' => './assets/scripts',
                'mainJsBundler' => 'bundle.min.js',
                'cssAssetsPath' => './assets/styles',
                'mainCssBundler' => 'bundle.min.css',
                'devMainJsBundler' => 'bundle.js',
                'devMainCssBundler' => 'bundle.css',
                'useDevAssets' => false,
                'assetRoutes' => [
                    ['label' => 't', 'js' => ['assets/scripts/a.js'], 'css' => ['assets/styles/a.css']],
                ],
                'i18nPath' => './assets/i18n',
                'migrationsFolderPath' => '',
                'migrationsEnabled' => false,
                'backgroundTasksFolderPath' => '',
                'authenticationEnabled' => false,
            ], JSON_THROW_ON_ERROR),
        ]);

        $url = vfsStream::url('bundleapp');
        $cmd = new CreateBundleCommand(new ConsoleOutput($this->stdout, $this->stderr));
        $exit = $cmd->execute(['--app-path='.$url]);

        $this->assertSame(0, $exit);
        $this->assertTrue(is_file($url.'/assets/scripts/bundle.min.js'));
        $this->assertTrue(is_file($url.'/assets/styles/bundle.min.css'));
        $minJs = file_get_contents($url.'/assets/scripts/bundle.min.js');
        \assert(\is_string($minJs));
        $this->assertStringNotContainsString('/* a */', $minJs);
    }

    public function testCreateBundleFailsWithoutAssetRoutes(): void
    {
        vfsStream::setup('noassets', null, [
            'index.php' => '<?php',
            MvcConfig::CONFIG_FILENAME => json_encode([
                'jsAssetsPath' => './assets/scripts',
                'mainJsBundler' => 'm.js',
                'cssAssetsPath' => './assets/styles',
                'mainCssBundler' => 'm.css',
                'i18nPath' => './assets/i18n',
                'migrationsFolderPath' => '',
                'migrationsEnabled' => false,
                'backgroundTasksFolderPath' => '',
                'authenticationEnabled' => false,
            ], JSON_THROW_ON_ERROR),
        ]);

        $url = vfsStream::url('noassets');
        $cmd = new CreateBundleCommand(new ConsoleOutput($this->stdout, $this->stderr));
        $this->assertSame(1, $cmd->execute(['--app-path='.$url]));
    }
}
