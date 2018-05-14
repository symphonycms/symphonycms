<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseShow
 */
final class DatabaseShowTest extends TestCase
{
    public function testSHOWTABLESLIKE()
    {
        $db = new Database([]);
        $sql = $db->show()
                  ->like('%show%s');
        $this->assertEquals(
            "SHOW TABLES LIKE ?",
            $sql->generateSQL(),
            'SHOW TABLES LIKE clause'
        );
        $values = $sql->getValues();
        $this->assertEquals('%show%s', $values[0], '0 is %show%s');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSHOWTABLESWHERE()
    {
        $db = new Database([]);
        $sql = $db->show()
                  ->where(['x' => 1]);
        $this->assertEquals(
            "SHOW TABLES WHERE `x` = :x",
            $sql->generateSQL(),
            'SHOW TABLES WHERE clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSHOWCOLUMNSLIKE()
    {
        $db = new Database([]);
        $sql = $db->showColumns()
                  ->from('tbl')
                  ->like('%show%s');
        $this->assertEquals(
            "SHOW COLUMNS FROM `tbl` LIKE ?",
            $sql->generateSQL(),
            'SHOW COLUMNS LIKE clause'
        );
        $values = $sql->getValues();
        $this->assertEquals('%show%s', $values[0], '0 is %show%s');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSHOWTABLESWHERELIKEFormatted()
    {
        $db = new Database([]);
        $sql = $db->show()
            ->where(['x' => 1])
            ->like('%show%s');
        $this->assertEquals(
            "SHOW TABLES\n\tLIKE ?\n\tWHERE `x` = :x",
            $sql->generateFormattedSQL(),
            'SHOW TABLES WHERE LIKE formatted'
        );
    }

    public function testSHOWINDEX()
    {
        $db = new Database([]);
        $sql = $db->showIndex()
                ->from('tbl')
                ->where(['x' => 'PRIMARY']);
        $this->assertEquals(
            "SHOW INDEX FROM `tbl` WHERE `x` = :x",
            $sql->generateSQL(),
            'SHOW INDEX clause'
        );
        $values = $sql->getValues();
        $this->assertEquals('PRIMARY', $values['x'], 'x is PRIMARY');
        $this->assertEquals(1, count($values), '1 value');
    }
}
