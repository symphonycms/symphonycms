<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers EntryQueryFieldAdapter
 */
final class EntryQueryFieldAdapterTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    /**
     * @param Field $f
     * @return EntryQueryFieldAdapter
     */
    private function createAdapter(Field $f = null)
    {
        if (!$f) {
            $f = new \Field();
            $f->set('id', 1);
        }
        return new \EntryQueryFieldAdapter($f);
    }

    public function testExactFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['test']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE `f1`.`value` = :f1_value",
            $q->generateSQL(),
            'Simple exact match ->filter([test])'
        );
        $values = $q->getValues();
        $this->assertEquals('test', $values['f1_value'], 'f1_value is test');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testEmptyFilters()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, []);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e`",
            $q->generateSQL(),
            'Simple empty match ->filter([])'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testNotFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['not: test', 'tata']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE (`f1`.`value` != :f1_value AND `f1`.`value` != :f1_value2)",
            $q->generateSQL(),
            'Not filter ->filter([not: test, tata])'
        );
        $values = $q->getValues();
        $this->assertEquals('test', $values['f1_value'], 'f1_value is test');
        $this->assertEquals('tata', $values['f1_value2'], 'f1_value2 is tata');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testRegExpFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['regexp: ^test[\d]']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE `f1`.`value` REGEXP :f1_value",
            $q->generateSQL(),
            'RegExp match ->filter([regexp: test])'
        );
        $values = $q->getValues();
        $this->assertEquals('^test[\d]', $values['f1_value'], 'f1_value is ^test[\d]');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSQLFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['sql: null']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE `f1`.`value` IS :_null_",
            $q->generateSQL(),
            'SQL null match ->filter([sql: null])'
        );
        $values = $q->getValues();
        $this->assertEquals(null, $values['_null_'], '_null_ is null');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testCreateFilterEqualityOrNull()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $filter = $o->createFilterEqualityOrNull('test', ['test']);
        $q->where($filter);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE (`f1`.`test` = :f1_test OR `f1`.`test` IS :_null_)",
            $q->generateSQL(),
            'createFilterEqualityOrNull test'
        );
        $values = $q->getValues();
        $this->assertEquals('test', $values['f1_test'], 'f1_test is test');
        $this->assertEquals(null, $values['_null_'], '_null_ is null');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testSortAsc()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->sort($q, 'asc');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `f1`.`value` FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` ORDER BY `f1`.`value` ASC , `e`.`id` ASC",
            $q->generateSQL(),
            'Simple asc sort ->sort(asc)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSortRandom()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->sort($q, 'rand');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY RAND()",
            $q->generateSQL(),
            'Simple random ->sort(random)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testExactFilterSortAsc()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['test']);
        $o->sort($q, 'asc');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `f1`.`value` FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE `f1`.`value` = :f1_value ORDER BY `f1`.`value` ASC , `e`.`id` ASC",
            $q->generateSQL(),
            'Exact match ->filter([test]) and asc sort ->sort(asc)'
        );
        $values = $q->getValues();
        $this->assertEquals('test', $values['f1_value'], 'f1_value is test');
        $this->assertEquals(1, count($values), '1 value');
    }
}
