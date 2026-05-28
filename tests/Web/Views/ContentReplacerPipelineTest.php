<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Views;

use PhpMvc\Requests\RequestContext;
use PhpMvc\Views\ContentReplacer;
use PhpMvc\Views\ContentReplacerPipeline;
use PHPUnit\Framework\TestCase;
use Tests\Unit\PhpMvc\Fixtures\Views\OrderRecorder;

/**
 * @internal
 *
 * @coversNothing
 */
final class ContentReplacerPipelineTest extends TestCase
{
    public function testRunsReplacersInOrder(): void
    {
        $recorder = new OrderRecorder();
        $first = new class($recorder) implements ContentReplacer {
            public function __construct(
                private readonly OrderRecorder $recorder
            ) {}

            /**
             * @param null|array<string, mixed>|object $model
             */
            public function replace(array|object|null $model, string $template, RequestContext $context): string
            {
                $this->recorder->order[] = 1;

                return $template.'-1';
            }
        };
        $second = new class($recorder) implements ContentReplacer {
            public function __construct(
                private readonly OrderRecorder $recorder
            ) {}

            /**
             * @param null|array<string, mixed>|object $model
             */
            public function replace(array|object|null $model, string $template, RequestContext $context): string
            {
                $this->recorder->order[] = 2;

                return $template.'-2';
            }
        };
        $pipeline = new ContentReplacerPipeline([$first, $second]);

        $result = $pipeline->replace((object) [], 'base', new RequestContext());

        $this->assertSame([1, 2], $recorder->order);
        $this->assertSame('base-1-2', $result);
    }

    public function testOutputOfOneReplacerIsInputToNext(): void
    {
        $replacerA = new class implements ContentReplacer {
            /**
             * @param null|array<string, mixed>|object $model
             */
            public function replace(array|object|null $model, string $template, RequestContext $context): string
            {
                return str_replace('{{a}}', 'A', $template);
            }
        };
        $replacerB = new class implements ContentReplacer {
            /**
             * @param null|array<string, mixed>|object $model
             */
            public function replace(array|object|null $model, string $template, RequestContext $context): string
            {
                return str_replace('{{b}}', 'B', $template);
            }
        };
        $pipeline = new ContentReplacerPipeline([$replacerA, $replacerB]);

        $result = $pipeline->replace((object) [], '{{a}} and {{b}}', new RequestContext());

        $this->assertSame('A and B', $result);
    }
}
