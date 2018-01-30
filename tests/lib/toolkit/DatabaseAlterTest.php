<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseAlter
 */
final class DatabaseAlterTest extends TestCase
{
    public function testALTERADDCOL()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->add([
                        'x' => 'varchar(100)'
                    ]);
        $this->assertEquals(
            "ALTER TABLE `alter` ADD COLUMN `x` varchar(100) NOT NULL",
            $sql->generateSQL(),
            'ALTER ADD COLUMN clause'
        );
    }

    public function testALTERADDCOLS()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->add([
                        'x' => [
                            'type' => 'varchar(100)',
                            'default' => 'TATA',
                        ],
                        'y' => [
                            'type' => 'datetime',
                            'default' => '2012-01-01 12:12:12'
                        ],
                    ]);
        $this->assertEquals(
            "ALTER TABLE `alter` ADD COLUMN `x` varchar(100) NOT NULL DEFAULT 'TATA', ADD COLUMN `y` datetime NOT NULL DEFAULT '2012-01-01 12:12:12'",
            $sql->generateSQL(),
            'ALTER ADD COLUMN multiple clause'
        );
    }

    public function testALTERADDCOLAFTER()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->add([
                        'x' => 'varchar(100)'
                    ])
                  ->after('y');
        $this->assertEquals(
            "ALTER TABLE `alter` ADD COLUMN `x` varchar(100) NOT NULL AFTER `y`",
            $sql->generateSQL(),
            'ALTER ADD COLUMN AFTER clause'
        );
    }

    public function testALTERADDCOLFIRST()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->add([
                        'x' => 'varchar(100)'
                    ])
                  ->first();
        $this->assertEquals(
            "ALTER TABLE `alter` ADD COLUMN `x` varchar(100) NOT NULL FIRST",
            $sql->generateSQL(),
            'ALTER ADD COLUMN FIRST clause'
        );
    }

    public function testALTERADDCOLFIRSTANDAFTER()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->add([
                        'x' => 'varchar(100)'
                    ])
                  ->first()
                  ->add([
                        'x' => 'varchar(100)'
                    ])
                  ->after('y');
        $this->assertEquals(
            "ALTER TABLE `alter` ADD COLUMN `x` varchar(100) NOT NULL FIRST ADD COLUMN `x` varchar(100) NOT NULL AFTER `y`",
            $sql->generateSQL(),
            'ALTER ADD COLUMN FIRST ADD COLUMN AFTER clause'
        );
    }

    public function testALTERDROPCOL()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->drop('x');
        $this->assertEquals(
            "ALTER TABLE `alter` DROP COLUMN `x`",
            $sql->generateSQL(),
            'ALTER DROP COLUMN clause'
        );
    }

    public function testALTERADDDROPCOL()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->add([
                    'x' => 'varchar(100)'
                  ])
                  ->drop('x');
        $this->assertEquals(
            "ALTER TABLE `alter` ADD COLUMN `x` varchar(100) NOT NULL DROP COLUMN `x`",
            $sql->generateSQL(),
            'ALTER ADD COLUMN DROP COLUMN clause'
        );
    }

    public function testALTERDROPCOLS()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->drop(['x', 'y']);
        $this->assertEquals(
            "ALTER TABLE `alter` DROP COLUMN `x`, DROP COLUMN `y`",
            $sql->generateSQL(),
            'ALTER DROP COLUMN multiple clause'
        );
    }

    public function testALTERCHANGECOL()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->change('x', [
                        'y' => 'varchar(200)'
                    ]);
        $this->assertEquals(
            "ALTER TABLE `alter` CHANGE COLUMN `x` `y` varchar(200) NOT NULL",
            $sql->generateSQL(),
            'ALTER CHANGE COLUMN clause'
        );
    }

    public function testALTERCHANGECOLS()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->change(['x', 'z'], [
                        'y' => 'varchar(200)',
                        'a' => 'datetime',
                    ]);
        $this->assertEquals(
            "ALTER TABLE `alter` CHANGE COLUMN `x` `y` varchar(200) NOT NULL, CHANGE COLUMN `z` `a` datetime NOT NULL",
            $sql->generateSQL(),
            'ALTER CHANGE COLUMN multiple clause'
        );
    }

    public function testALTERADDKEY()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->addKey('x');
        $this->assertEquals(
            "ALTER TABLE `alter` ADD KEY `x` (`x`)",
            $sql->generateSQL(),
            'ALTER ADD KEY'
        );
    }

    public function testALTERDROPKEY()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->dropKey('x');
        $this->assertEquals(
            "ALTER TABLE `alter` DROP KEY `x`",
            $sql->generateSQL(),
            'ALTER DROP KEY'
        );
    }

    public function testALTERADDINDEX()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->addIndex(['x' => 'fulltext']);
        $this->assertEquals(
            "ALTER TABLE `alter` ADD FULLTEXT `x` (`x`)",
            $sql->generateSQL(),
            'ALTER ADD INDEX'
        );
    }

    public function testALTERDROPINDEX()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->dropIndex('x');
        $this->assertEquals(
            "ALTER TABLE `alter` DROP INDEX `x`",
            $sql->generateSQL(),
            'ALTER DROP INDEX'
        );
    }

    public function testALTERADDKEYMULTIPLE()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->addKey(['x' => 'key', 'y' => 'key']);
        $this->assertEquals(
            "ALTER TABLE `alter` ADD KEY `x` (`x`), ADD KEY `y` (`y`)",
            $sql->generateSQL(),
            'ALTER ADD KEY  multiple clause'
        );
    }

    public function testALTERADDPRIMARYKEY()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->addPrimaryKey('x');
        $this->assertEquals(
            "ALTER TABLE `alter` ADD PRIMARY KEY (`x`)",
            $sql->generateSQL(),
            'ALTER ADD PRIMARY KEY'
        );
    }

    public function testALTERDROPPRIMARYKEY()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->dropPrimaryKey();
        $this->assertEquals(
            "ALTER TABLE `alter` DROP PRIMARY KEY",
            $sql->generateSQL(),
            'ALTER DROP PRIMARY KEY'
        );
    }
}
