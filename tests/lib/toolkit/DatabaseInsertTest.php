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
