<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseDelete
 */
final class DatabaseDeleteTest extends TestCase
{
    public function testDELETE()
    {
        $db = new Database([]);
        $sql = $db->delete('delete');
        $this->assertEquals(
            "DELETE FROM `delete`",
            $sql->generateSQL(),
            'DELETE FROM clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDELETEWHERE()
    {
        $db = new Database([]);
        $sql = $db->delete('delete')
                  ->where(['x' => 1]);
        $this->assertEquals(
            "DELETE FROM `delete` WHERE `x` = :x",
            $sql->generateSQL(),
            'DELETE WHERE clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testDELETELIMIT()
    {
        $db = new Database([]);
        $sql = $db->delete('delete')->limit(1);
        $this->assertEquals(
            "DELETE FROM `delete` LIMIT 1",
            $sql->generateSQL(),
            'DELETE LIMIT clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testDELETENoWhere()
    {
        $db = new Database([]);
        $sql = $db->delete('delete')->finalize();
    }

    public function testDELETEALL()
    {
        $db = new Database([]);
        $sql = $db->delete('delete')->all()->finalize();
        $this->assertEquals(
            "DELETE FROM `delete`",
            $sql->generateSQL(),
            'DELETE FROM clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
