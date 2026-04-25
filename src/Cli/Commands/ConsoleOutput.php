<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Commands;

final class ConsoleOutput
{
    private bool $colorsEnabled;

    /** @var resource */
    private mixed $stdout;

    /** @var resource */
    private mixed $stderr;

    /**
     * @param null|resource $stdout
     * @param null|resource $stderr
     */
    public function __construct(mixed $stdout = null, mixed $stderr = null)
    {
        $this->colorsEnabled = false === getenv('NO_COLOR');
        $this->stdout = $stdout ?? \STDOUT;
        $this->stderr = $stderr ?? \STDERR;
    }

    public function info(string $message): void
    {
        $this->writeln($this->colorize($message, '36'));
    }

    public function success(string $message): void
    {
        $this->writeln($this->colorize($message, '32'));
    }

    public function error(string $message): void
    {
        fwrite($this->stderr, $this->colorize($message, '31').PHP_EOL);
    }

    public function line(string $message = ''): void
    {
        $this->writeln($message);
    }

    private function writeln(string $message): void
    {
        fwrite($this->stdout, $message.PHP_EOL);
    }

    private function colorize(string $message, string $colorCode): string
    {
        if (!$this->colorsEnabled) {
            return $message;
        }

        return "\033[{$colorCode}m{$message}\033[0m";
    }
}
