<?php

declare(strict_types=1);

namespace PhpMvc\BackgroundTasks\Domain;

final readonly class TemplateEngine
{
    /**
     * Renders template content by replacing {{key}} placeholders with sanitized values.
     * Values are cast to string and HTML-escaped for safe HTML output.
     * Placeholders whose keys are not in $values are left unchanged.
     *
     * @param array<string, bool|float|int|string> $values
     */
    public function render(string $templateContent, array $values): string
    {
        if ([] === $values) {
            return $templateContent;
        }

        $placeholders = [];
        $sanitizedValues = [];

        foreach ($values as $key => $value) {
            $placeholders[] = '{{'.$key.'}}';
            $sanitizedValues[] = htmlspecialchars(
                (string) $value,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
        }

        return str_replace($placeholders, $sanitizedValues, $templateContent);
    }
}
