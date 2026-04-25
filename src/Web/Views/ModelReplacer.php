<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Views;

use AlfonsoSG\Mvc\Requests\RequestContext;

final readonly class ModelReplacer implements ContentReplacer
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

        return $this->replaceContent($model, $template);
    }

    /**
     * Recursively replace {{path}}, {{#for}} blocks. Scope can be object or array.
     *
     * @param array<string, mixed>|object $scope
     */
    private function replaceContent(array|object $scope, string $template): string
    {
        $replacements = [];
        // 0. Process raw {{{path}}} placeholders (no escaping, used sparingly for trusted HTML)
        if (preg_match_all('/\{\{\{([^}]+)\}\}\}/', $template, $rawMatches, PREG_SET_ORDER)) {
            foreach ($rawMatches as $rawMatch) {
                $path = trim($rawMatch[1]);
                $placeholder = $rawMatch[0];
                if (!array_key_exists($placeholder, $replacements) && $this->resolver->pathExists($scope, $path)) {
                    $value = $this->resolver->resolve($scope, $path);
                    $replacements[$placeholder] = $this->formatRawValue($value);
                }
            }
            $template = str_replace(array_keys($replacements), array_values($replacements), $template);
        }

        // 1.5. Flatten one-level nested placeholders used for dynamic i18n keys, e.g.:
        // {{availabilities.dayOfWeek.{{availability->dayOfWeekId}}}} -> {{availabilities.dayOfWeek.1}}
        $template = $this->flattenNestedPlaceholders($scope, $template);

        // 1. Process {{#for var in path:}}...{{#endfor path:}}
        if (
            preg_match_all(
                '/\{\{#for\s+(.+?)\s+in\s+(.+?):\}\}(.*?)\{\{#endfor\s+\2:\}\}/s',
                $template,
                $matches,
                PREG_SET_ORDER,
            )
        ) {
            foreach ($matches as $match) {
                $loopVariable = trim($match[1]);
                $path = trim($match[2]);
                $blockContent = $match[3];
                $collection = $this->resolver->resolve($scope, $path);
                $content = '';
                if (is_array($collection)) {
                    foreach ($collection as $item) {
                        $itemScope = (object) [$loopVariable => $item];
                        $content .= $this->replaceContent($itemScope, $blockContent);
                    }
                }
                $replacements[$match[0]] = $content;
            }

            /** @var array<string, string> $replacements */
            $template = str_replace(array_keys($replacements), array_values($replacements), $template);
        }

        // 2. Process simple {{path}} (exclude {{# directives and raw {{{ }}}).
        //    Only resolve to a model value when the path exists in the scope so that placeholders
        //    not in the model (e.g. i18n keys like {{layout.app}}) are left for I18nReplacer. When the path exists
        //    but value is null we replace with "" so that e.g. {{email}} becomes empty.
        if (preg_match_all('/(?<!\{)\{\{(?!\{)(?!#)([^}]+)\}\}(?!\})/', $template, $tagMatches, PREG_SET_ORDER)) {
            $replacements = [];
            foreach ($tagMatches as $tagMatch) {
                $path = trim($tagMatch[1]);
                $placeholder = $tagMatch[0];
                if (!array_key_exists($placeholder, $replacements)) {
                    if ($this->resolver->pathExists($scope, $path)) {
                        $value = $this->resolver->resolve($scope, $path);
                        $replacements[$placeholder] = $this->formatValue($value);
                    }
                }
            }
            $template = str_replace(array_keys($replacements), array_values($replacements), $template);
        }

        return $template;
    }

    private function formatValue(mixed $value): string
    {
        $stringValue = match (true) {
            $value instanceof \DateTimeImmutable => $value->format(\DateTime::ISO8601_EXPANDED),
            $value instanceof \DateTime => $value->format(\DateTime::ISO8601_EXPANDED),
            is_bool($value) => $value ? 'true' : 'false',
            is_numeric($value) => (string) $value,
            is_string($value) => $value,
            default => '',
        };

        return htmlspecialchars($stringValue, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }

    private function formatRawValue(mixed $value): string
    {
        return match (true) {
            $value instanceof \DateTimeImmutable => $value->format(\DateTime::ISO8601_EXPANDED),
            $value instanceof \DateTime => $value->format(\DateTime::ISO8601_EXPANDED),
            is_bool($value) => $value ? 'true' : 'false',
            is_numeric($value) => (string) $value,
            is_string($value) => $value,
            default => '',
        };
    }

    /**
     * Flattens one-level nested placeholders of the form:
     *   {{prefix.{{innerPath}}}} -> {{prefix.innerValue}}
     *
     * This is enough for dynamic i18n keys like:
     *   {{availabilities.dayOfWeek.{{availability->dayOfWeekId}}}}
     *
     * @param array<string, mixed>|object $scope
     */
    private function flattenNestedPlaceholders(array|object $scope, string $template): string
    {
        if (!str_contains($template, '{{')) {
            return $template;
        }

        if (
            !preg_match_all(
                '/\{\{([^{}]+)\{\{(?!#)([^{}]+)\}\}\}\}/',
                $template,
                $matches,
                PREG_SET_ORDER,
            )
        ) {
            return $template;
        }

        $replacements = [];
        foreach ($matches as $match) {
            $outerPlaceholder = $match[0];
            $prefix = trim($match[1]);
            $innerPath = trim($match[2]);

            if (array_key_exists($outerPlaceholder, $replacements)) {
                continue;
            }

            if (!$this->resolver->pathExists($scope, $innerPath)) {
                continue;
            }

            $value = $this->resolver->resolve($scope, $innerPath);
            $flattenedExpression = $prefix.$this->formatRawValue($value);
            $replacements[$outerPlaceholder] = '{{'.$flattenedExpression.'}}';
        }

        if ([] === $replacements) {
            return $template;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
