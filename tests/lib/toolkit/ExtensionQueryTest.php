<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers ExtensionQuery
 */
final class ExtensionQueryTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    public function testDefaultSchema()
    {
        $q = new \ExtensionQuery($this->db);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `extensions` AS `ex`",
            $q->generateSQL(),
            'Simple new ExtensionQuery test'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSchemaDefaultProjection()
    {
        $q = (new \ExtensionQuery($this->db))->disableDefaultSort()->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `ex`.* FROM `extensions` AS `ex`",
            $q->generateSQL(),
            'new ExtensionQuery with Default schema and Default projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSchemaDefaultProjectionDefaultSort()
    {
        $q = (new \ExtensionQuery($this->db))->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `ex`.* FROM `extensions` AS `ex` ORDER BY `ex`.`name` ASC",
            $q->generateSQL(),
            'new ExtensionQuery with Default schema, Default projection and Default Sort'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultCount()
    {
        $q = new \ExtensionQuery($this->db, ['COUNT(*)']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(*) FROM `extensions` AS `ex`",
            $q->generateSQL(),
            'new ExtensionQuery test with COUNT(*) projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testExtensionFilter()
    {
        $q = (new \ExtensionQuery($this->db))->extension(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `extensions` AS `ex` WHERE `ex`.`id` = :ex_id",
            $q->generateSQL(),
            'new ExtensionQuery test ->extension()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['ex_id'], 'ex_id is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testStatusFilter()
    {
        $q = (new \ExtensionQuery($this->db))->status('x');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `extensions` AS `ex` WHERE `ex`.`status` = :ex_status",
            $q->generateSQL(),
            'new ExtensionQuery test ->status()'
        );
        $values = $q->getValues();
        $this->assertEquals('x', $values['ex_status'], 'ex_status is x');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testEnabledFilter()
    {
        $q = (new \ExtensionQuery($this->db))->enabled();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `extensions` AS `ex` WHERE `ex`.`status` = :ex_status",
            $q->generateSQL(),
            'new ExtensionQuery test ->enabled()'
        );
        $values = $q->getValues();
        $this->assertEquals('enabled', $values['ex_status'], 'ex_status is enabled');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testDisabledFilter()
    {
        $q = (new \ExtensionQuery($this->db))->disabled();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `extensions` AS `ex` WHERE `ex`.`status` = :ex_status",
            $q->generateSQL(),
            'new ExtensionQuery test ->disabled()'
        );
        $values = $q->getValues();
        $this->assertEquals('disabled', $values['ex_status'], 'ex_status is disabled');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSort()
    {
        $q = (new \ExtensionQuery($this->db))->sort('x', 'DESC');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `extensions` AS `ex` ORDER BY `ex`.`x` DESC",
            $q->generateSQL(),
            'new ExtensionQuery with ->sort(x, DESC)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
