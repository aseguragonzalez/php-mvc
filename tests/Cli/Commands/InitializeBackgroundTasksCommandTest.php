<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Commands;

use org\bovigo\vfs\vfsStream;
use PhpMvc\Commands\ConsoleOutput;
use PhpMvc\Commands\InitializeBackgroundTasksCommand;
use PhpMvc\Commands\StubGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class InitializeBackgroundTasksCommandTest extends TestCase
{
    private InitializeBackgroundTasksCommand $command;

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
                'Ports' => [
                    'MyApp' => [
                        'index.php' => '<?php // app entry point',
                    ],
                ],
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
        $this->command = new InitializeBackgroundTasksCommand($output, $stubGenerator);
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

    public function testExecuteCreatesBackgroundTasksStructure(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $exitCode = $this->command->execute([
            '--path='.$appPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue(is_dir($appPath.'/BackgroundTasks'));
        $this->assertTrue(is_dir($appPath.'/BackgroundTasks/Handlers'));
        $this->assertTrue(is_dir($appPath.'/BackgroundTasks/Tasks'));
        $this->assertTrue(is_file($appPath.'/BackgroundTasks/index.php'));
        $this->assertTrue(is_file($appPath.'/BackgroundTasks/MyAppBackgroundTasksBootstrap.php'));

        $configContent = file_get_contents($appPath.'/mvc.config.json');
        \assert(\is_string($configContent));

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($configContent, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('./BackgroundTasks', $decoded['backgroundTasksFolderPath']);
        $this->assertFalse($decoded['backgroundTasksEnabled']);
    }

    public function testGeneratedIndexPhpContainsCorrectAutoloadPath(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $this->command->execute([
            '--path='.$appPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $content = file_get_contents($appPath.'/BackgroundTasks/index.php');
        \assert(\is_string($content));
        $this->assertStringContainsString("'/../../../../vendor/autoload.php'", $content);
    }

    public function testGeneratedBootstrapClassHasCorrectNamespaceAndClassName(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $this->command->execute([
            '--path='.$appPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $content = file_get_contents($appPath.'/BackgroundTasks/MyAppBackgroundTasksBootstrap.php');
        \assert(\is_string($content));
        $this->assertStringContainsString('namespace App\Ports\MyApp\BackgroundTasks;', $content);
        $this->assertStringContainsString('class MyAppBackgroundTasksBootstrap', $content);
    }

    public function testGeneratedIndexPhpReferencesBootstrapAndBaseApp(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $this->command->execute([
            '--path='.$appPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $content = file_get_contents($appPath.'/BackgroundTasks/index.php');
        \assert(\is_string($content));
        $this->assertStringContainsString('MyAppBackgroundTasksBootstrap', $content);
        $this->assertStringContainsString(
            'App\Ports\MyApp\BackgroundTasks\MyAppBackgroundTasksBootstrap',
            $content
        );
        $this->assertStringContainsString('BackgroundTasksApp', $content);
    }

    public function testGeneratedIndexPhpGuardsWhenBackgroundTasksDisabled(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $this->command->execute([
            '--path='.$appPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $content = file_get_contents($appPath.'/BackgroundTasks/index.php');
        \assert(\is_string($content));
        $this->assertStringContainsString('isBackgroundTasksEnabled', $content);
        $this->assertStringContainsString('mvc background-tasks:enable', $content);
    }

    public function testExecuteFailsWhenNotAnAppDirectory(): void
    {
        $nonAppPath = vfsStream::url('project/src/Ports');

        $exitCode = $this->command->execute([
            '--path='.$nonAppPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $this->assertSame(1, $exitCode);
    }

    public function testExecuteFailsWhenBackgroundTasksAlreadyExists(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');
        mkdir($appPath.'/BackgroundTasks', 0o755, true);

        $exitCode = $this->command->execute([
            '--path='.$appPath,
            '--name=MyApp',
            '--namespace=App\Ports\MyApp',
        ]);

        $this->assertSame(1, $exitCode);
    }

    public function testExecuteFailsWhenNameIsMissing(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $exitCode = $this->command->execute([
            '--path='.$appPath,
            '--namespace=App\Ports\MyApp',
        ]);

        $this->assertSame(1, $exitCode);
    }

    public function testExecuteFailsWhenNamespaceIsMissing(): void
    {
        $appPath = vfsStream::url('project/src/Ports/MyApp');

        $exitCode = $this->command->execute([
            '--path='.$appPath,
            '--name=MyApp',
        ]);

        $this->assertSame(1, $exitCode);
    }

    public function testHelpFlagReturnsZero(): void
    {
        $exitCode = $this->command->execute(['--help']);

        $this->assertSame(0, $exitCode);
    }
}
