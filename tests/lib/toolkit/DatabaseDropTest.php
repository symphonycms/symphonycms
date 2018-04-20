<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseDrop
 */
final class DatabaseDropTest extends TestCase
{
    public function testDROP()
    {
        $db = new Database([]);
        $sql = $db->drop('drop.drop');
        $this->assertEquals(
            "DROP TABLE `drop`.`drop`",
            $sql->generateSQL(),
            'DROP clause'
        );
    }

    public function testDROPIFEXISTS()
    {
        $db = new Database([]);
        $sql = $db->drop('drop.drop')->ifExists();
        $this->assertEquals(
            "DROP TABLE IF EXISTS `drop`.`drop`",
            $sql->generateSQL(),
            'DROP IF EXISTS clause'
        );
    }

    public function testDROP2Tables()
    {
        $db = new Database([]);
        $sql = $db->drop('drop.drop')->table('table2');
        $this->assertEquals(
            "DROP TABLE `drop`.`drop` , `table2`",
            $sql->generateSQL(),
            'DROP clause with 2 tables'
        );
    }
}
