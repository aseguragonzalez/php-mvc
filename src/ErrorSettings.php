<?php

declare(strict_types=1);

namespace PhpMvc;

final readonly class ErrorSettings
{
    /**
     * @param array<class-string<\Throwable>, ErrorMapping> $errorsMapping
     */
    public function __construct(public array $errorsMapping, public ErrorMapping $errorsMappingDefaultValue) {}
}
