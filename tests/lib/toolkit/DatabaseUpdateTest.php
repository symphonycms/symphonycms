<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseUpdate
 * @covers DatabaseWhereDefinition
 */
final class DatabaseUpdateTest extends TestCase
{
    public function testUPDATE()
    {
        $db = new Database([]);
        $sql = $db->update('update')
                  ->set([
                        'x' => 1,
                        'y' => 'TEST',
                        'z' => true
                    ]);
        $this->assertEquals(
            "UPDATE `update` SET `x` = :x, `y` = :y, `z` = :z",
            $sql->generateSQL(),
            'UPDATE clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals('TEST', $values['y'], 'y is TEST');
        $this->assertEquals(true, $values['z'], 'z is true');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testUPDATESelfReference()
    {
        $db = new Database([]);
        $sql = $db->update('update')
            ->set([
                'x' => '$x - 1',
            ])
            ->where([
                'y' => '$y + 1',
            ]);
        $this->assertEquals(
            "UPDATE `update` SET `x` = `x` - 1 WHERE `y` = `y` + 1",
            $sql->generateSQL(),
            'UPDATE clause self reference'
        );
        $values = $sql->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testUPDATEWHERE()
    {
        $db = new Database([]);
        $sql = $db->update('update')
                  ->set([
                        'x' => 1,
                        'y' => 'TEST',
                    ])
                  ->where(['a' => 'id']);
        $this->assertEquals(
            "UPDATE `update` SET `x` = :x, `y` = :y WHERE `a` = :a",
            $sql->generateSQL(),
            'UPDATE clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals('TEST', $values['y'], 'y is TEST');
        $this->assertEquals('id', $values['a'], 'a is id');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testUPDATENULLWHERENULL()
    {
        $db = new Database([]);
        $sql = $db->update('update')
                  ->set([
                        'x' => 1,
                        'y' => 'TEST',
                        'z' => null
                    ])
                  ->where(['a' => 'id'])
                  ->where(['b' => ['!=' => null]]);
        $this->assertEquals(
            "UPDATE `update` SET `x` = :x, `y` = :y, `z` = :_null_ WHERE `a` = :a AND `b` IS NOT :_null_",
            $sql->generateSQL(),
            'UPDATE NULL WHERE NULL clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals('TEST', $values['y'], 'y is TEST');
        $this->assertEquals('id', $values['a'], 'a is id');
        $this->assertEquals(null, $values['_null_'], '_null_ is null');
        $this->assertEquals(4, count($values), '4 values');
    }

    public function testUPDATEWHEREFormatted()
    {
        $db = new Database([]);
        $sql = $db->update('update')
            ->set([
                'x' => 1,
                'y' => 'TEST',
            ])
            ->where(['z' => 'id']);
        $this->assertEquals(
            "UPDATE `update`\n\tSET `x` = :x, `y` = :y\n\tWHERE `z` = :z",
            $sql->generateFormattedSQL(),
            'UPDATE clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals('TEST', $values['y'], 'y is TEST');
        $this->assertEquals('id', $values['z'], 'z is id');
        $this->assertEquals(3, count($values), '3 values');
    }
}
