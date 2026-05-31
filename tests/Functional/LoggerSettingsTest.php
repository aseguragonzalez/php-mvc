<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc;

use PhpMvc\LoggerSettings;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class LoggerSettingsTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $settings = new LoggerSettings();

        $this->assertSame('local', $settings->environment);
        $this->assertSame('my-app', $settings->serviceName);
        $this->assertSame('1.0.0', $settings->serviceVersion);
        $this->assertSame('debug', $settings->logLevel);
    }

    public function testCustomValues(): void
    {
        $settings = new LoggerSettings(
            environment: 'production',
            serviceName: 'orders-service',
            serviceVersion: '3.2.1',
            logLevel: 'warning',
        );

        $this->assertSame('production', $settings->environment);
        $this->assertSame('orders-service', $settings->serviceName);
        $this->assertSame('3.2.1', $settings->serviceVersion);
        $this->assertSame('warning', $settings->logLevel);
    }
}
