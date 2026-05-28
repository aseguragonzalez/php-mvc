<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Responses\Headers;

use PhpMvc\Responses\Headers\ContentLanguage;
use PhpMvc\Responses\Headers\ContentType;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ContentLanguageTest extends TestCase
{
    public function testToStringReturnsFormattedHeader(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('en');

        $this->assertSame('Content-Language: en', (string) $header);
    }

    public function testCreateFromCurrentLanguageWithEnglish(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('en');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('en', $header->value);
    }

    public function testCreateFromCurrentLanguageWithFrench(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('fr');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('fr', $header->value);
    }

    public function testCreateFromCurrentLanguageWithSpanish(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('es');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('es', $header->value);
    }

    public function testCreateFromCurrentLanguageWithPortuguese(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('pt');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('pt', $header->value);
    }

    public function testCreateFromCurrentLanguageWithGerman(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('de');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('de', $header->value);
    }

    public function testCreateFromCurrentLanguageWithItalian(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('it');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('it', $header->value);
    }

    public function testCreateFromCurrentLanguageWithDutch(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('nl');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('nl', $header->value);
    }

    public function testCreateFromCurrentLanguageWithRussian(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('ru');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('ru', $header->value);
    }

    public function testCreateFromCurrentLanguageThrowsExceptionForInvalidLanguage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid language: ja');

        ContentLanguage::createFromCurrentLanguage('ja');
    }

    public function testEqualsReturnsTrueForSameHeaders(): void
    {
        $header1 = ContentLanguage::createFromCurrentLanguage('es');
        $header2 = ContentLanguage::createFromCurrentLanguage('es');

        $this->assertTrue($header1->equals($header2));
    }

    public function testEqualsReturnsFalseForHeadersWithDifferentValues(): void
    {
        $header1 = ContentLanguage::createFromCurrentLanguage('en');
        $header2 = ContentLanguage::createFromCurrentLanguage('fr');

        $this->assertFalse($header1->equals($header2));
    }

    public function testEqualsReturnsFalseForHeadersWithDifferentNames(): void
    {
        $contentLanguage = ContentLanguage::createFromCurrentLanguage('en');
        $contentType = ContentType::json();

        $this->assertFalse($contentLanguage->equals($contentType));
    }

    public function testFrenchLanguage(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('fr');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('fr', $header->value);
    }

    public function testSpanishLanguage(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('es');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('es', $header->value);
    }

    public function testPortugueseLanguage(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('pt');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('pt', $header->value);
    }

    public function testGermanLanguage(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('de');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('de', $header->value);
    }

    public function testItalianLanguage(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('it');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('it', $header->value);
    }

    public function testDutchLanguage(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('nl');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('nl', $header->value);
    }

    public function testRussianLanguage(): void
    {
        $header = ContentLanguage::createFromCurrentLanguage('ru');

        $this->assertSame('Content-Language', $header->name);
        $this->assertSame('ru', $header->value);
    }
}
