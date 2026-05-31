<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\BackgroundTasks;

use PhpMvc\BackgroundTasks\Application\ProcessPendingTasks\ProcessPendingTasks;
use PhpMvc\BackgroundTasks\BackgroundTasksApp;
use PhpMvc\BackgroundTasks\BackgroundTasksSettings;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class BackgroundTasksAppTest extends TestCase
{
    private BackgroundTasksSettings $defaultSettings;

    protected function setUp(): void
    {
        $this->defaultSettings = new BackgroundTasksSettings('localhost', 'my_db', 'root', 'pass');
    }

    public function testRunWithNoArgsBatchesOnceAndReturnsZero(): void
    {
        $processPendingTasks = $this->createMock(ProcessPendingTasks::class);
        $processPendingTasks->expects($this->once())->method('execute');

        $container = $this->makeContainer($processPendingTasks);

        $app = new BackgroundTasksApp($container);
        $this->assertSame(0, $app->run(0, []));
    }

    public function testRunReturnsOneWhenBatchThrowsException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $processPendingTasks = $this->createStub(ProcessPendingTasks::class);
        $processPendingTasks->method('execute')->willThrowException(new \RuntimeException('Batch failed'));

        $container = $this->makeContainer($processPendingTasks, $logger);

        $app = new BackgroundTasksApp($container);
        $this->assertSame(1, $app->run(0, []));
    }

    public function testRunWithIntervalFromArgvEqualsFormatExitsWhenBatchFails(): void
    {
        $logger = $this->createStub(LoggerInterface::class);

        $processPendingTasks = $this->createStub(ProcessPendingTasks::class);
        $processPendingTasks->method('execute')->willThrowException(new \RuntimeException('Fail'));

        $container = $this->makeContainer($processPendingTasks, $logger);

        $app = new BackgroundTasksApp($container);
        // interval=5 from arg, batch fails on first run → exits with code 1
        $this->assertSame(1, $app->run(1, ['--interval=5']));
    }

    public function testRunWithIntervalFromArgvSpaceFormatExitsWhenBatchFails(): void
    {
        $logger = $this->createStub(LoggerInterface::class);

        $processPendingTasks = $this->createStub(ProcessPendingTasks::class);
        $processPendingTasks->method('execute')->willThrowException(new \RuntimeException('Fail'));

        $container = $this->makeContainer($processPendingTasks, $logger);

        $app = new BackgroundTasksApp($container);
        $this->assertSame(1, $app->run(2, ['--interval', '5']));
    }

    public function testRunUsesSettingsPollIntervalWhenNoArgvInterval(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $settings = new BackgroundTasksSettings('localhost', 'db', 'u', 'p', pollIntervalSeconds: 10);

        $processPendingTasks = $this->createStub(ProcessPendingTasks::class);
        $processPendingTasks->method('execute')->willThrowException(new \RuntimeException('Fail'));

        $container = $this->makeContainer($processPendingTasks, $logger, $settings);

        $app = new BackgroundTasksApp($container);
        // interval=10 from settings, batch fails immediately → return 1
        $this->assertSame(1, $app->run(0, []));
    }

    private function makeContainer(
        ProcessPendingTasks $processPendingTasks,
        ?LoggerInterface $logger = null,
        ?BackgroundTasksSettings $settings = null,
    ): ContainerInterface {
        $resolvedSettings = $settings ?? $this->defaultSettings;
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnCallback(
            function (string $key) use ($processPendingTasks, $logger, $resolvedSettings): mixed {
                return match ($key) {
                    ProcessPendingTasks::class => $processPendingTasks,
                    BackgroundTasksSettings::class => $resolvedSettings,
                    LoggerInterface::class => $logger,
                    default => null,
                };
            }
        );

        return $container;
    }
}
