<?php

declare(strict_types=1);

namespace PhpMvc\Config;

/** One logical group of asset sources (e.g. a route area), merged in config order into a single bundle. */
final readonly class AssetRouteGroup
{
    /**
     * @param list<string> $js  Paths relative to the MVC app root
     * @param list<string> $css Paths relative to the MVC app root
     */
    public function __construct(
        public string $label,
        public array $js,
        public array $css,
    ) {}
}
