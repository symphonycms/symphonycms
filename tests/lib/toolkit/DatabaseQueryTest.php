<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseQuery
 * @covers DatabaseQueryJoin
 * @covers DatabaseWhereDefinition
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
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` = :x",
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

    public function testSELECTDefaultProjection()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE * FROM `test_table`",
            $sql->generateSQL(),
            'Simple SQL clause with default projection'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSELECTDOUBLEFROM()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->from('other');
    }

    public function testSELECTWithPlaceholders()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->usePlaceholders()
                  ->where(['x' => 1]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` = ?",
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
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` = `y` - 1",
            $sql->generateSQL(),
            'Simple SQL clause with WHERE filter with minus operator'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTAliasedPlusOperator()
    {
        $db = new Database([]);
        $sql = $db->select(['y + 1' => 'result'])
            ->from('tbl_test_table');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE (`y` + 1) AS `result` FROM `test_table`",
            $sql->generateSQL(),
            'SQL clause with aliased operator in projection'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSELECTDOUBLEALIAS()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->alias('t')
            ->alias('f');
    }

    public function testSELECTSelfReference()
    {
        $db = new Database([]);
        $sql = $db->select(['`z` + 1' => 't'])
            ->from('tbl_test_table')
            ->where(['x' => ['<=' => '$x * 8']]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE (`z` + 1) AS `t` FROM `test_table` WHERE `x` <= `x` * 8",
            $sql->generateSQL(),
            'SQL clause with self reference WHERE filter'
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
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` = :x AND `y` = :y",
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
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` = :x AND `x` = :x2",
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
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE (`x` = :x OR `y` < :y)",
            $sql->generateSQL(),
            "SQL clause with WHERE OR filter"
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(2, $values['y'], 'y is 2');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testSELECTwithORSameColumun()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->where([
                'or' => [
                    ['x' => 1],
                    ['x' => ['<' => '2']],
                ]
            ]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE (`x` = :x OR `x` < :x2)",
            $sql->generateSQL(),
            "SQL clause with WHERE OR filter"
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(2, $values['x2'], 'x2 is 2');
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
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE ((`x` = :x AND `y` < :y) OR `w` >= :w)",
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
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` IS :_null_",
            $sql->generateSQL(),
            "SQL clause with WHERE IS NULL"
        );
        $values = $sql->getValues();
        $this->assertEquals(null, $values['_null_'], '_null_ is NULL');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTwithWHEREISNOTNULL()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->where([
                'x' => ['!=' => null]
            ]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` IS NOT :_null_",
            $sql->generateSQL(),
            "SQL clause with WHERE IS NOT NULL"
        );
        $values = $sql->getValues();
        $this->assertEquals(null, $values['_null_'], '_null_ is NULL');
        $this->assertEquals(1, count($values), '1 value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSELECTwithWHEREEMPTY()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->where([]);
    }

    public function testSELECTwithJOIN()
    {
        $db = new Database([]);
        $sql = $db->select(['a'])
            ->from('tbl_test_table')
            ->join('sym.tbl_test1')
            ->on(['tbl_test_table.id' => '$sym.tbl_test1.other-id']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `a` FROM `test_table` JOIN `sym`.`test1` ON `test_table`.`id` = `sym`.`test1`.`other-id`",
            $sql->generateSQL(),
            "SQL clause with JOIN"
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTwithLEFTJOIN()
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
                  ->outerJoin('sym.tbl_test1')
                  ->on(['tbl_test_table.id' => '$sym.tbl_test1.other-id'])
                  ->rightJoin('sym.tbl_test2')
                  ->on(['tbl_test_table.x' => ['>' => '4']]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `a` FROM `test_table` OUTER JOIN `sym`.`test1` ON `test_table`.`id` = `sym`.`test1`.`other-id` RIGHT JOIN `sym`.`test2` ON `test_table`.`x` > :tbl_test_table_x",
            $sql->generateSQL(),
            "SQL clause with WHERE LEFT JOIN"
        );
        $values = $sql->getValues();
        $this->assertEquals(4, $values['tbl_test_table_x'], 'tbl_test_table_x is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSELECTDOUBLEALIASONJOIN()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->alias('t')
            ->outerJoin('sym.tbl_test1')
            ->alias('f')
            ->alias('t');
    }

    public function testSELECTwithORDERBY()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => 1])
                  ->orderBy('tbl1.tbl_test');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` = :x ORDER BY `tbl1`.`test` ASC",
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
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` = :x ORDER BY RAND() , RAND()",
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
            "SELECT SQL_NO_CACHE FROM `test_table` ORDER BY `tbl1`.`test` DESC, `tbl2` ASC",
            $sql->generateSQL(),
            'SQL clause with multiple ORDER BY'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTBETWEENWithPlaceholders()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->usePlaceholders()
                  ->from('tbl_test_table')
                  ->where(['x' => ['between' => [1, 10]]]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE (`x` BETWEEN ? AND ?)",
            $sql->generateSQL(),
            'BETWEEN clause with placeholders'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values[0], '0 is 1');
        $this->assertEquals(10, $values[1], '1 is 10');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testSELECTBETWEEN()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => ['between' => [1, 10]]]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE (`x` BETWEEN :xl AND :xu)",
            $sql->generateSQL(),
            'BETWEEN clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['xl'], '0 is 1');
        $this->assertEquals(10, $values['xu'], '1 is 10');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testSELECTLIKE()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => ['like' => '%test%']]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` LIKE :x",
            $sql->generateSQL(),
            'LIKE clause'
        );
        $values = $sql->getValues();
        $this->assertEquals('%test%', $values['x'], 'x is %test%');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTNOTLIKE()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->where(['x' => ['not like' => '%test%']]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` NOT LIKE :x",
            $sql->generateSQL(),
            'NOT LIKE clause'
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
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` REGEXP :x",
            $sql->generateSQL(),
            'REGEXP clause'
        );
        $values = $sql->getValues();
        $this->assertEquals('[[:<:]]handle[[:>:]]', $values['x'], 'x is [[:<:]]handle[[:>:]]');
        $this->assertEquals(1, count($values), '1 value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSELECTInvalidOperation()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => ['operation' => 'value']]);
    }

    public function testSELECTINWithPlaceholders()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->usePlaceholders()
                  ->from('tbl_test_table')
                  ->where(['x' => ['in' => [1, 2, 5]]]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` IN (?, ?, ?)",
            $sql->generateSQL(),
            'IN clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values[0], '0 is 1');
        $this->assertEquals(2, $values[1], '1 is 2');
        $this->assertEquals(5, $values[2], '2 is 5');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testSELECTIN()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => ['in' => [1, 2, 5]]]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` IN (:x, :x2, :x3)",
            $sql->generateSQL(),
            'IN clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(2, $values['x2'], 'x2 is 2');
        $this->assertEquals(5, $values['x3'], 'x3 is 5');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testSELECTNOTINWithPlaceholders()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->usePlaceholders()
                  ->from('tbl_test_table')
                  ->where(['x' => ['not in' => [1, 2, 5]]]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` NOT IN (?, ?, ?)",
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
                  ->where(['x' => ['not in' => [1, 2, 5]]]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` NOT IN (:x, :x2, :x3)",
            $sql->generateSQL(),
            'IN clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(2, $values['x2'], 'x2 is 2');
        $this->assertEquals(5, $values['x3'], 'x3 is 5');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testSELECTMATCHBOOLEANMODE()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->where(['x' => ['boolean' => 'value']]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE MATCH (`x`) AGAINST (:x IN BOOLEAN MODE)",
            $sql->generateSQL(),
            'MATCH clause'
        );
        $values = $sql->getValues();
        $this->assertEquals('value', $values['x'], 'x is value');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTWhereStartDate()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->where(['x' => ['date' => ['start' => '2018-03-28']]]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` >= :x",
            $sql->generateSQL(),
            'day clause'
        );
        $values = $sql->getValues();
        $this->assertEquals('2018-03-28', $values['x'], 'x is 2018-03-28');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSELECTWhereEndDateWithTimeInclusive()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->where(['x' => ['date' => [
                'end' => '2018-03-28 11:11:11',
                'strict' => false,
            ]]]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` <= :x",
            $sql->generateSQL(),
            'day clause with time'
        );
        $values = $sql->getValues();
        $this->assertEquals('2018-03-28 11:11:11', $values['x'], 'x is 2018-03-28 11:11:11');
        $this->assertEquals(1, count($values), '1 value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSELECTEmptyIN()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => ['in' => []]]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` IN ()",
            $sql->generateSQL(),
            'Empty IN clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSELECTINTooManyArrays()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->where(['x' => ['in' => [[1]]]]);
    }

    public function testSELECTTableAS()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->alias('tbl_');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` AS `tbl_`",
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
            "SELECT SQL_NO_CACHE FROM `test_table` AS `tbl_` INNER JOIN `innertable` AS `inner` ON `z` = `z`",
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
            "SELECT SQL_NO_CACHE FROM `group` GROUP BY `a`, `b`, `c` HAVING `a` = :a",
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
            "SELECT SQL_NO_CACHE DISTINCT FROM `group`",
            $sql->generateSQL(),
            'DISTINCT clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSELECTDOUBLEDISTINCT()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->distinct()
            ->distinct();
    }

    public function testSELECTLIMITOFFSET()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->limit(1)
                  ->offset(10);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` LIMIT 1 OFFSET 10",
            $sql->generateSQL(),
            'LIMIT OFFSET clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSELECTDOUBLELIMIT()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->limit(1)
            ->limit(10);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSELECTLIMITWRONG()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->from('tbl_test_table')
                  ->limit('wrong');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSELECTOFFSETWRONG()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->offset(['invalid']);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSELECTDOUBLEOFFSET()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->offset(1)
            ->offset(10);
    }

    public function testSELECTPaginate()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->paginate(2, 10);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `test_table` LIMIT 10 OFFSET 10",
            $sql->generateSQL(),
            'Paginate clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTCOUNT()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->count()
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
        $sql = $db->select()
                  ->count('x')
                  ->from('tbl_test_table');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(`x`) FROM `test_table`",
            $sql->generateSQL(),
            'SELECT COUNT(...) clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTCOUNTSTARCOL()
    {
        $db = new Database([]);
        $sql = $db->select()
                  ->count('t.*')
                  ->from('tbl_test_table');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(*) FROM `test_table`",
            $sql->generateSQL(),
            'SELECT COUNT(t.*) clause'
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
            "SELECT SQL_NO_CACHE FROM `test_table` GROUP BY `x` HAVING `x` < COUNT(`y`)",
            $sql->generateSQL(),
            'SELECT COUNT(*) clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTFUNCTIONNOPARAMS()
    {
        $db = new Database([]);
        $sql = $db->select(['X()']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE X()",
            $sql->generateSQL(),
            'SELECT X() (function projection)'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTFUNCTIONNOPARAMSALIASED()
    {
        $db = new Database([]);
        $sql = $db->select(['X()' => 'x']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE X() AS `x`",
            $sql->generateSQL(),
            'SELECT X() as `x` (function projection aliased)'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTFUNCTIONWITHPARAMSALIASED()
    {
        $db = new Database([]);
        $sql = $db->select([])->projection([
            'X(a,b,c)' => 'x'
        ]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE X(`a`, `b`, `c`) AS `x`",
            $sql->generateSQL(),
            'SELECT X(a,b,c) as `x` (function projection aliased)'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTNESTEDFUNCTIONALIASED()
    {
        $db = new Database([]);
        $sql = $db->select([])->projection([
            'X(Y(a), Z(b,c), d)' => 'x'
        ]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE X(Y(`a`), Z(`b`, `c`), `d`) AS `x`",
            $sql->generateSQL(),
            'SELECT X(Y(a), Z(b,c), d) as `x` (nested function projection aliased)'
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
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` = (SELECT `y` FROM `sub` WHERE `y` = :i1_y)",
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
            "SELECT SQL_NO_CACHE FROM `test_table` WHERE `x` = (SELECT `y` FROM `sub` WHERE `y` = :i1_y) AND `y` IN (SELECT `y` FROM `sub` WHERE `y` = :i2_y)",
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
            "SELECT SQL_NO_CACHE (SELECT `y` FROM `sub` WHERE `y` = `x`) AS `inner`, `x` FROM `test_table`",
            $sql->generateSQL(),
            'Simple SQL with sub-query in the projection'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSELECTFormattedSQL()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table')
            ->where(['x' => 1])
            ->where(['y' => 1]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE\n\tFROM `test_table`\n\tWHERE `x` = :x\n\tAND `y` = :y",
            $sql->generateFormattedSQL(),
            'Formatted SQL query test'
        );
    }

    public function testSELECTWithSubQueryFormatted()
    {
        $db = new Database([]);
        $sql = $db->select()
            ->from('tbl_test_table');
        $sub = $sql->select(['y'])
            ->from('sub')
            ->where(['y' => 2]);
        $sql->where(['x' => $sub]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE\n\tFROM `test_table`\n\tWHERE `x` = (SELECT `y` FROM `sub` WHERE `y` = :i1_y)",
            $sql->generateFormattedSQL(),
            'Formatted SQL sub-query test'
        );
    }

    public function testCountProjection()
    {
        $db = new Database([]);
        $original = $db->select()
            ->from('tbl_test_table')
            ->where([
                'or' => [
                    ['x' => 1],
                    ['x' => ['<' => '2']],
                ]
            ])
            ->where(['z' => 'tata'])
            ->unsafe();
        $sql = $original->countProjection();
        $this->assertNotEquals($original, $sql);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(*) FROM `test_table` WHERE (`x` = :x OR `x` < :x2) AND `z` = :z",
            $sql->generateSQL(),
            "SQL count clause clause from projection"
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals(2, $values['x2'], 'x2 is 2');
        $this->assertEquals('tata', $values['z'], 'z is tata');
        $this->assertEquals(3, count($values), '3 values');
        $this->assertFalse($sql->isSafe());
    }
}
