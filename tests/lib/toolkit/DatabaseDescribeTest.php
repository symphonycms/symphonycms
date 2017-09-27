<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseDescribe
 */
final class DatabaseDescribeTest extends TestCase
{
    public function testDESC()
    {
        $db = new Database([]);
        $sql = $db->describe('desc')->field('x');
        $this->assertEquals(
            "DESC `desc` `x`",
            $sql->generateSQL(),
            'DESC clause'
        );
    }

    public function testDESCFields()
    {
        $db = new Database([]);
        $sql = $db->describe('desc')->fields(['x', 'y']);
        $this->assertEquals(
            "DESC `desc` `x`, `y`",
            $sql->generateSQL(),
            'DESC clause'
        );
    }
}
