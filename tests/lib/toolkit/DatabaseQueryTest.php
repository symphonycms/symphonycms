<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseQuery
 */
final class DatabaseQueryTest extends TestCase
{
    public function testSELECT()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => 1]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` = :x",
            $sql->generateSQL(),
            'Simple SQL clause with WHERE filter'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTwithOR()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where([
                        'or' => [
                            ['x' => 1],
                            ['y' => ['<' =>'2']]
                        ]
                    ]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE (`x` = :x OR `y` < :y)",
            $sql->generateSQL(),
            "SQL clause with WHERE OR filter"
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(2, $values['y'], 'y is 2');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testSELECTwithNestedAND()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where([
                        'or' => [
                            'and' => [
                                ['x' => 1],
                                ['y' => ['<' =>'2']],
                            ],
                            'w' => ['>=' => 4],
                        ]
                    ]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE ((`x` = :x AND `y` < :y) OR `w` >= :w)",
            $sql->generateSQL(),
            "SQL clause with NESTED ANDs"
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(2, $values['y'], 'y is 2');
        $this->assertEquals(4, $values['w'], 'w is 4');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testSELECTwithJOIN()
    {
        $db = new Database([]);
        $sql = $db->select(['a'])
                  ->from('tbl_test_table')
                  ->leftJoin('sym.tbl_test1')
                  ->on(['tbl_test_table.id' => '$sym.tbl_test1.other-id'])
                  ->where([
                        'sym.tbl_test1.x' => 1,
                    ]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `a` FROM `test_table` LEFT JOIN `sym`.`test1` ON `test_table`.`id` = `sym`.`test1`.`other-id` WHERE `sym`.`test1`.`x` = :sym.tbl_test1.x",
            $sql->generateSQL(),
            "SQL clause with WHERE LEFT JOIN"
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['sym.tbl_test1.x'], 'sym.tbl_test1.x is 1');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTwithORDERBY()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => 1])
                  ->orderBy('tbl1.tbl_test');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` = :x ORDER BY `tbl1`.`test` ASC",
            $sql->generateSQL(),
            'SQL clause with ORDER BY'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTwithORDERBYRANDOM()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => 1])
                  ->orderBy('tbl1.tbl_test', 'RANDOM()');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` = :x ORDER BY `tbl1`.`test` RANDOM()",
            $sql->generateSQL(),
            'SQL clause with ORDER BY'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTwithORDERBYMULTIPLE()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->orderBy([0 => 'tbl1.tbl_test', 'tbl2' => 'ASC'], 'DESC');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` ORDER BY `tbl1`.`test` DESC, `tbl2` ASC",
            $sql->generateSQL(),
            'SQL clause with multiple ORDER BY'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTBETWEEN()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['between' => ['x' => [1, 10]]]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE (`x` BETWEEN ? AND ?)",
            $sql->generateSQL(),
            'BETWEEN clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values[0], '0 is 1');
        $this->assertEquals(10, $values[1], '1 is 10');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testSELECTIN()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['in' => ['x' => [1, 2, 5]]]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` IN (?, ?, ?)",
            $sql->generateSQL(),
            'IN clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values[0], '0 is 1');
        $this->assertEquals(2, $values[1], '1 is 2');
        $this->assertEquals(5, $values[2], '2 is 5');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testSELECTAS()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->alias('tbl_');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` AS `tbl_`",
            $sql->generateSQL(),
            'AS clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTJOINAS()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->alias('tbl_')
                  ->innerJoin('innertable', 'inner');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` AS `tbl_` INNER JOIN `innertable` AS `inner`",
            $sql->generateSQL(),
            'AS clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTGROUPBY()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('group')
                  ->groupBy(['a', 'b', 'c'])
                  ->having(['a' => 1]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `group` GROUP BY `a`, `b`, `c` HAVING `a` = :a",
            $sql->generateSQL(),
            'GROUP BY HAVING clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['a'], 'a is 1');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTDISTINCT()
    {
        $db = new Database([]);
        $sql = $db->selectDistinct()
                  ->from('group');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE DISTINCT * FROM `group`",
            $sql->generateSQL(),
            'DISTINCT clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTLIMITOFFSET()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->limit(1)
                  ->offset(10);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` LIMIT 1 OFFSET 10",
            $sql->generateSQL(),
            'LIMIT OFFSET clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    /**
     * @expectedException DatabaseException
     */
    public function testSELECTLIMITOFFSETWRONG()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->limit('wrong')
                  ->offset(['invalide']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` LIMIT -1 OFFSET -1",
            $sql->generateSQL(),
            'LIMIT OFFSET clause WRONG data'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTCOUNT()
    {
        $db = new Database([]);
        $sql = $db->selectCount()
                  ->from('tbl_test_table');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(*) FROM `test_table`",
            $sql->generateSQL(),
            'SELECT COUNT(*) clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTCOUNTCOL()
    {
        $db = new Database([]);
        $sql = $db->selectCount('x')
                  ->from('tbl_test_table');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(`x`) FROM `test_table`",
            $sql->generateSQL(),
            'SELECT COUNT(...) clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTHAVINGCOUNT()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->groupBy('x')
                  ->having(['x' => ['<' => 'COUNT(y)']]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` GROUP BY `x` HAVING `x` < COUNT(`y`)",
            $sql->generateSQL(),
            'SELECT COUNT(*) clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
