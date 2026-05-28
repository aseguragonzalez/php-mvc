<?php

declare(strict_types=1);

namespace PhpMvc\Views;

use PhpMvc\Requests\RequestContext;

final readonly class BranchesReplacer implements ContentReplacer
{
    public function __construct(private ViewValueResolver $resolver) {}

    /**
     * @param null|array<string, mixed>|object $model
     */
    public function replace(array|object|null $model, string $template, RequestContext $context): string
    {
        if (null === $model) {
            return $template;
        }

        return $this->replaceBranches($model, $template);
    }

    /**
     * Replace #if/#endif blocks. Processes innermost blocks first so nested #if work.
     *
     * @param array<string, mixed>|object $model
     */
    private function replaceBranches(array|object $model, string $template): string
    {
        // Match innermost blocks only: block must not contain {{#if (so we process from inside out)
        $pattern = '/\{\{#if\s+(.+?):\}\}((?:(?!\{\{#if).)*?)\{\{#endif\s+\1:\}\}/s';
        while (preg_match($pattern, $template, $match)) {
            $expression = trim($match[1]);
            $blockContent = $match[2];
            $replacement = $this->resolver->isTruthy($model, $expression) ? $blockContent : '';
            $template = str_replace($match[0], $replacement, $template);
        }

        return $template;
    }
}
