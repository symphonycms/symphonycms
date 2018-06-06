<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseStatement
 */
final class DatabaseStatementIntegrationTest extends TestCase
{
    /**
     * @expectedException DatabaseException
     */
    public function testExecuteInvalidSQL()
    {
        Symphony::Database()
            ->statement('INVALID')
            ->execute();
    }

    public function testExecuteValidSQL()
    {
        $stm = Symphony::Database()->statement('SHOW VARIABLES');
        $this->assertInstanceOf('\DatabaseStatement', $stm);
        $result = $stm->execute();
        $this->assertInstanceOf('\DatabaseStatementResult', $result);
        $this->assertTrue($result->success());
        $this->assertInstanceOf('\PDOStatement', $result->statement());
    }
}
