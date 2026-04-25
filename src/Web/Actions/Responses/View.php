<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Actions\Responses;

use AlfonsoSG\Mvc\Responses\Headers\ContentType;
use AlfonsoSG\Mvc\Responses\Headers\Header;
use AlfonsoSG\Mvc\Responses\StatusCode;

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
