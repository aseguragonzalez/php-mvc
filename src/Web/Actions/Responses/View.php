<?php

declare(strict_types=1);

namespace PhpMvc\Actions\Responses;

use PhpMvc\Responses\Headers\ContentType;
use PhpMvc\Responses\Headers\Header;
use PhpMvc\Responses\StatusCode;

final class View extends ActionResponse
{
    /**
     * @param array<Header>                    $headers
     * @param null|array<string, mixed>|object $data
     */
    public function __construct(
        public readonly string $viewPath,
        array|object|null $data = null,
        array $headers = [],
        StatusCode $statusCode = StatusCode::Ok
    ) {
        $headers = array_merge($headers, [ContentType::html()]);
        parent::__construct(headers: $headers, statusCode: $statusCode, data: $data ?? new \stdClass());
    }
}
