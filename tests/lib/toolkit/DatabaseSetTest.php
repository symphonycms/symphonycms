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
}
