<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Migrations\Domain;

use PhpMvc\Migrations\Domain\Entities\Script;
use PhpMvc\Migrations\Domain\Exceptions\MigrationException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MigrationExceptionTest extends TestCase
{
    public function testExceptionHoldsScriptsArray(): void
    {
        $script1 = Script::build('001_create_table.sql');
        $script2 = Script::build('002_add_column.sql');
        $scripts = [$script1, $script2];
        $message = 'Migration failed';

        $exception = new MigrationException(scripts: $scripts, message: $message);

        $this->assertSame($scripts, $exception->scripts);
        $this->assertCount(2, $exception->scripts);
        $this->assertSame($script1, $exception->scripts[0]);
        $this->assertSame($script2, $exception->scripts[1]);
    }

    public function testExceptionPreservesMessageCodeAndPreviousException(): void
    {
        $scripts = [];
        $message = 'Migration failed';
        $code = 500;
        $previousException = new \RuntimeException('Previous error');

        $exception = new MigrationException(
            scripts: $scripts,
            message: $message,
            code: $code,
            previous: $previousException
        );

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testScriptsPropertyIsReadonlyAndAccessible(): void
    {
        $script = Script::build('001_create_table.sql');
        $scripts = [$script];
        $exception = new MigrationException(scripts: $scripts, message: 'Error');

        $this->assertSame($scripts, $exception->scripts);
        $this->assertIsArray($exception->scripts);
    }

    public function testExceptionWithEmptyScriptsArray(): void
    {
        $scripts = [];
        $exception = new MigrationException(scripts: $scripts, message: 'Error with no scripts');

        $this->assertSame([], $exception->scripts);
        $this->assertCount(0, $exception->scripts);
    }
}
