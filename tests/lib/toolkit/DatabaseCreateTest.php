<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseCreate
 * @covers DatabaseKeyDefinition
 * @covers DatabaseColumnDefinition
 */
final class DatabaseCreateTest extends TestCase
{
    public function testCREATE()
    {
        $db = new Database([]);
        $sql = $db->create('create')
                  ->charset('utf8')
                  ->collate('utf8')
                  ->engine('engine')
                  ->fields([
                    'x' => 'varchar(100)'
                  ])
                  ->finalize(); // this would by called by execute()
        $this->assertEquals(
            "CREATE TABLE `create` ( `x` varchar(100) CHARACTER SET utf8 COLLATE utf8 NOT NULL ) ENGINE=engine DEFAULT CHARSET=utf8 COLLATE=utf8",
            $sql->generateSQL(),
            'CREATE clause'
        );
    }

    public function testCREATEWITHARRAYMERGE()
    {
        $db = new Database([]);
        $sql = $db->create('create')
                  ->fields(array_merge(['x' => 'varchar(100)'], ['y' => 'varchar(100)']))
                  ->finalize(); // this would by called by execute()
        $this->assertEquals(
            "CREATE TABLE `create` ( `x` varchar(100) NOT NULL, `y` varchar(100) NOT NULL )",
            $sql->generateSQL(),
            'CREATE WITH ARRAY MERGE clause'
        );
    }

    public function testCREATEIFNOTEXISTS()
    {
        $db = new Database([]);
        $sql = $db->create('create')
            ->ifNotExists()
            ->fields([
                'x' => 'varchar(100)'
            ])
            ->finalize(); // this would by called by execute()
        $this->assertEquals(
            "CREATE TABLE IF NOT EXISTS `create` ( `x` varchar(100) NOT NULL )",
            $sql->generateSQL(),
            'CREATE IF NOT EXISTS clause'
        );
    }

    public function testCREATEMULTIPLE()
    {
        $db = new Database([]);
        $sql = $db->create('create')
                  ->collate('utf8')
                  ->fields([
                    'x' => [
                        'type' => 'varchar(100)',
                        'default' => 'TATA',
                    ],
                    'y' => [
                        'type' => 'datetime',
                        'default' => '2012-01-01 12:12:12'
                    ],
                    'z' => [
                        'type' => 'enum',
                        'values' => ['yes', 'no'],
                        'default' => 'yes',
                    ],
                    'id' => [
                        'type' => 'int(11)',
                        'auto' => true
                    ]
                  ])
                  ->finalize(); // this would by called by execute()
        $this->assertEquals(
            "CREATE TABLE `create` ( `x` varchar(100) COLLATE utf8 NOT NULL DEFAULT :x_default, `y` datetime NOT NULL DEFAULT :y_default, `z` enum(:z1, :z2) COLLATE utf8 NOT NULL DEFAULT :z_default, `id` int(11) unsigned NOT NULL AUTO_INCREMENT ) COLLATE=utf8",
            $sql->generateSQL(),
            'CREATE clause with multiple fields'
        );
        $values = $sql->getValues();
        $this->assertEquals('yes', $values['z1'], 'z1 is "yes"');
        $this->assertEquals('no', $values['z2'], 'z2 is "no"');
        $this->assertEquals('TATA', $values['x_default'], 'z_default is "TATA"');
        $this->assertEquals('2012-01-01 12:12:12', $values['y_default'], 'z_default is "2012-01-01 12:12:12"');
        $this->assertEquals('yes', $values['z_default'], 'z_default is "yes"');
        $this->assertEquals(5, count($values), '5 values');
    }

    public function testCREATEMULTIPLECALLS()
    {
        $db = new Database([]);
        $sql = $db->create('create')
                  ->fields([
                    'x' => 'varchar(100)'
                  ])
                  ->fields([
                    'y' => [
                        'type' => 'datetime',
                        'default' => '2012-01-01 12:12:12'
                    ]
                  ]);
        $this->assertEquals(
            "CREATE TABLE `create` ( `x` varchar(100) NOT NULL , `y` datetime NOT NULL DEFAULT :y_default )",
            $sql->generateSQL(),
            'CREATE clause'
        );
        $values = $sql->getValues();
        $this->assertEquals('2012-01-01 12:12:12', $values['y_default'], 'z_default is "2012-01-01 12:12:12"');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testCREATEKEYS()
    {
        $db = new Database([]);
        $sql = $db->create('create')
                  ->fields([
                    'x' => 'varchar(100)',
                    'id' => [
                        'type' => 'int(11)',
                        'auto' => true
                    ]
                  ])
                  ->keys([
                    'id' => 'primary',
                    'x' => 'key',
                    'x1' => [
                        'type' => 'unique',
                        'cols' => ['x'],
                    ],
                    'x2' => [
                        'type' => 'index',
                        'cols' => ['x' => 333, 'id']
                    ],
                    'x3' => [
                        'type' => 'fulltext',
                        'cols' => 'x',
                    ]
                  ]);
        $this->assertEquals(
            "CREATE TABLE `create` ( `x` varchar(100) NOT NULL, `id` int(11) unsigned NOT NULL AUTO_INCREMENT , PRIMARY KEY (`id`), KEY `x` (`x`), UNIQUE KEY `x1` (`x`), INDEX `x2` (`x`(333), `id`), FULLTEXT `x3` (`x`) )",
            $sql->generateSQL(),
            'CREATE clause with KEYS'
        );
    }

    public function testCREATEKeyBeforeFields()
    {
        $db = new Database([]);
        $sql = $db->create('create')
                    ->keys([
                    'id' => 'primary',
                    'x' => 'key',
                    ])
                    ->fields([
                    'x' => 'varchar(100)',
                    'id' => [
                        'type' => 'int(11)',
                        'auto' => true
                    ]
                    ]);
        $this->assertEquals(
            "CREATE TABLE `create` ( `x` varchar(100) NOT NULL, `id` int(11) unsigned NOT NULL AUTO_INCREMENT , PRIMARY KEY (`id`), KEY `x` (`x`) )",
            $sql->generateSQL(),
            'CREATE clause with KEYS'
        );
    }

    public function testCREATENoFields()
    {
        $db = new Database([]);
        $sql = $db->create('create')
                  ->charset('utf8')
                  ->collate('utf8')
                  ->engine('engine')
                  ->finalize(); // this would by called by execute()
        $this->assertEquals(
            "CREATE TABLE `create` ( ) ENGINE=engine DEFAULT CHARSET=utf8 COLLATE=utf8",
            $sql->generateSQL(),
            'CREATE clause with finalize()'
        );
    }

    public function testCREATEFormattedSQL()
    {
        $db = new Database([]);
        $sql = $db->create('create')
            ->charset('utf8')
            ->collate('utf8')
            ->engine('engine')
            ->keys([
                'id' => 'primary',
                'x' => 'key',
            ])
            ->fields([
                'x' => 'varchar(100)',
                'id' => [
                    'type' => 'int(11)',
                    'auto' => true
                ]
            ])
            ->finalize();
        $this->assertEquals(
            "CREATE TABLE `create` (\n\t`x` varchar(100) CHARACTER SET utf8 COLLATE utf8 NOT NULL, `id` int(11) unsigned NOT NULL AUTO_INCREMENT ,\n\tPRIMARY KEY (`id`), KEY `x` (`x`)\n) ENGINE=engine DEFAULT CHARSET=utf8 COLLATE=utf8",
            $sql->generateFormattedSQL(),
            'CREATE clause formatted'
        );
    }
}
