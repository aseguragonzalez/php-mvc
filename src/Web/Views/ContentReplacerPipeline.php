<?php

declare(strict_types=1);

namespace PhpMvc\Views;

use PhpMvc\Requests\RequestContext;

/**
 * Runs an ordered list of ContentReplacer in sequence. Each replacer receives
 * the output of the previous one as the template. Order: first in list runs first.
 */
final readonly class ContentReplacerPipeline implements ContentReplacer
{
    /**
     * @param list<ContentReplacer> $replacers
     */
    public function __construct(private array $replacers) {}

    /**
     * @param null|array<string, mixed>|object $model
     */
    public function replace(array|object|null $model, string $template, RequestContext $context): string
    {
        $result = $template;
        foreach ($this->replacers as $replacer) {
            $result = $replacer->replace($model, $result, $context);
        }

        return $result;
    }
}
