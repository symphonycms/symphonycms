<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseDelete
 * @covers DatabaseWhereDefinition
 * @covers DatabaseSubQueryDefinition
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
    public function testDELETEDOUBLELIMIT()
    {
        $db = new Database([]);
        $sql = $db->delete('delete')->limit(1)->limit(2);
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testDELETEINVALIDLIMIT()
    {
        $db = new Database([]);
        $sql = $db->delete('delete')->limit('');
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

    public function testDELETEFormattedSQL()
    {
        $db = new Database([]);
        $sql = $db->delete('delete')
            ->where(['x' => 1])
            ->limit(1);
        $this->assertEquals(
            "DELETE FROM `delete`\n\tWHERE `x` = :x\n\tLIMIT 1",
            $sql->generateFormattedSQL(),
            'DELETE WHERE formatted'
        );
    }

    public function testDELETEWithSubQuery()
    {
        $db = new Database([]);
        $sql = $db->delete('tbl_test_table');
        $sub = $sql->select(['y'])
            ->from('sub')
            ->where(['y' => 2]);
        $sql->where(['x' => $sub]);
        $this->assertEquals(
            "DELETE FROM `test_table` WHERE `x` = (SELECT `y` FROM `sub` WHERE `y` = :i1_y)",
            $sql->generateSQL(),
            'DELETE SQL clause with WHERE sub-query'
        );
        $values = $sql->getValues();
        $this->assertEquals(2, $values['i1_y'], 'i1_y is 2');
        $this->assertEquals(1, count($values), '1 value');
    }
}
