<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseTruncate
 */
final class DatabaseTruncateTest extends TestCase
{
    public function testTRUNCATE()
    {
        $db = new Database([]);
        $sql = $db->truncate('trun.cate');
        $this->assertEquals(
            "TRUNCATE TABLE `trun`.`cate`",
            $sql->generateSQL(),
            'TRUNCATE clause'
        );
    }
}
