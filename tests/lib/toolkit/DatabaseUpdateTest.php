<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseUpdate
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

    public function testUPDATEWHERE()
    {
        $db = new Database([]);
        $sql = $db->update('update')
                  ->set([
                        'x' => 1,
                        'y' => 'TEST',
                    ])
                  ->where(['z' => 'id']);
        $this->assertEquals(
            "UPDATE `update` SET `x` = :x, `y` = :y WHERE `z` = :z",
            $sql->generateSQL(),
            'UPDATE clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals('TEST', $values['y'], 'y is TEST');
        $this->assertEquals('id', $values['z'], 'z is id');
        $this->assertEquals(3, count($values), '3 values');
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
