<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Migrations;

use PhpMvc\Migrations\MigrationSettings;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MigrationSettingsTest extends TestCase
{
    public function testGetDsnReturnsMysqlConnectionString(): void
    {
        $settings = new MigrationSettings(
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
        $settings = new MigrationSettings(
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
