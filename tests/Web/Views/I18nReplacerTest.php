<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Views;

use PhpMvc\Files\FileManager;
use PhpMvc\LanguageSettings;
use PhpMvc\Requests\RequestContext;
use PhpMvc\Requests\RequestContextKeys;
use PhpMvc\Views\I18nReplacer;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class I18nReplacerTest extends TestCase
{
    private FileManager&Stub $fileManager;
    private I18nReplacer $i18nReplacer;
    private RequestContext $context;

    protected function setUp(): void
    {
        $settings = new LanguageSettings(basePath: __DIR__);
        $this->fileManager = $this->createStub(FileManager::class);
        $this->i18nReplacer = new I18nReplacer($settings, $this->fileManager);
        $this->context = new RequestContext([RequestContextKeys::Language->value => 'en']);
    }

    public function testReplacesKeysWithDictionaryValues(): void
    {
        $this->fileManager
            ->method('readKeyValueJson')
            ->willReturn([
                'greeting' => 'Hello',
                'name' => 'Peter',
            ])
        ;

        $result = $this->i18nReplacer->replace((object) [], '{{greeting}}, {{name}}!', $this->context);

        $this->assertSame('Hello, Peter!', $result);
    }

    public function testReplacesWithEmptyDictionary(): void
    {
        $this->fileManager->method('readKeyValueJson')->willReturn([]);

        $result = $this->i18nReplacer->replace((object) [], 'No keys here. {{some-key}}', $this->context);

        // With empty dictionary, remaining placeholders fall back to their key names.
        $this->assertSame('No keys here. some-key', $result);
    }

    public function testReplacesWithMissingKeysInDictionary(): void
    {
        $this->fileManager->method('readKeyValueJson')->willReturn(['greeting' => 'Hello']);

        $result = $this->i18nReplacer->replace((object) [], '{{greeting}}, {{name}}!', $this->context);

        // Known keys are replaced; unknown keys fall back to the plain key string.
        $this->assertSame('Hello, name!', $result);
    }

    public function testHandlesPrecomputedDynamicKey(): void
    {
        $this->fileManager
            ->method('readKeyValueJson')
            ->willReturn([
                'flash.success' => 'Operation completed successfully',
            ])
        ;

        $result = $this->i18nReplacer->replace(
            (object) [],
            'Status: {{flash.success}}',
            $this->context
        );

        $this->assertSame('Status: Operation completed successfully', $result);
    }

    public function testFallbackForPrecomputedMissingDynamicKey(): void
    {
        $this->fileManager
            ->method('readKeyValueJson')
            ->willReturn([
                'flash.success' => 'Operation completed successfully',
            ])
        ;

        $result = $this->i18nReplacer->replace(
            (object) [],
            'Status: {{flash.missing}}',
            $this->context
        );

        // Missing dynamic key falls back to the plain key string.
        $this->assertSame('Status: flash.missing', $result);
    }
}
