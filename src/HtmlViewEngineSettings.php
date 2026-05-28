<?php

declare(strict_types=1);

namespace PhpMvc;

final readonly class HtmlViewEngineSettings
{
    public string $path;

    public function __construct(string $basePath, string $viewPath = 'Views/')
    {
        $this->path = "{$basePath}/{$viewPath}";
    }
}
