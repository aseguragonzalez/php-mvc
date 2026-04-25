<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Actions\Responses;

use AlfonsoSG\Mvc\Responses\Headers\Header;
use AlfonsoSG\Mvc\Responses\StatusCode;

abstract class ActionResponse
{
    /**
     * @param array<Header>               $headers
     * @param array<string, mixed>|object $data
     */
    public function __construct(
        public readonly array|object $data,
        public readonly array $headers,
        public readonly StatusCode $statusCode,
    ) {}
}
