<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseSet
 */
final class DatabaseSetTest extends TestCase
{
    public function testSET()
    {
        $db = new Database([]);
        $sql = $db->set('x')->value('value');
        $this->assertEquals(
            "SET x = :value",
            $sql->generateSQL(),
            'SET clause'
        );
        $values = $sql->getValues();
        $this->assertEquals('value', $values['value'], 'value is value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSETDOUBLEVALUE()
    {
        $db = new Database([]);
        $db->set('t')
            ->value('v')
            ->value('other');
    }

    public function testSETWithInteger()
    {
        $db = new Database([]);
        $sql = $db->set('x')->value(1);
        $this->assertEquals(
            "SET x = :value",
            $sql->generateSQL(),
            'SET clause with integer'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['value'], 'value is 1');
    }
}
