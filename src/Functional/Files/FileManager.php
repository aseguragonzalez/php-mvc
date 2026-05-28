<?php

declare(strict_types=1);

namespace PhpMvc\Files;

interface FileManager
{
    public function readTextPlain(string $path): string;

    /**
     * @return array<string, string>
     */
    public function readKeyValueJson(string $path): array;

    /**
     * @param array<string> $extensions
     * @param array<string> $notEndsWith
     *
     * @return array<string>
     */
    public function getFileNamesFromPath(
        string $path,
        array $extensions = [],
        array $notEndsWith = []
    ): array;

    /**
     * @return array<string>
     */
    public function getFoldersFromPath(string $path): array;
}
