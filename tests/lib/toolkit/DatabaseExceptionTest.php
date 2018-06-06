<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseException
 */
final class DatabaseExceptionTest extends TestCase
{
    public function testGetQuery()
    {
        $e = new DatabaseException('', [
            'query' => 'test',
        ]);
        $this->assertEquals('test', $e->getQuery());
    }

    public function testGetDatabaseErrorCode()
    {
        $e = new DatabaseException('', [
            'num' => 1,
        ]);
        $this->assertEquals(1, $e->getDatabaseErrorCode());
    }

    public function testGetDatabaseErrorMessage()
    {
        $e = new DatabaseException('', [
            'msg' => 'test',
        ]);
        $this->assertEquals('test', $e->getDatabaseErrorMessage());
    }
}
