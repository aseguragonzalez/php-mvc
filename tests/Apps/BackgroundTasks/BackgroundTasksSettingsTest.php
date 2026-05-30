<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\BackgroundTasks;

use PhpMvc\BackgroundTasks\BackgroundTasksSettings;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class BackgroundTasksSettingsTest extends TestCase
{
    public function testGetDsnReturnsMysqlConnectionString(): void
    {
        $settings = new BackgroundTasksSettings(
            host: 'localhost',
            database: 'my_db',
            user: 'root',
            password: 'secret',
        );

        $this->assertSame(
            'mysql:host=localhost;dbname=my_db;charset=utf8mb4',
            $settings->getDsn()
        );
    }

    public function testGetDsnUsesCustomCharset(): void
    {
        $settings = new BackgroundTasksSettings(
            host: '127.0.0.1',
            database: 'app_db',
            user: 'user',
            password: 'pass',
            charset: 'utf8',
        );

        $this->assertSame(
            'mysql:host=127.0.0.1;dbname=app_db;charset=utf8',
            $settings->getDsn()
        );
    }
}
