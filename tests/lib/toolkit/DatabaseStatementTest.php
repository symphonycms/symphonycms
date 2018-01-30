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
    }

    public function testAsTickedList()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $this->assertEquals('`x`', $sql->asTickedList(['x']));
        $this->assertEquals('`x`, `y`', $sql->asTickedList(['x', 'y']));
    }
}
