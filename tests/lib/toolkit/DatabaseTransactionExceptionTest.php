<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseTransactionException
 */
final class DatabaseTransactionExceptionTest extends TestCase
{
    public function testCtor()
    {
        $e = new DatabaseTransactionException('MSG');
        $this->assertEquals('MSG', $e->getMessage());
        $this->assertEquals(0, $e->getCode());
    }
}
