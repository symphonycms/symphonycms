<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseAlter
 * @covers DatabaseKeyDefinition
 * @covers DatabaseColumnDefinition
 */
final class DatabaseAlterTest extends TestCase
{
    public function testALTERENGINE()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
            ->engine('InnoBD');
        $this->assertEquals(
            "ALTER TABLE `alter` ENGINE = :engine",
            $sql->generateSQL(),
            'ALTER TABLE ENGINE  clause'
        );
        $values = $sql->getValues();
        $this->assertEquals('InnoBD', $values['engine'], 'engine is InnoBD');
        $this->assertEquals(1, count($values), '1 value');
    }

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
            "ALTER TABLE `alter` ADD COLUMN `x` varchar(100) NOT NULL DEFAULT :x_default, ADD COLUMN `y` datetime NOT NULL DEFAULT :y_default",
            $sql->generateSQL(),
            'ALTER ADD COLUMN multiple clause'
        );
        $values = $sql->getValues();
        $this->assertEquals('TATA', $values['x_default'], 'x_default is "TATA"');
        $this->assertEquals('2012-01-01 12:12:12', $values['y_default'], 'y_default is "2012-01-01 12:12:12"');
        $this->assertEquals(2, count($values), '2 values');
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
            "ALTER TABLE `alter` ADD COLUMN `x` varchar(100) NOT NULL FIRST , ADD COLUMN `x` varchar(100) NOT NULL AFTER `y`",
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
            "ALTER TABLE `alter` ADD COLUMN `x` varchar(100) NOT NULL , DROP COLUMN `x`",
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

    public function testALTERMODIFYCOLS()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->modify([
                        'y' => 'varchar(200)',
                        'a' => 'datetime',
                  ]);
        $this->assertEquals(
            "ALTER TABLE `alter` MODIFY COLUMN `y` varchar(200) NOT NULL, MODIFY COLUMN `a` datetime NOT NULL",
            $sql->generateSQL(),
            'ALTER MODIFY COLUMN multiple clause'
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
            'ALTER ADD KEY multiple clause'
        );
    }

    public function testALTERADDCOLADDKEY()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->add(['x' => 'varchar(250)'])
                  ->addKey(['x' => 'key', 'y' => 'key']);
        $this->assertEquals(
            "ALTER TABLE `alter` ADD COLUMN `x` varchar(250) NOT NULL , ADD KEY `x` (`x`), ADD KEY `y` (`y`)",
            $sql->generateSQL(),
            'ALTER ADD COLUMN ADD KEY clause'
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

    public function testALTERFormattedSQL()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
            ->addIndex(['x' => 'fulltext'])
            ->change('x', [
                'y' => 'varchar(200)'
            ])
            ->addPrimaryKey('x')
            ->dropPrimaryKey();
        $this->assertEquals(
            "ALTER TABLE `alter`\n\tADD FULLTEXT `x` (`x`)\n\t, CHANGE COLUMN `x` `y` varchar(200) NOT NULL\n\t, ADD PRIMARY KEY (`x`)\n\t, DROP PRIMARY KEY",
            $sql->generateFormattedSQL(),
            'ALTER Formatted'
        );
    }

    public function testMultipleCalls()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
            ->change('y', ['z' => [
                'type' => 'int(11)',
                'signed' => false,
                'default' => 0
            ]])
            ->add([
                'o' => [
                    'type' => 'int(10)',
                    'default' => 1
                ]
            ])
            ->after('callback')
            ->dropPrimaryKey();
        $this->assertEquals(
            "ALTER TABLE `alter` CHANGE COLUMN `y` `z` int(11) unsigned NOT NULL DEFAULT :z_default , ADD COLUMN `o` int(10) unsigned NOT NULL DEFAULT :o_default AFTER `callback` , DROP PRIMARY KEY",
            $sql->generateSQL(),
            'ALTER Formatted'
        );
    }

    public function testALTERADDCOLSWITHCHARSETANDCOLATE()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->add([
                        'x' => [
                            'type' => 'varchar(100)',
                            'default' => 'TATA',
                            'charset' => 'utf8',
                            'collate' => 'utf8_unicode_ci',
                        ],
                    ]);
        $this->assertEquals(
            "ALTER TABLE `alter` ADD COLUMN `x` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT :x_default",
            $sql->generateSQL(),
            'ALTER ADD COLUMN COLLATE CHARSET'
        );
        $values = $sql->getValues();
        $this->assertEquals('TATA', $values['x_default'], 'x_default is "TATA"');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testALTERCONVERTO()
    {
        $db = new Database([
            'collate' => 'test'
        ]);
        $sql = $db->alter('alter')
                  ->charset('test')
                  ->convertTo();
        $this->assertEquals(
            "ALTER TABLE `alter` CONVERT TO CHARACTER SET test COLLATE test",
            $sql->generateSQL(),
            'ALTER convert to'
        );
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testALTERCONVERTO_NoValues()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->convertTo();
    }

    public function testALTERDEFAULTS()
    {
        $db = new Database([
            'charset' => 'test'
        ]);
        $sql = $db->alter('alter')
                  ->collate('test')
                  ->defaults();
        $this->assertEquals(
            "ALTER TABLE `alter` DEFAULT CHARACTER SET test DEFAULT COLLATE test",
            $sql->generateSQL(),
            'ALTER defaults'
        );
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testALTERDEFAULTS_NoValues()
    {
        $db = new Database([]);
        $sql = $db->alter('alter')
                  ->defaults();
    }
}
