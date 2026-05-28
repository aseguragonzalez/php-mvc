<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Fixtures\Actions;

use PhpMvc\Actions\ArrayOf;

final class RequestObject
{
    public function __construct(
        public readonly int $id = 0,
        public readonly float $amount = 0.0,
        public readonly string $name = '',
        public readonly string $uuid = '',
        public readonly ?\DateTime $date = null,
        public readonly ?\DateTimeImmutable $dateImmutable = null,
        public readonly bool $active = false,
        public readonly ?InnerTypeObject $innerTypeObject = null,
        #[ArrayOf('bool')]
        public readonly array $boolItems = [],
        #[ArrayOf(\DateTime::class)]
        public readonly array $dateTimeItems = [],
        #[ArrayOf(\DateTimeImmutable::class)]
        public readonly array $dateTimeImmutableItems = [],
        #[ArrayOf('float')]
        public readonly array $floatItems = [],
        #[ArrayOf('int')]
        public readonly array $intItems = [],
        #[ArrayOf('string')]
        public readonly array $stringItems = [],
        #[ArrayOf(InnerTypeObject::class)]
        public readonly array $customClassType = [],
        public readonly ?EmbeddedObject $embeddedObject = null,
    ) {}
}
