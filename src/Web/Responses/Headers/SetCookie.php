<?php

declare(strict_types=1);

namespace PhpMvc\Responses\Headers;

final readonly class SetCookie extends Header
{
    /**
     * @param int    $expires  Unix timestamp for cookie expiration. (Default: 0)
     *                         - If $expires > 0: Sets the cookie expiration to the specified timestamp (formatted as GMT date string)
     *                         - If $expires === 0: Sets cookie to expire immediately (Thu, 01 Jan 1970 00:00:00 GMT) with Max-Age=0,
     *                         effectively removing/deleting the cookie if the cookie value is empty. Else, it will be a session
     *                         cookie that expires when the browser closes.
     *                         - If $expires < 0: Sets cookie as expires in the past, effectively removing/deleting the cookie.
     * @param string $path     the path on the server in which the cookie will be available on
     * @param bool   $secure   whether the cookie should only be sent over a secure HTTPS connection
     * @param bool   $httpOnly whether the cookie should only be accessible via HTTP(S), not JavaScript
     * @param string $sameSite whether the cookie should be sent with requests originating from the same site
     */
    private function __construct(
        private string $cookieName,
        private string $cookieValue,
        private int $expires = 0,
        private string $path = '/',
        private bool $secure = false,
        private bool $httpOnly = false,
        private string $sameSite = 'Lax'
    ) {
        parent::__construct('Set-Cookie', $this->buildValue());
    }

    public static function removeCookie(string $cookieName): self
    {
        return new self($cookieName, '', 0);
    }

    /**
     * Creates a secure cookie with the given name, value, expiration date, path, and secure flag.
     *
     * @param string $cookieName  the name of the cookie
     * @param string $cookieValue the value of the cookie
     * @param int    $expires     The expiration date of the cookie. (Default: 0)
     *                            - If $expires > 0: Sets the cookie expiration to the specified timestamp (formatted as GMT date string)
     *                            - If $expires === 0: Sets cookie to expire immediately (Thu, 01 Jan 1970 00:00:00 GMT) with Max-Age=0,
     *                            effectively removing/deleting the cookie if the cookie value is empty. Else, it will be a session
     *                            cookie that expires when the browser closes.
     *                            - If $expires < 0: Sets cookie as expires in the past, effectively removing/deleting the cookie.
     * @param string $path        The path on the server in which the cookie will be available on. (Default: '/')
     *
     * @return self the created cookie
     */
    public static function createSecureCookie(
        string $cookieName,
        string $cookieValue,
        int $expires = 0,
        string $path = '/',
    ): self {
        return new self(
            cookieName: $cookieName,
            cookieValue: $cookieValue,
            expires: $expires,
            path: $path,
            secure: true,
            httpOnly: true,
            sameSite: 'Strict'
        );
    }

    private function buildValue(): string
    {
        $value = urlencode($this->cookieName).'='.urlencode($this->cookieValue);

        if ($this->expires > 0) {
            $value .= '; Expires='.gmdate('D, d-M-Y H:i:s T', $this->expires);
        } elseif (0 === $this->expires) {
            $value .= '; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Max-Age=0';
        }

        if ('' !== $this->path) {
            $value .= '; Path='.$this->path;
        }

        if ($this->secure) {
            $value .= '; Secure';
        }

        if ($this->httpOnly) {
            $value .= '; HttpOnly';
        }

        if ('' !== $this->sameSite) {
            $value .= '; SameSite='.$this->sameSite;
        }

        return $value;
    }
}
