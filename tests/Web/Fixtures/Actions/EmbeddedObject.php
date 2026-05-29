<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Fixtures\Actions;

use PhpMvc\Actions\ArrayOf;

final class EmbeddedObject
{
    /**
     * @param list<bool>               $boolItems
     * @param list<\DateTime>          $dateTimeItems
     * @param list<\DateTimeImmutable> $dateTimeImmutableItems
     * @param list<float>              $floatItems
     * @param list<int>                $intItems
     * @param list<string>             $stringItems
     */
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
