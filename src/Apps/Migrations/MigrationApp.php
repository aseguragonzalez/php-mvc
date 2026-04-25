<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Migrations;

use AlfonsoSG\Mvc\Application;
use AlfonsoSG\Mvc\Migrations\Application\RunMigrations;
use AlfonsoSG\Mvc\Migrations\Application\TestMigration;
use AlfonsoSG\Mvc\Migrations\Application\TestMigrationCommand;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class MigrationApp extends Application
{
    public function __construct(ContainerInterface $container, string $basePath)
    {
        parent::__construct($container, $basePath);
    }

    /**
     * Run the application with the given arguments.
     *
     * @param null|int      $argc The number of arguments passed to the application. Default is null.
     * @param array<string> $argv The arguments to pass to the application. Default is an empty array.
     *
     * @return int the exit code of the application
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
     * Parse the arguments and return the command and the migration name.
     *
     * @param array<string> $argv The arguments to pass to the application. Default is an empty array.
     *
     * @return array<string, string> the command and the arguments
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
