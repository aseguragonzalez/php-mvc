<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Fixtures\Actions;

use AlfonsoSG\Mvc\Actions\ArrayOf;

final class EmbeddedObject
{
    public function __construct(
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
    ) {}
}
