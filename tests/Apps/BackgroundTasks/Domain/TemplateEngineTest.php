<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\BackgroundTasks\Domain;

use PhpMvc\BackgroundTasks\Domain\TemplateEngine;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class TemplateEngineTest extends TestCase
{
    private TemplateEngine $templateEngine;

    protected function setUp(): void
    {
        $this->templateEngine = new TemplateEngine();
    }

    public function testRenderReplacesPlaceholdersWithSanitizedValues(): void
    {
        $template = 'Hello {{name}}, link: {{link}}.';
        $values = [
            'name' => 'Alice',
            'link' => 'https://example.com',
        ];

        $result = $this->templateEngine->render($template, $values);

        $this->assertSame('Hello Alice, link: https://example.com.', $result);
    }

    public function testRenderEscapesHtmlInValues(): void
    {
        $template = 'Content: {{payload}}';
        $values = [
            'payload' => '<script>alert(1)</script>',
        ];

        $result = $this->templateEngine->render($template, $values);

        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testRenderCastsNonStringScalarsToStringThenEscapes(): void
    {
        $template = 'Count: {{count}}, flag: {{flag}}';
        $values = [
            'count' => 42,
            'flag' => true,
        ];

        $result = $this->templateEngine->render($template, $values);

        $this->assertSame('Count: 42, flag: 1', $result);
    }

    public function testRenderLeavesMissingPlaceholdersUnchanged(): void
    {
        $template = 'Known: {{known}}, unknown: {{unknown}}';
        $values = [
            'known' => 'value',
        ];

        $result = $this->templateEngine->render($template, $values);

        $this->assertSame('Known: value, unknown: {{unknown}}', $result);
    }

    public function testRenderReturnsTemplateUnchangedWhenValuesEmpty(): void
    {
        $template = 'Hello {{name}}';
        $values = [];

        $result = $this->templateEngine->render($template, $values);

        $this->assertSame('Hello {{name}}', $result);
    }
}
