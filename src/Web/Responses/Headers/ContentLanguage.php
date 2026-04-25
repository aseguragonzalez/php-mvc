<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Responses\Headers;

final readonly class ContentLanguage extends Header
{
    private function __construct(string $value)
    {
        parent::__construct('Content-Language', $value);
    }

    public static function createFromCurrentLanguage(string $language): ContentLanguage
    {
        if (!in_array($language, ['en', 'fr', 'es', 'pt', 'de', 'it', 'nl', 'ru'], true)) {
            throw new \InvalidArgumentException("Invalid language: {$language}");
        }

        return new self($language);
    }
}
