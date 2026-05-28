<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Migrations\Domain;

use PhpMvc\Migrations\Domain\Entities\Migration;
use PhpMvc\Migrations\Domain\Entities\Script;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MigrationTest extends TestCase
{
    public function testNewCreatesMigrationWithDefaultUtcTimestamp(): void
    {
        $scripts = [];
        $migration = Migration::new(name: 'test_migration', scripts: $scripts);

        $this->assertSame('test_migration', $migration->name);
        $this->assertInstanceOf(\DateTimeImmutable::class, $migration->createdAt);
        $this->assertSame([], $migration->scripts);
        $this->assertSame('UTC', $migration->createdAt->getTimezone()->getName());
    }

    public function testNewAcceptsCustomTimestamp(): void
    {
        $customTimestamp = new \DateTimeImmutable('2024-01-15 10:30:00', new \DateTimeZone('UTC'));
        $scripts = [];
        $migration = Migration::new(name: 'test_migration', scripts: $scripts, createdAt: $customTimestamp);

        $this->assertSame('test_migration', $migration->name);
        $this->assertSame($customTimestamp, $migration->createdAt);
        $this->assertSame([], $migration->scripts);
    }

    public function testBuildCreatesMigrationWithProvidedValues(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-15 10:30:00', new \DateTimeZone('UTC'));
        $script1 = Script::build('001_create_table.sql');
        $script2 = Script::build('002_add_column.sql');
        $scripts = [$script1, $script2];

        $migration = Migration::build(createdAt: $createdAt, name: 'test_migration', scripts: $scripts);

        $this->assertSame('test_migration', $migration->name);
        $this->assertSame($createdAt, $migration->createdAt);
        $this->assertSame($scripts, $migration->scripts);
        $this->assertCount(2, $migration->scripts);
    }

    public function testReadonlyPropertiesAreAccessible(): void
    {
        $script = Script::build('001_create_table.sql');
        $scripts = [$script];
        $migration = Migration::new(name: 'test_migration', scripts: $scripts);

        $this->assertSame('test_migration', $migration->name);
        $this->assertInstanceOf(\DateTimeImmutable::class, $migration->createdAt);
        $this->assertSame($scripts, $migration->scripts);
    }

    public function testScriptsArrayHandling(): void
    {
        $script1 = Script::build('001_create_table.sql');
        $script2 = Script::build('002_add_column.sql');
        $script3 = Script::build('003_add_index.sql');
        $scripts = [$script1, $script2, $script3];

        $migration = Migration::new(name: 'test_migration', scripts: $scripts);

        $this->assertCount(3, $migration->scripts);
        $this->assertSame($script1, $migration->scripts[0]);
        $this->assertSame($script2, $migration->scripts[1]);
        $this->assertSame($script3, $migration->scripts[2]);
    }
}
