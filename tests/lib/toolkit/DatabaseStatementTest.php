<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseStatement
 */
final class DatabaseStatementTest extends TestCase
{
    public function testAsTickedString()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $this->assertEquals('`x`', $sql->asTickedString('x'));
        $this->assertEquals('`x`', $sql->asTickedString('x`'));
        $this->assertEquals('`x`', $sql->asTickedString('`x`'));
        $this->assertEquals('`xtest`', $sql->asTickedString('x`test'));
        $this->assertEquals('`x`.`y`', $sql->asTickedString('x.y'));
        $this->assertEquals('`x`.`y`', $sql->asTickedString('x.`y`'));
        $this->assertEquals('`x`.`y`.`z`', $sql->asTickedString('x.y.z'));
        $this->assertEquals('`x-test`', $sql->asTickedString('x-test'));
        $this->assertEquals('`x_test`', $sql->asTickedString('x_test'));
    }

    public function testAsTickedStringWithOperator()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $this->assertEquals('`x` - 1', $sql->asTickedString('x - 1'));
    }

    public function testAsTickedList()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $this->assertEquals('`x`', $sql->asTickedList(['x']));
        $this->assertEquals('`x`, `y`', $sql->asTickedList(['x', 'y']));
    }

    /**
     * @expectedException DatabaseSatementException
     */
    public function testSQLInjection()
    {
        $db = new Database([]);
        $sql = $db->statement("INSERT INTO `tbl` VALUES('test');--,'test')");
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql);
    }

    public function testSQLUnsafeInjection()
    {
        $db = new Database([]);
        $injectedSql = "INSERT INTO `tbl` VALUES('test')";
        $sql = $db->statement($injectedSql);
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql, false);
        $this->assertEquals($injectedSql, $sql);
    }
}
