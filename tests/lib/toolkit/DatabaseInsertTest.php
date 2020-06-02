<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseInsert
 */
final class DatabaseInsertTest extends TestCase
{
    public function testINSERT()
    {
        $db = new Database([]);
        $sql = $db->insert('tbl_insert')
                  ->values([
                        'x' => 1,
                        'y' => 'TEST',
                        'z' => true
                    ]);
        $this->assertEquals(
            "INSERT INTO `insert` (`x`, `y`, `z`) VALUES (:x, :y, :z)",
            $sql->generateSQL(),
            'INSERT INTO clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals('TEST', $values['y'], 'y is TEST');
        $this->assertEquals(true, $values['z'], 'z is true');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testINSERTUPDATE()
    {
        $db = new Database([]);
        $sql = $db->insert('tbl_insert')
                  ->values([
                        'x' => 1,
                        'y' => 'TEST',
                        'z' => true
                    ])
                  ->updateOnDuplicateKey();
        $this->assertEquals(
            "INSERT INTO `insert` (`x`, `y`, `z`) VALUES (:x, :y, :z) ON DUPLICATE KEY UPDATE `x` = VALUES(`x`), `y` = VALUES(`y`), `z` = VALUES(`z`)",
            $sql->generateSQL(),
            'INSERT ... UPDATE ON DUPLICATE KEY clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals('TEST', $values['y'], 'y is TEST');
        $this->assertEquals(true, $values['z'], 'z is true');
        $this->assertEquals(3, count($values), '6 values');
    }

    public function testINSERTUPDATECUSTOM()
    {
        $db = new Database([]);
        $sql = $db->insert('tbl_insert')
        ->values([
            'x' => 1,
            'y' => 'TEST',
        ])
        ->updateOnDuplicateKey(['z' => 1]);
        $this->assertEquals(
            "INSERT INTO `insert` (`x`, `y`) VALUES (:x, :y) ON DUPLICATE KEY UPDATE `z` = VALUES(`z`)",
            $sql->generateSQL(),
            'INSERT ... UPDATE ON DUPLICATE KEY custom clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals('TEST', $values['y'], 'y is TEST');
        $this->assertEquals(2, count($values), '6 values');
    }

    public function testINSERTExtended()
    {
        $db = new Database([]);
        $sql = $db->insert('tbl_insert')
        ->extended([[
            'x' => 1,
            'y' => 'TEST',
            'z' => true
        ], [
            'x' => 2,
            'y' => 'TEST2',
            'z' => false
        ]]);
        $this->assertEquals(
            "INSERT INTO `insert` (`x`, `y`, `z`) VALUES (:x, :y, :z), (:x2, :y2, :z2)",
            $sql->generateSQL(),
            'INSERT INTO extended clause'
        );
        $values = $sql->getValues();
        $this->assertEquals(1, $values['x'], 'x is 1');
        $this->assertEquals('TEST', $values['y'], 'y is TEST');
        $this->assertEquals(true, $values['z'], 'z is true');
        $this->assertEquals(2, $values['x2'], 'x2 is 2');
        $this->assertEquals('TEST2', $values['y2'], 'y2 is TEST2');
        $this->assertEquals(false, $values['z2'], 'z2 is false');
        $this->assertEquals(6, count($values), '6 values');
    }

    public function testINSERTUPDATEFormatted()
    {
        $db = new Database([]);
        $sql = $db->insert('tbl_insert')
            ->values([
                'x' => 1,
                'y' => 'TEST',
                'z' => true
            ])
            ->updateOnDuplicateKey();
        $this->assertEquals(
            "INSERT INTO `insert` (`x`, `y`, `z`)\n\tVALUES (:x, :y, :z)\n\tON DUPLICATE KEY UPDATE `x` = VALUES(`x`), `y` = VALUES(`y`), `z` = VALUES(`z`)",
            $sql->generateFormattedSQL(),
            'INSERT ... UPDATE ON DUPLICATE KEY Formatted'
        );
    }

    public function testINSERTWithLeadingZero()
    {
        $db = new Database([]);
        $sql = $db->insert('tbl_insert')
            ->values([
                'x' => '0004543',
            ]);
        $this->assertEquals(
            "INSERT INTO `insert` (`x`) VALUES (:x)",
            $sql->generateSQL(),
            'INSERT INTO clause with leading zero'
        );
        $values = $sql->getValues();
        $this->assertEquals('0004543', $values['x'], 'x is 0004543');
        $this->assertTrue(is_string($values['x']));
        $this->assertEquals(1, count($values), '1 value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testINSERTDOUBLEVALUES()
    {
        $db = new Database([]);
        $sql = $db->insert('tbl_insert')
            ->values([
                'x' => 1,
            ])
            ->values([]);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testINSERTDOUBLEDUPLICATE()
    {
        $db = new Database([]);
        $sql = $db->insert('tbl_insert')
            ->updateOnDuplicateKey()
            ->updateOnDuplicateKey();
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testDUPLICATEBEFOREINSERT()
    {
        $db = new Database([]);
        $sql = $db->insert('tbl_insert')
            ->updateOnDuplicateKey();
    }
}
