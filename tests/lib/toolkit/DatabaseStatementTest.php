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
        $this->assertEquals('`x` + 1', $sql->asTickedString('x + 1'));
        $this->assertEquals('`x` * 1', $sql->asTickedString('x * 1'));
        $this->assertEquals('`x` / 1', $sql->asTickedString('x / 1'));
        $this->assertEquals('(`x` + 10) AS `t`', $sql->asTickedString('x + 10', 't'));
    }

    public function testAsTickedList()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $this->assertEquals('`x`', $sql->asTickedList(['x']));
        $this->assertEquals('`x`, `y`', $sql->asTickedList(['x', 'y']));
    }

    public function testSplitFunctionArguments()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $args = $sql->splitFunctionArguments('X(a,  b,c),T(Z(R(a))), r,   GHZAS(a,G(b ),c),');
        $this->assertEquals('X(a,b,c)', $args[0]);
        $this->assertEquals('T(Z(R(a)))', $args[1]);
        $this->assertEquals('r', $args[2]);
        $this->assertEquals('GHZAS(a,G(b),c)', $args[3]);
        $this->assertEquals(4, count($args));
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSplitFunctionArgumentsImbalanced()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $args = $sql->splitFunctionArguments('X(a, ( b,c)');
        $this->assertEquals(0, count($args));
    }

    /**
     * @expectedException DatabaseStatementException
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
