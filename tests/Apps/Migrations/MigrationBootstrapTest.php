<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Migrations;

use PhpMvc\Migrations\Domain\Clients\DbClient;
use PhpMvc\Migrations\Domain\Repositories\MigrationRepository;
use PhpMvc\Migrations\MigrationBootstrap;
use PhpMvc\Migrations\MigrationSettings;
use PhpMvc\MutableContainerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MigrationBootstrapTest extends TestCase
{
    public function testConfigureRegistersAllBindings(): void
    {
        $settings = new MigrationSettings('localhost', 'my_db', 'root', 'pass');
        $migrationRepository = $this->createStub(MigrationRepository::class);
        $dbClient = $this->createStub(DbClient::class);

        $container = $this->createMock(MutableContainerInterface::class);
        $container->method('get')->willReturnCallback(
            function (string $key) use ($settings, $migrationRepository, $dbClient): mixed {
                return match ($key) {
                    MigrationSettings::class => $settings,
                    MigrationRepository::class => $migrationRepository,
                    DbClient::class => $dbClient,
                    default => null,
                };
            }
        );
        $container->expects($this->atLeastOnce())->method('set');

        MigrationBootstrap::configure($container);
    }
}
