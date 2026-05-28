<?php

declare(strict_types=1);

namespace PhpMvc\Commands;

/**
 * @deprecated use {@see MigrationsEnableCommand} (`mvc migrations:enable`)
 */
final class InitializeMigrationsCommand implements Command
{
    public function __construct(
        private readonly MigrationsEnableCommand $enable,
    ) {}

    public function getName(): string
    {
        return 'initialize-migrations';
    }

    public function getDescription(): string
    {
        return 'Alias for migrations:enable (deprecated)';
    }

    /**
     * @param array<string> $args
     */
    public function execute(array $args): int
    {
        return $this->enable->execute($args);
    }
}
