<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers PageQuery
 */
final class PageQueryTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    public function testDefaultSchema()
    {
        $q = new \PageQuery($this->db);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `pages` AS `p`",
            $q->generateSQL(),
            'Simple new PageQuery test'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSchemaDefaultProjection()
    {
        $q = (new \PageQuery($this->db))->disableDefaultSort()->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `p`.* FROM `pages` AS `p`",
            $q->generateSQL(),
            'new PageQuery with Default schema and Default projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSchemaDefaultProjectionDefaultSort()
    {
        $q = (new \PageQuery($this->db))->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `p`.* FROM `pages` AS `p` ORDER BY `p`.`sortorder` ASC",
            $q->generateSQL(),
            'new PageQuery with Default schema, Default projection and Default Sort'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultCount()
    {
        $q = new \PageQuery($this->db, ['COUNT(*)']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(*) FROM `pages` AS `p`",
            $q->generateSQL(),
            'new PageQuery test with COUNT(*) projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testPageFilter()
    {
        $q = (new \PageQuery($this->db))->page(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `pages` AS `p` WHERE `p`.`id` = :p_id",
            $q->generateSQL(),
            'new PageQuery test ->page()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['p_id'], 'p_id is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testPagesFilter()
    {
        $q = (new \PageQuery($this->db))->pages([4, 5, 6]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `pages` AS `p` WHERE `p`.`id` IN (:p_id, :p_id2, :p_id3)",
            $q->generateSQL(),
            'new PageQuery test ->pages()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['p_id'], 'p_id is 4');
        $this->assertEquals(5, $values['p_id2'], 'p_id2 is 5');
        $this->assertEquals(6, $values['p_id3'], 'p_id3 is 6');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testHandleFilter()
    {
        $q = (new \PageQuery($this->db))->handle('x');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `pages` AS `p` WHERE `p`.`handle` = :p_handle",
            $q->generateSQL(),
            'new PageQuery test ->handle()'
        );
        $values = $q->getValues();
        $this->assertEquals('x', $values['p_handle'], 'p_handle is x');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testPathFilter()
    {
        $q = (new \PageQuery($this->db))->path('x');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `pages` AS `p` WHERE `p`.`path` = :p_path",
            $q->generateSQL(),
            'new PageQuery test ->path()'
        );
        $values = $q->getValues();
        $this->assertEquals('x', $values['p_path'], 'p_path is x');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testParentFilter()
    {
        $q = (new \PageQuery($this->db))->parent('x');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `pages` AS `p` WHERE `p`.`parent` = :p_parent",
            $q->generateSQL(),
            'new PageQuery test ->parent()'
        );
        $values = $q->getValues();
        $this->assertEquals('x', $values['p_parent'], 'p_parentp is x');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSort()
    {
        $q = (new \PageQuery($this->db))->sort('x', 'DESC');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `pages` AS `p` ORDER BY `p`.`x` DESC",
            $q->generateSQL(),
            'new PageQuery with ->sort(x, DESC)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
