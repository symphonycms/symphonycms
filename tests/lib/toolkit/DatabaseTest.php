<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers Database
 */
final class DatabaseTest extends TestCase
{
    public function testGetDSN_TCP()
    {
        $db = new Database([
            'host' => '127.0.0.1',
            'port' => '3306',
            'db' => 'test',
            'driver' => 'mysql',
            'charset' => 'utf8mb4',
        ]);

        $this->assertEquals('mysql:dbname=test;host=127.0.0.1;port=3306;charset=utf8mb4', $db->getDSN());
    }

    public function testGetDSN_UNIXSOCK()
    {
        $db = new Database([
            'host' => 'unix_socket',
            'port' => '3306',
            'db' => 'test',
            'driver' => 'mysql',
            'charset' => 'utf8mb4',
        ]);

        $this->assertEquals('mysql:unix_socket=;dbname=test;charset=utf8mb4', $db->getDSN());

        $db = new Database([
            'host' => 'unix_socket',
            'port' => '/var/tmp/mysql.sock',
            'db' => 'test',
            'driver' => 'mysql',
            'charset' => 'utf8mb4',
        ]);

        $this->assertEquals('mysql:unix_socket=/var/tmp/mysql.sock;dbname=test;charset=utf8mb4', $db->getDSN());
    }
}
