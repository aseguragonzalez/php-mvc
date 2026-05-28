<?php

declare(strict_types=1);

namespace PhpMvc\Migrations;

use PhpMvc\Migrations\Application\RunMigrations;
use PhpMvc\Migrations\Application\TestMigration;
use PhpMvc\Migrations\Application\TestMigrationCommand;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class MigrationApp
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly string $basePath,
    ) {}

    /**
     * @param array<string> $argv
     */
    public function run(?int $argc = null, array $argv = []): int
    {
        try {
            $arguments = $this->parseArguments($argv);

            if ('test' === $arguments['command']) {
                /** @var string $migrationName */
                $migrationName = $arguments['args'];

                /** @var TestMigration $testMigration */
                $testMigration = $this->container->get(TestMigration::class);
                $testMigration->execute(new TestMigrationCommand(
                    migrationName: $migrationName,
                    basePath: $this->basePath,
                ));
            } elseif ('run' === $arguments['command']) {
                /** @var RunMigrations $runMigrations */
                $runMigrations = $this->container->get(RunMigrations::class);
                $runMigrations->execute(basePath: $this->basePath);
            }

            return 0;
        } catch (\Exception $e) {
            /** @var LoggerInterface $logger */
            $logger = $this->container->get(LoggerInterface::class);
            $logger->error('Error running migrations: {error}', ['error' => $e->getMessage()]);

            return 1;
        }
    }

    /**
     * @param array<string> $argv
     *
     * @return array<string, string>
     */
    private function parseArguments(array $argv = []): array
    {
        if (empty($argv)) {
            return [
                'command' => 'run',
                'args' => '',
            ];
        }

        if (1 === count($argv)) {
            $migrationName = str_replace('--test=', '', $argv[0] ?? '');

            return [
                'command' => 'test',
                'args' => $migrationName,
            ];
        }

        throw new \InvalidArgumentException('Invalid command');
    }
}
