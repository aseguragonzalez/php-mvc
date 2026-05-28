<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Commands;

use PhpMvc\Commands\ConsoleOutput;
use PhpMvc\Commands\CreateAppCommand;
use PhpMvc\Commands\StubGenerator;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CreateAppCommandTest extends TestCase
{
    private CreateAppCommand $command;

    /** @var resource */
    private mixed $stdout;

    /** @var resource */
    private mixed $stderr;

    protected function setUp(): void
    {
        vfsStream::setup('project', null, [
            'composer.json' => '{}',
            'vendor' => [
                'autoload.php' => '<?php // autoload',
            ],
            'src' => [
                'Ports' => [],
            ],
        ]);

        $stdout = fopen('php://memory', 'r+');
        $stderr = fopen('php://memory', 'r+');
        \assert(\is_resource($stdout));
        \assert(\is_resource($stderr));
        $this->stdout = $stdout;
        $this->stderr = $stderr;
        $output = new ConsoleOutput($this->stdout, $this->stderr);
        $stubGenerator = new StubGenerator();
        $this->command = new CreateAppCommand($output, $stubGenerator);
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

    public function testExecuteCreatesAppStructure(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $exitCode = $this->command->execute([
            $appPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $this->assertSame(0, $exitCode);

        $this->assertTrue(is_file($appPath.'/index.php'));
        $this->assertTrue(is_file($appPath.'/.htaccess'));
        $this->assertTrue(is_file($appPath.'/MyAppBootstrap.php'));
        $this->assertTrue(is_file($appPath.'/MyAppApp.php'));
        $this->assertTrue(is_file($appPath.'/MyAppSettings.php'));
        $this->assertTrue(is_file($appPath.'/Controllers/RouterBuilder.php'));
        $this->assertTrue(is_file($appPath.'/Controllers/HomeController.php'));
        $this->assertTrue(is_file($appPath.'/Views/layout.html'));
        $this->assertTrue(is_file($appPath.'/Views/Home/index.html'));
        $this->assertTrue(is_file($appPath.'/Views/Shared/401.html'));
        $this->assertTrue(is_file($appPath.'/Views/Shared/403.html'));
        $this->assertTrue(is_file($appPath.'/Views/Shared/404.html'));
        $this->assertTrue(is_file($appPath.'/Views/Shared/500.html'));
        $this->assertTrue(is_file($appPath.'/assets/i18n/en.json'));
        $this->assertTrue(is_file($appPath.'/mvc.config.json'));
        $this->assertTrue(is_dir($appPath.'/Models'));
        $this->assertTrue(is_dir($appPath.'/assets/scripts'));
        $this->assertTrue(is_dir($appPath.'/assets/styles'));
        $this->assertTrue(is_file($appPath.'/assets/scripts/main.js'));
        $this->assertTrue(is_file($appPath.'/assets/styles/main.css'));
    }

    public function testCreatedMvcConfigContainsDefaults(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $exitCode = $this->command->execute([
            $appPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $this->assertSame(0, $exitCode);

        $configContent = file_get_contents($appPath.'/mvc.config.json');
        \assert(\is_string($configContent));

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($configContent, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('./assets/scripts', $decoded['jsAssetsPath']);
        $this->assertSame('main.min.js', $decoded['mainJsBundler']);
        $this->assertSame('./assets/styles', $decoded['cssAssetsPath']);
        $this->assertSame('main.min.css', $decoded['mainCssBundler']);
        $this->assertSame('./assets/i18n', $decoded['i18nPath']);
        $this->assertSame('', $decoded['migrationsFolderPath']);
        $this->assertFalse($decoded['migrationsEnabled']);
        $this->assertSame('', $decoded['backgroundTasksFolderPath']);
        $this->assertFalse($decoded['backgroundTasksEnabled']);
        $this->assertSame(0, $decoded['backgroundTasksPollIntervalSeconds']);
        $this->assertFalse($decoded['authenticationEnabled']);
        $this->assertSame('main.js', $decoded['devMainJsBundler']);
        $this->assertSame('main.css', $decoded['devMainCssBundler']);
        $this->assertFalse($decoded['useDevAssets']);
        $this->assertIsArray($decoded['assetRoutes']);

        /** @var array<int, array<string, mixed>> $assetRoutes */
        $assetRoutes = $decoded['assetRoutes'];
        $this->assertArrayHasKey(0, $assetRoutes);
        $this->assertSame('default', $assetRoutes[0]['label']);
        $this->assertSame(['assets/scripts/main.js'], $assetRoutes[0]['js']);
        $this->assertSame(['assets/styles/main.css'], $assetRoutes[0]['css']);
    }

    public function testGeneratedIndexPhpContainsCorrectAutoloadPath(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $this->command->execute([
            $appPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $indexContent = file_get_contents($appPath.'/index.php');
        \assert(\is_string($indexContent));
        $this->assertStringContainsString("'/../../../vendor/autoload.php'", $indexContent);
        $this->assertStringContainsString('MyAppBootstrap::register', $indexContent);
    }

    public function testGeneratedBootstrapClassContainsCorrectNamespace(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $this->command->execute([
            $appPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $bootstrapContent = file_get_contents($appPath.'/MyAppBootstrap.php');
        \assert(\is_string($bootstrapContent));
        $this->assertStringContainsString('namespace App\Ports\MyApp;', $bootstrapContent);
        $this->assertStringContainsString('class MyAppBootstrap', $bootstrapContent);
    }

    public function testGeneratedAppClassContainsCorrectNamespace(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $this->command->execute([
            $appPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $appContent = file_get_contents($appPath.'/MyAppApp.php');
        \assert(\is_string($appContent));
        $this->assertStringContainsString('namespace App\Ports\MyApp;', $appContent);
        $this->assertStringContainsString('class MyAppApp extends MvcWebApp', $appContent);
    }

    public function testGeneratedSettingsContainsCorrectClassName(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $this->command->execute([
            $appPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $settingsContent = file_get_contents($appPath.'/MyAppSettings.php');
        \assert(\is_string($settingsContent));
        $this->assertStringContainsString('class MyAppSettings', $settingsContent);
        $this->assertStringContainsString('namespace App\Ports\MyApp;', $settingsContent);
    }

    public function testGeneratedControllerHasCorrectNamespace(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $this->command->execute([
            $appPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $controllerContent = file_get_contents($appPath.'/Controllers/HomeController.php');
        \assert(\is_string($controllerContent));
        $this->assertStringContainsString('namespace App\Ports\MyApp\Controllers;', $controllerContent);
    }

    public function testExecuteFailsWhenPathIsMissing(): void
    {
        $exitCode = $this->command->execute([
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $this->assertSame(1, $exitCode);
    }

    public function testExecuteFailsWhenNameIsMissing(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $exitCode = $this->command->execute([
            $appPath,
            '--namespace=App\Ports\MyApp',
        ]);

        $this->assertSame(1, $exitCode);
    }

    public function testExecuteFailsWhenNamespaceIsMissing(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $exitCode = $this->command->execute([
            $appPath,
            '--name=MyApp',
        ]);

        $this->assertSame(1, $exitCode);
    }

    public function testExecuteFailsWhenDirectoryExistsAndNotEmpty(): void
    {
        vfsStream::setup('project', null, [
            'composer.json' => '{}',
            'vendor' => [
                'autoload.php' => '<?php // autoload',
            ],
            'src' => [
                'Ports' => [
                    'Existing' => [
                        'file.txt' => 'data',
                    ],
                ],
            ],
        ]);

        $appPath = vfsStream::url('project/src/Ports/Existing');

        $exitCode = $this->command->execute([
            $appPath,
            '--name=Existing',
            '--namespace=App\Ports\Existing',
        ]);

        $this->assertSame(1, $exitCode);
    }

    public function testHelpFlagReturnsZero(): void
    {
        $exitCode = $this->command->execute(['--help']);

        $this->assertSame(0, $exitCode);
    }

    public function testGeneratedEnvPrefixIsUpperSnakeCase(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $this->command->execute([
            $appPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $bootstrapContent = file_get_contents($appPath.'/MyAppBootstrap.php');
        \assert(\is_string($bootstrapContent));
        $this->assertStringContainsString("'MY_APP_SERVICE_NAME'", $bootstrapContent);
        $this->assertStringContainsString("'MY_APP_DATABASE_HOST'", $bootstrapContent);
    }
}
