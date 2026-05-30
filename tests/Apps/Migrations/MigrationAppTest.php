<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Migrations;

use PhpMvc\Migrations\Application\RunMigrations;
use PhpMvc\Migrations\Application\TestMigration;
use PhpMvc\Migrations\MigrationApp;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class MigrationAppTest extends TestCase
{
    public function testRunWithNoArgsExecutesRunMigrationsAndReturnsZero(): void
    {
        $runMigrations = $this->createMock(RunMigrations::class);
        $runMigrations->expects($this->once())->method('execute')->with('/app');

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn($runMigrations);

        $app = new MigrationApp($container, '/app');
        $this->assertSame(0, $app->run(0, []));
    }

    public function testRunWithTestArgExecutesTestMigrationAndReturnsZero(): void
    {
        $testMigration = $this->createMock(TestMigration::class);
        $testMigration->expects($this->once())->method('execute');

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn($testMigration);

        $app = new MigrationApp($container, '/app');
        $this->assertSame(0, $app->run(1, ['--test=my_migration']));
    }

    public function testRunReturnOneWhenExceptionThrown(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnCallback(
            function (string $key) use ($logger): mixed {
                if (LoggerInterface::class === $key) {
                    return $logger;
                }

                throw new \RuntimeException('Service unavailable');
            }
        );

        $app = new MigrationApp($container, '/app');
        $this->assertSame(1, $app->run(0, []));
    }

    public function testRunWithInvalidArgCountLogsAndReturnsOne(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturn($logger);

        $app = new MigrationApp($container, '/app');
        $this->assertSame(1, $app->run(2, ['arg1', 'arg2']));
    }
}
