<?php

declare(strict_types=1);

namespace PhpMvc\Files;

final class DefaultFileManager implements FileManager
{
    public function readTextPlain(string $path): string
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        $content = file_get_contents($path);
        if (false === $content) {
            throw new \RuntimeException("Failed to read file: {$path}");
        }

        return $content;
    }

    /**
     * @return array<string, string>
     */
    public function readKeyValueJson(string $path): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        $content = file_get_contents($path);
        if (false === $content) {
            throw new \RuntimeException("Failed to read file: {$path}");
        }

        /** @var null|array<string, string> $dictionary */
        $dictionary = json_decode($content, true);
        if (null === $dictionary) {
            throw new \RuntimeException('Failed to decode JSON: '.json_last_error_msg());
        }

        return $dictionary;
    }

    /**
     * @return array<string>
     */
    public function getFileNamesFromPath(
        string $path,
        array $extensions = [],
        array $notEndsWith = []
    ): array {
        if (!is_dir($path)) {
            return [];
        }

        return array_filter(scandir($path), fn ($file) => self::isValidFile($path, $file, $extensions, $notEndsWith));
    }

    /**
     * @return array<string>
     */
    public function getFoldersFromPath(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        return array_filter(scandir($path), fn ($folder) => self::isValidFolder($path, $folder));
    }

    /**
     * @param array<string> $extensions
     * @param array<string> $notEndsWith
     */
    private static function isValidFile(
        string $path,
        string $fileName,
        array $extensions = [],
        array $notEndsWith = []
    ): bool {
        $filePath = "{$path}/{$fileName}";
        if (!file_exists($filePath) || !is_file($filePath)) {
            return false;
        }

        $fileInfo = pathinfo($fileName);

        if (!empty($extensions) && !in_array($fileInfo['extension'] ?? '', $extensions)) {
            return false;
        }

        if (empty($notEndsWith)) {
            return true;
        }

        foreach ($notEndsWith as $endWith) {
            if (str_ends_with($fileInfo['filename'], $endWith)) {
                return false;
            }
        }

        return true;
    }

    private static function isValidFolder(string $path, string $folder): bool
    {
        return '.' !== $folder && '..' !== $folder && is_dir($path.'/'.$folder);
    }
}
