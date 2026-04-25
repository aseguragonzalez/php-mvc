<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Commands;

final class StubGenerator
{
    private string $stubsPath;

    public function __construct(?string $stubsPath = null)
    {
        $this->stubsPath = $stubsPath ?? __DIR__.'/stubs';
    }

    /**
     * @param array<string, string> $replacements
     */
    public function generate(string $stubName, array $replacements): string
    {
        $stubFile = $this->stubsPath.'/'.$stubName;

        if (!file_exists($stubFile)) {
            throw new \RuntimeException("Stub file not found: {$stubFile}");
        }

        $content = file_get_contents($stubFile);
        if (false === $content) {
            throw new \RuntimeException("Failed to read stub file: {$stubFile}");
        }

        foreach ($replacements as $placeholder => $value) {
            $content = str_replace('{{'.$placeholder.'}}', $value, $content);
        }

        return $content;
    }
}
