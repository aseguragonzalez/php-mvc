<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Views;

use AlfonsoSG\Mvc\Files\FileManager;
use AlfonsoSG\Mvc\LanguageSettings;
use AlfonsoSG\Mvc\Requests\RequestContext;
use AlfonsoSG\Mvc\Requests\RequestContextKeys;

final class I18nReplacer implements ContentReplacer
{
    public function __construct(
        private readonly LanguageSettings $settings,
        private readonly FileManager $fileManager,
    ) {}

    /**
     * @param null|array<string, mixed>|object $model
     */
    public function replace(array|object|null $model, string $template, RequestContext $context): string
    {
        $language = $context->get(RequestContextKeys::Language->value);
        $file = "{$this->settings->i18nPath}/{$language}.json";

        /** @var array<string, string> $languageKeyValueJson */
        $languageKeyValueJson = $this->fileManager->readKeyValueJson($file);
        $keys = array_map(fn ($key) => "{{{$key}}}", array_keys($languageKeyValueJson));
        $result = str_replace($keys, array_values($languageKeyValueJson), $template);

        // Post-processing step:
        // - Handle dynamically computed keys like {{flash.success}} that may have been produced
        //   by ModelReplacer (e.g. from {{flash.{{model->status}}}}).
        // - Apply fallback for missing keys by rendering the plain key string.
        if (preg_match_all('/\{\{(?!\{)(?!#)([^}{]+)\}\}(?!\})/', $result, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $placeholder = $match[0];
                $key = trim($match[1]);

                // Skip malformed or control placeholders.
                if ('' === $key || str_contains($key, '#') || str_contains($key, '{') || str_contains($key, '}')) {
                    continue;
                }

                $replacement = array_key_exists($key, $languageKeyValueJson)
                    ? $languageKeyValueJson[$key]
                    : $key;

                $result = str_replace($placeholder, $replacement, $result);
            }
        }

        return $result;
    }
}
