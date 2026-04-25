<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Responses\Headers;

final readonly class Expires extends Header
{
    public function __construct(\DateTimeImmutable $expires)
    {
        parent::__construct('Expires', $expires->format('D, d M Y H:i:s \G\M\T'));
    }
}
