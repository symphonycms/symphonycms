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

    public function testSELECTProjection()
    {
        $db = new Database([]);
        $sql = $db->select(['x'])
                  ->projection(['y'])
                  ->from('tbl_test_table');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `x` , `y` FROM `test_table`",
            $sql->generateSQL(),
            'Simple SQL clause with multiple projection'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTWithPlaceholders()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->usePlaceholders()
                  ->where(['x' => 1]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` = ?",
            $sql->generateSQL(),
            'Simple SQL clause with WHERE filter'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values[0], '0 is 1');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTColumnAS()
    {
        $db = new Database([]);
        $sql = $db->select(['x' => 'y', 'z'])
                  ->from('tbl_test_table')
                  ->where(['x' => 1]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `x` AS `y`, `z` FROM `test_table` WHERE `x` = :x",
            $sql->generateSQL(),
            'Simple SQL clause with WHERE filter'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTMinusOperator()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => '$y - 1']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` = `y` - 1",
            $sql->generateSQL(),
            'Simple SQL clause with WHERE filter with minus operator'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTMULTIPLEWHERE()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => 1])
                  ->where(['y' => 1]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` = :x AND `y` = :y",
            $sql->generateSQL(),
            'Simple SQL clause with WHERE filter'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(1, $values['y'], 'y is 1');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testSELECTMULTIPLEWHERESameFilterdCol()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => 1])
                  ->where(['x' => 2]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` = :x AND `x` = :x2",
            $sql->generateSQL(),
            'Simple SQL clause with WHERE filter on the same column'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(2, $values['x2'], 'x2 is 2');
        $this->assertEquals(2, count($values), '2 values');
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

    public function testSELECTwithWHEREISNULL()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where([
                        'x' => null
                    ]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` = :x",
            $sql->generateSQL(),
            "SQL clause with WHERE IS NULL"
        );
        $values = $sql->getValues();
        $this->assertEquals(null, $values['x'], 'x is NULL');
        $this->assertEquals(1, count($values), '1 value');
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
            "SELECT SQL_NO_CACHE `a` FROM `test_table` LEFT JOIN `sym`.`test1` ON `test_table`.`id` = `sym`.`test1`.`other-id` WHERE `sym`.`test1`.`x` = :sym_tbl_test1_x",
            $sql->generateSQL(),
            "SQL clause with WHERE LEFT JOIN"
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['sym_tbl_test1_x'], 'sym_tbl_test1_x is 1');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTwithMULTIPLEJOINS()
    {
        $db = new Database([]);
        $sql = $db->select(['a'])
                  ->from('tbl_test_table')
                  ->leftJoin('sym.tbl_test1')
                  ->on(['tbl_test_table.id' => '$sym.tbl_test1.other-id'])
                  ->rightJoin('sym.tbl_test2')
                  ->on(['tbl_test_table.x' => ['>' => '4']]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `a` FROM `test_table` LEFT JOIN `sym`.`test1` ON `test_table`.`id` = `sym`.`test1`.`other-id` RIGHT JOIN `sym`.`test2` ON `test_table`.`x` > :tbl_test_table_x",
            $sql->generateSQL(),
            "SQL clause with WHERE LEFT JOIN"
        );
        $values = $sql->getValues();
        $this->assertEquals(4, $values['tbl_test_table_x'], 'tbl_test_table_x is 4');
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
                  ->orderBy('tbl1.tbl_test', 'RANDOM()')
                  ->orderBy('tbl1.x', 'RAND()');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` = :x ORDER BY `tbl1`.`test` RANDOM() , `tbl1`.`x` RAND()",
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
                  ->where(['x' => ['between' => [1, 10]]]);
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

    public function testSELECTLIKE()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => ['like' => '%test%']]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` LIKE :x",
            $sql->generateSQL(),
            'LIKE clause'
        );
        $values = $sql->getValues();
        $this->assertEquals('%test%', $values['x'], 'x is %test%');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTREGEXP()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => ['regexp' => '[[:<:]]handle[[:>:]]']]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` REGEXP :x",
            $sql->generateSQL(),
            'REGEXP clause'
        );
        $values = $sql->getValues();
        $this->assertEquals('[[:<:]]handle[[:>:]]', $values['x'], 'x is [[:<:]]handle[[:>:]]');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTIN()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => ['in' => [1, 2, 5]]]);
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

    public function testSELECTNOTIN()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => ['notin' => [1, 2, 5]]]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` NOT IN (?, ?, ?)",
            $sql->generateSQL(),
            'IN clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values[0], '0 is 1');
        $this->assertEquals(2, $values[1], '1 is 2');
        $this->assertEquals(5, $values[2], '2 is 5');
        $this->assertEquals(3, count($values), '3 values');
    }

    /**
     * @expectedException DatabaseSatementException
     */
    public function testSELECTEmptyIN()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => ['in' => []]]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` IN ()",
            $sql->generateSQL(),
            'Empty IN clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTTableAS()
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
                  ->innerJoin('innertable', 'inner')
                  ->on(['z' => '$z']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` AS `tbl_` INNER JOIN `innertable` AS `inner` ON `z` = `z`",
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
        $sql = $db->select()
                  ->distinct()
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
     * @expectedException DatabaseSatementException
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

    public function testSELECTWithSubQuery()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table');
        $sub = $sql->select(['y'])
                  ->from('sub')
                  ->where(['y' => 2]);
        $sql->where(['x' => $sub]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` = (SELECT SQL_NO_CACHE `y` FROM `sub` WHERE `y` = :i1_y)",
            $sql->generateSQL(),
            'Simple SQL clause with WHERE sub-query'
        );
        $values = $sql->getValues();
        $this->assertEquals(2, $values['i1_y'], 'i1_y is 2');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTWithSubQueries()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table');
        $sub1 = $sql->select(['y'])
                  ->from('sub')
                  ->where(['y' => 2]);
        $sub2 = $sql->select(['y'])
                  ->from('sub')
                  ->where(['y' => 4]);
        $sql->where(['x' => $sub1]);
        $sql->where(['y' => ['in' => $sub2]]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table` WHERE `x` = (SELECT SQL_NO_CACHE `y` FROM `sub` WHERE `y` = :i1_y) AND `y` IN (SELECT SQL_NO_CACHE `y` FROM `sub` WHERE `y` = :i2_y)",
            $sql->generateSQL(),
            'Simple SQL clause with WHERE two sub-queries'
        );
        $values = $sql->getValues();
        $this->assertEquals(2, $values['i1_y'], 'i1_y is 2');
        $this->assertEquals(4, $values['i2_y'], 'i2_y is 4');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testSELECTWithSubQueryInProjection()
    {
        $db = new Database([]);
        $sql = $db->select([])
                  ->from('tbl_test_table');
        $sub = $sql->select(['y'])
                  ->from('sub')
                  ->where(['y' => '$x']);
        $sql->projection(['inner' => $sub, 'x']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE (SELECT SQL_NO_CACHE `y` FROM `sub` WHERE `y` = `x`) AS `inner`, `x` FROM `test_table`",
            $sql->generateSQL(),
            'Simple SQL with sub-query in the projection'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
