<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Responses\Headers;

final readonly class AccessControlAllowHeaders extends Header
{
    public function __construct(
        private bool $contentTypes = true,
        private bool $authorization = true,
        private bool $accept = true,
        private bool $xRequestedWith = true,
        private bool $origin = true,
        private bool $userAgent = true,
        private bool $cacheControl = true,
        private bool $contentLength = true,
        private bool $acceptEncoding = true,
        private bool $acceptLanguage = true,
    ) {
        parent::__construct('Access-Control-Allow-Headers', $this->buildValue());
    }

    private function buildValue(): string
    {
        $headers = [];

        if ($this->contentTypes) {
            $headers[] = 'Content-Type';
        }

        if ($this->authorization) {
            $headers[] = 'Authorization';
        }

        if ($this->accept) {
            $headers[] = 'Accept';
        }

        if ($this->xRequestedWith) {
            $headers[] = 'X-Requested-With';
        }

        if ($this->origin) {
            $headers[] = 'Origin';
        }

        if ($this->userAgent) {
            $headers[] = 'User-Agent';
        }

        if ($this->cacheControl) {
            $headers[] = 'Cache-Control';
        }

        if ($this->contentLength) {
            $headers[] = 'Content-Length';
        }

        if ($this->acceptEncoding) {
            $headers[] = 'Accept-Encoding';
        }

        if ($this->acceptLanguage) {
            $headers[] = 'Accept-Language';
        }

        return 10 === count($headers) ? '*' : implode(', ', $headers);
    }
}
