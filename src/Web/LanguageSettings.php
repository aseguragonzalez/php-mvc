<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc;

final readonly class LanguageSettings
{
    public string $i18nPath;

    /**
     * @param array<string> $languages
     */
    public function __construct(
        string $basePath,
        string $assetsPath = 'assets/i18n/',
        public array $languages = ['en'],
        public string $cookieName = 'lang',
        public string $defaultValue = 'en',
        public string $setUrl = '/set-language',
    ) {
        $this->i18nPath = "{$basePath}/{$assetsPath}";
    }
}
