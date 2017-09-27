<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseCreate
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
                  ->appendCloseParenthesis(); // this would by called by execute()
        $this->assertEquals(
            "CREATE TABLE `create` ( `x` varchar(100) COLLATE utf8 NOT NULL )",
            $sql->generateSQL(),
            'CREATE clause'
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
                  ->appendCloseParenthesis(); // this would by called by execute()
        $this->assertEquals(
            "CREATE TABLE `create` ( `x` varchar(100) COLLATE utf8 NOT NULL DEFAULT 'TATA', `y` datetime NOT NULL DEFAULT '2012-01-01 12:12:12', `z` enum('yes', 'no') COLLATE utf8 NOT NULL DEFAULT 'yes', `id` int(11) unsigned NOT NULL AUTO_INCREMENT )",
            $sql->generateSQL(),
            'CREATE clause with multiple fields'
        );
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
                        'cols' => 'x',
                    ],
                    'x2' => [
                        'type' => 'index',
                        'cols' => ['x', 'id']
                    ],
                    'x3' => [
                        'type' => 'fulltext',
                        'cols' => 'x',
                    ]
                  ])
                  ->appendCloseParenthesis(); // this would by called by execute()
        $this->assertEquals(
            "CREATE TABLE `create` ( `x` varchar(100) NOT NULL, `id` int(11) unsigned NOT NULL AUTO_INCREMENT , PRIMARY KEY (`id`), KEY `x` (`x`), UNIQUE KEY `x1` (`x`), INDEX `x2` (`x`, `id`), FULLTEXT `x3` (`x`) )",
            $sql->generateSQL(),
            'CREATE clause with KEYS'
        );
    }

    public function testCREATEfinalize()
    {
        $db = new Database([]);
        $sql = $db->create('create')
                  ->charset('utf8')
                  ->collate('utf8')
                  ->engine('engine')
                  ->finalize(); // this would by called by execute()
        $this->assertEquals(
            "CREATE TABLE `create` ENGINE=engine DEFAULT CHARSET=utf8 COLLATE=utf8",
            $sql->generateSQL(),
            'CREATE clause with finalize()'
        );
    }
}
