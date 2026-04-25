<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Responses\Headers;

use AlfonsoSG\Mvc\Responses\Headers\SetCookie;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SetCookieTest extends TestCase
{
    public function testSetCookieWithoutExpires(): void
    {
        $setCookie = SetCookie::createSecureCookie('test', 'value', -1);

        $this->assertStringNotContainsString('Expires=', $setCookie->value);
    }

    public function testSetCookieToString(): void
    {
        $expires = time() + 3600;
        $setCookie = SetCookie::createSecureCookie('test', 'value', $expires, '/path');
        $expectedString = 'Set-Cookie: test=value; Expires='
            .gmdate('D, d-M-Y H:i:s T', $expires)
            .'; Path=/path; Secure; HttpOnly; SameSite=Strict';

        $this->assertSame($expectedString, (string) $setCookie);
    }

    public function testRemoveCookie(): void
    {
        $setCookie = SetCookie::removeCookie('testCookie');

        $this->assertSame('Set-Cookie', $setCookie->name);
        $this->assertStringContainsString('testCookie=', $setCookie->value);
        $this->assertStringContainsString('Expires=Thu, 01 Jan 1970 00:00:00 GMT', $setCookie->value);
        $this->assertStringContainsString('Max-Age=0', $setCookie->value);
        $this->assertStringContainsString('Path=/', $setCookie->value);
    }

    public function testCreateSecureCookie(): void
    {
        $expires = time() + 3600;
        $setCookie = SetCookie::createSecureCookie('authToken', 'token123', $expires);

        $this->assertSame('Set-Cookie', $setCookie->name);
        $this->assertStringContainsString('authToken=token123', $setCookie->value);
        $this->assertStringContainsString('Expires='.gmdate('D, d-M-Y H:i:s T', $expires), $setCookie->value);
        $this->assertStringContainsString('Path=/', $setCookie->value);
        $this->assertStringContainsString('Secure', $setCookie->value);
        $this->assertStringContainsString('HttpOnly', $setCookie->value);
        $this->assertStringContainsString('SameSite=Strict', $setCookie->value);
    }

    public function testCreateSecureCookieWithCustomPath(): void
    {
        $setCookie = SetCookie::createSecureCookie('authToken', 'token123', 0, '/admin');

        $this->assertSame('Set-Cookie', $setCookie->name);
        $this->assertStringContainsString('authToken=token123', $setCookie->value);
        $this->assertStringContainsString('Path=/admin', $setCookie->value);
        $this->assertStringContainsString('Secure', $setCookie->value);
        $this->assertStringContainsString('HttpOnly', $setCookie->value);
        $this->assertStringContainsString('SameSite=Strict', $setCookie->value);
    }

    public function testSetCookieWithUrlEncodedNameAndValue(): void
    {
        $setCookie = SetCookie::createSecureCookie('cookie name', 'value=with=equals; and semicolons');

        $this->assertSame('Set-Cookie', $setCookie->name);
        $this->assertStringContainsString('cookie+name=', $setCookie->value);
        $this->assertStringContainsString('value%3Dwith%3Dequals%3B+and+semicolons', $setCookie->value);
    }

    public function testSetCookieWithExpiresZero(): void
    {
        $setCookie = SetCookie::createSecureCookie('test', 'value', 0);

        $this->assertSame('Set-Cookie', $setCookie->name);
        $this->assertStringContainsString('test=value', $setCookie->value);
        $this->assertStringContainsString('Expires=Thu, 01 Jan 1970 00:00:00 GMT', $setCookie->value);
        $this->assertStringContainsString('Max-Age=0', $setCookie->value);
    }
}
