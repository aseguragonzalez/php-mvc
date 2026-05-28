<?php

declare(strict_types=1);

namespace PhpMvc\Responses\Headers;

final readonly class AccessControlAllowMethods extends Header
{
    public function __construct(
        private bool $get = true,
        private bool $post = true,
        private bool $put = true,
        private bool $delete = true,
        private bool $options = true,
        private bool $head = true,
        private bool $patch = true,
        private bool $connect = true,
        private bool $trace = true
    ) {
        parent::__construct('Access-Control-Allow-Methods', $this->buildValue());
    }

    private function buildValue(): string
    {
        $methods = [];

        if ($this->get) {
            $methods[] = 'GET';
        }

        if ($this->post) {
            $methods[] = 'POST';
        }

        if ($this->put) {
            $methods[] = 'PUT';
        }

        if ($this->delete) {
            $methods[] = 'DELETE';
        }

        if ($this->options) {
            $methods[] = 'OPTIONS';
        }

        if ($this->head) {
            $methods[] = 'HEAD';
        }

        if ($this->patch) {
            $methods[] = 'PATCH';
        }

        if ($this->connect) {
            $methods[] = 'CONNECT';
        }

        if ($this->trace) {
            $methods[] = 'TRACE';
        }

        return 9 === count($methods) ? '*' : implode(', ', $methods);
    }
}
