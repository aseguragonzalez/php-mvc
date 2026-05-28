<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Domain\Services;

use PhpMvc\Files\FileManager;
use PhpMvc\Migrations\Domain\Entities\Migration;
use PhpMvc\Migrations\Domain\Entities\Script;

final readonly class MigrationFileManagerHandler implements MigrationFileManager
{
    public function __construct(private FileManager $fileManager) {}

    /**
     * @return array<Migration>
     */
    public function getMigrations(string $basePath): array
    {
        $folders = $this->fileManager->getFoldersFromPath($basePath);

        return array_map(function (string $folder) use ($basePath) {
            $folderPath = "{$basePath}/{$folder}";
            $files = $this->fileManager->getFileNamesFromPath(
                $folderPath,
                extensions: ['sql'],
                notEndsWith: ['rollback']
            );
            $scripts = array_map(fn ($script) => Script::fromFile($folderPath, $script, $this->fileManager), $files);

            return Migration::new(name: $folder, scripts: $scripts);
        }, $folders);
    }

    public function getMigrationByName(string $basePath, string $migrationName): ?Migration
    {
        $migrationPath = "{$basePath}/{$migrationName}";

        if (!is_dir($migrationPath)) {
            return null;
        }

        $files = $this->fileManager->getFileNamesFromPath(
            path: $migrationPath,
            extensions: ['sql'],
            notEndsWith: ['rollback']
        );

        if (empty($files)) {
            return null;
        }

        $scripts = array_map(fn ($script) => Script::fromFile($migrationPath, $script, $this->fileManager), $files);

        return Migration::new(name: $migrationName, scripts: $scripts);
    }
}
