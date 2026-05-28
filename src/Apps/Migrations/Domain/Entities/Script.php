<?php

declare(strict_types=1);

namespace PhpMvc\Migrations\Domain\Entities;

use PhpMvc\Files\FileManager;

final readonly class Script
{
    private function __construct(
        public string $fileName,
        public ?string $content = null,
        public ?string $rollbackContent = null,
    ) {}

    public static function fromFile(string $basePath, string $fileName, FileManager $fileManager): self
    {
        $filePath = "{$basePath}/{$fileName}";
        $content = $fileManager->readTextPlain($filePath);

        $rollbackFilePath = str_replace('.sql', '.rollback.sql', $filePath);
        $rollbackContent = $fileManager->readTextPlain($rollbackFilePath);

        return new self($fileName, $content, $rollbackContent);
    }

    public static function build(string $fileName): self
    {
        return new self($fileName);
    }

    /**
     * @return array<string>
     */
    public function getStatements(): array
    {
        if (!$this->isLoaded()) {
            throw new \RuntimeException('Script is not loaded');
        }

        return $this->getSqlStatements($this->content ?? '');
    }

    /**
     * @return array<string>
     */
    public function getRollbackStatements(): array
    {
        if (!$this->isRollbackLoaded()) {
            throw new \RuntimeException('Script is not loaded');
        }

        return $this->getSqlStatements($this->rollbackContent ?? '');
    }

    private function isLoaded(): bool
    {
        return null !== $this->content;
    }

    /**
     * @return array<string>
     */
    private function getSqlStatements(string $fileContent): array
    {
        // Get file lines without comments and empty lines
        $fileLines = array_filter(
            explode("\n", $fileContent),
            fn ($line) => !empty($line) && !str_starts_with(trim($line), '--')
        );
        // Split the file content into statements separated by semicolons
        $statements = array_filter(explode(';', implode('', $fileLines)), fn ($stmt) => !empty(trim($stmt)));
        $trimmed = array_map(fn (string $stmt) => trim($stmt), $statements);
        // Drop USE statements so scripts are environment-agnostic; the app runs USE before each script
        $filtered = array_values(array_filter($trimmed, fn (string $stmt) => !$this->isUseStatement($stmt)));

        return array_map(fn (string $stmt) => $stmt.';', $filtered);
    }

    private function isUseStatement(string $stmt): bool
    {
        return (bool) preg_match('/^\s*USE\s+/i', trim($stmt));
    }

    private function isRollbackLoaded(): bool
    {
        return null !== $this->rollbackContent;
    }
}
