<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Responses\Headers;

use AlfonsoSG\Mvc\Responses\Headers\ContentType;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ContentTypeTest extends TestCase
{
    public function testJsonContentType(): void
    {
        $header = ContentType::json();
        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('application/json', $header->value);
    }

    public function testXmlContentType(): void
    {
        $header = ContentType::xml();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('application/xml', $header->value);
    }

    public function testHtmlContentType(): void
    {
        $header = ContentType::html();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('text/html', $header->value);
    }

    public function testTextContentType(): void
    {
        $header = ContentType::text();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('text/plain', $header->value);
    }

    public function testCssContentType(): void
    {
        $header = ContentType::css();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('text/css', $header->value);
    }

    public function testJavascriptContentType(): void
    {
        $header = ContentType::javascript();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('application/javascript', $header->value);
    }

    public function testFormUrlEncodedContentType(): void
    {
        $header = ContentType::formUrlEncoded();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('application/x-www-form-urlencoded', $header->value);
    }

    public function testFormDataContentType(): void
    {
        $header = ContentType::formData();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('multipart/form-data', $header->value);
    }

    public function testOctetStreamContentType(): void
    {
        $header = ContentType::octetStream();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('application/octet-stream', $header->value);
    }

    public function testPdfContentType(): void
    {
        $header = ContentType::pdf();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('application/pdf', $header->value);
    }

    public function testZipContentType(): void
    {
        $header = ContentType::zip();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('application/zip', $header->value);
    }

    public function testTarContentType(): void
    {
        $header = ContentType::tar();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('application/x-tar', $header->value);
    }

    public function testGzipContentType(): void
    {
        $header = ContentType::gzip();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('application/gzip', $header->value);
    }

    public function testRarContentType(): void
    {
        $header = ContentType::rar();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('application/x-rar-compressed', $header->value);
    }

    public function testSevenZipContentType(): void
    {
        $header = ContentType::sevenZip();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('application/x-7z-compressed', $header->value);
    }

    public function testPngContentType(): void
    {
        $header = ContentType::png();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('image/png', $header->value);
    }

    public function testJpegContentType(): void
    {
        $header = ContentType::jpeg();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('image/jpeg', $header->value);
    }

    public function testGifContentType(): void
    {
        $header = ContentType::gif();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('image/gif', $header->value);
    }

    public function testBmpContentType(): void
    {
        $header = ContentType::bmp();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('image/bmp', $header->value);
    }

    public function testWebpContentType(): void
    {
        $header = ContentType::webp();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('image/webp', $header->value);
    }

    public function testSvgContentType(): void
    {
        $header = ContentType::svg();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('image/svg+xml', $header->value);
    }

    public function testMpegContentType(): void
    {
        $header = ContentType::mpeg();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('audio/mpeg', $header->value);
    }

    public function testAudioOggContentType(): void
    {
        $header = ContentType::audioOgg();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('audio/ogg', $header->value);
    }

    public function testWavContentType(): void
    {
        $header = ContentType::wav();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('audio/wav', $header->value);
    }

    public function testMp4ContentType(): void
    {
        $header = ContentType::mp4();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('video/mp4', $header->value);
    }

    public function testVideoOggContentType(): void
    {
        $header = ContentType::videoOgg();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('video/ogg', $header->value);
    }

    public function testWebmContentType(): void
    {
        $header = ContentType::webm();

        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('video/webm', $header->value);
    }

    public function testToString(): void
    {
        $header = ContentType::json();

        $this->assertSame('Content-Type: application/json', (string) $header);
    }
}
