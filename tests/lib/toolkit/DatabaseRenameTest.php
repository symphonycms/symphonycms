<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseRename
 */
final class DatabaseRenameTest extends TestCase
{
    public function testRENAME()
    {
        $db = new Database([]);
        $sql = $db->rename('x')->to('y');
        $this->assertEquals(
            "RENAME TABLE `x` TO `y`",
            $sql->generateSQL(),
            'RENAME clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testRENAMEDOUBLETO()
    {
        $db = new Database([]);
        $sql = $db->rename('x')->to('y')->to('z');
    }
}
