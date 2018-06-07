<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers FieldQuery
 */
final class FieldQueryTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    public function testDefaultSchema()
    {
        $q = new \FieldQuery($this->db);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f`",
            $q->generateSQL(),
            'Simple new FieldQuery test'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSchemaDefaultProjection()
    {
        $q = (new \FieldQuery($this->db))->disableDefaultSort()->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `f`.* FROM `fields` AS `f`",
            $q->generateSQL(),
            'new FieldQuery with Default schema and Default projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSchemaDefaultProjectionDefaultSort()
    {
        $q = (new \FieldQuery($this->db))->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `f`.* FROM `fields` AS `f` ORDER BY `f`.`sortorder` ASC",
            $q->generateSQL(),
            'new FieldQuery with Default schema, Default projection and Default Sort'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultCount()
    {
        $q = new \FieldQuery($this->db, ['COUNT(*)']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(*) FROM `fields` AS `f`",
            $q->generateSQL(),
            'new FieldQuery test with COUNT(*) projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSectionFilter()
    {
        $q = (new \FieldQuery($this->db))->section(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f` WHERE `f`.`parent_section` = :f_parent_section",
            $q->generateSQL(),
            'new FieldQuery with ->section()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['f_parent_section'], 'f_parent_section is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testFieldFilter()
    {
        $q = (new \FieldQuery($this->db))->field(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f` WHERE `f`.`id` = :f_id",
            $q->generateSQL(),
            'new FieldQuery test ->field()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['f_id'], 'f_id is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testFieldsFilter()
    {
        $q = (new \FieldQuery($this->db))->fields([4, 5, 6]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f` WHERE `f`.`id` IN (:f_id, :f_id2, :f_id3)",
            $q->generateSQL(),
            'new FieldQuery test ->fields()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['f_id'], 'f_id is 4');
        $this->assertEquals(5, $values['f_id2'], 'f_id2 is 5');
        $this->assertEquals(6, $values['f_id3'], 'f_id3 is 6');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testTypeFilter()
    {
        $q = (new \FieldQuery($this->db))->type('textbox');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f` WHERE `f`.`type` = :f_type",
            $q->generateSQL(),
            'new FieldQuery test ->type()'
        );
        $values = $q->getValues();
        $this->assertEquals('textbox', $values['f_type'], 'f_type is `textbox`');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testLocationFilter()
    {
        $q = (new \FieldQuery($this->db))->location('main');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f` WHERE `f`.`location` = :f_location",
            $q->generateSQL(),
            'new FieldQuery test ->location()'
        );
        $values = $q->getValues();
        $this->assertEquals('main', $values['f_location'], 'f_location is `main`');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testNameFilter()
    {
        $q = (new \FieldQuery($this->db))->name('field');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f` WHERE `f`.`element_name` = :f_element_name",
            $q->generateSQL(),
            'new FieldQuery test ->name()'
        );
        $values = $q->getValues();
        $this->assertEquals('field', $values['f_element_name'], 'f_element_name is `field`');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSort()
    {
        $q = (new \FieldQuery($this->db))->sort('x', 'DESC');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f` ORDER BY `f`.`x` DESC",
            $q->generateSQL(),
            'new FieldQuery with ->sort(x, DESC)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
