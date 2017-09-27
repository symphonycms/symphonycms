<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseOptimize
 */
final class DatabaseOptimizeTest extends TestCase
{
    public function testOPTIMIZE()
    {
        $db = new Database([]);
        $sql = $db->optimize('op.timize');
        $this->assertEquals(
            "OPTIMIZE TABLE `op`.`timize`",
            $sql->generateSQL(),
            'OPTIMIZE clause'
        );
    }
}
