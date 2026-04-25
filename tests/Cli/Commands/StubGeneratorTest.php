<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Commands;

use AlfonsoSG\Mvc\Commands\StubGenerator;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class StubGeneratorTest extends TestCase
{
    public function testGenerateReplacesPlaceholders(): void
    {
        $root = vfsStream::setup('stubs');
        vfsStream::newFile('test.stub')
            ->at($root)
            ->setContent('Hello {{name}}, welcome to {{app}}!')
        ;

        $generator = new StubGenerator(vfsStream::url('stubs'));

        $result = $generator->generate('test.stub', [
            'name' => 'Alice',
            'app' => 'MyApp',
        ]);

        $this->assertSame('Hello Alice, welcome to MyApp!', $result);
    }

    public function testGenerateWithNoPlaceholdersReturnsContentUnchanged(): void
    {
        $root = vfsStream::setup('stubs');
        vfsStream::newFile('plain.stub')
            ->at($root)
            ->setContent('No placeholders here.')
        ;

        $generator = new StubGenerator(vfsStream::url('stubs'));

        $result = $generator->generate('plain.stub', []);

        $this->assertSame('No placeholders here.', $result);
    }

    public function testGenerateReplacesMultipleOccurrencesOfSamePlaceholder(): void
    {
        $root = vfsStream::setup('stubs');
        vfsStream::newFile('repeat.stub')
            ->at($root)
            ->setContent('{{name}} and {{name}} again')
        ;

        $generator = new StubGenerator(vfsStream::url('stubs'));

        $result = $generator->generate('repeat.stub', ['name' => 'Bob']);

        $this->assertSame('Bob and Bob again', $result);
    }

    public function testGenerateThrowsWhenStubFileNotFound(): void
    {
        $root = vfsStream::setup('stubs');
        $generator = new StubGenerator(vfsStream::url('stubs'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stub file not found');

        $generator->generate('missing.stub', []);
    }

    public function testGenerateUsesDefaultStubsPathWhenNoneProvided(): void
    {
        $generator = new StubGenerator();
        $result = $generator->generate('htaccess.stub', []);

        $this->assertStringContainsString('RewriteEngine On', $result);
    }
}
