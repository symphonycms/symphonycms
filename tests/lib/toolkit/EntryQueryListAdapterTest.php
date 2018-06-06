<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers EntryQueryListAdapter
 */
final class EntryQueryListAdapterTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    /**
     * @param Field $f
     * @return EntryQueryListAdapter
     */
    private function createAdapter(Field $f = null)
    {
        if (!$f) {
            $f = new \Field();
            $f->set('id', 1);
        }
        return new \EntryQueryListAdapter($f);
    }

    public function testExactFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['test']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE (`f1`.`value` = :f1_value OR `f1`.`handle` = :f1_handle)",
            $q->generateSQL(),
            'Simple exact match ->filter([test])'
        );
        $values = $q->getValues();
        $this->assertEquals('test', $values['f1_value'], 'f1_value is test');
        $this->assertEquals('test', $values['f1_handle'], 'f1_handle is test');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testNotFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['not: test', 'tata']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE ((`f1`.`value` != :f1_value AND `f1`.`handle` != :f1_handle) AND (`f1`.`value` != :f1_value2 AND `f1`.`handle` != :f1_handle2))",
            $q->generateSQL(),
            'Not filter ->filter([not: test, tata])'
        );
        $values = $q->getValues();
        $this->assertEquals('test', $values['f1_value'], 'f1_value is test');
        $this->assertEquals('test', $values['f1_handle'], 'f1_handle is test');
        $this->assertEquals('tata', $values['f1_value2'], 'f1_value2 is tata');
        $this->assertEquals('tata', $values['f1_handle2'], 'f1_handle2 is tata');
        $this->assertEquals(4, count($values), '4 values');
    }

    public function testNotOrNullFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['sql-null-or-not: test', 'tata']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE (((`f1`.`value` != :f1_value OR `f1`.`value` IS :_null_) OR (`f1`.`handle` != :f1_handle OR `f1`.`handle` IS :_null_) OR `f1`.`relation_id` IS :_null_) AND ((`f1`.`value` != :f1_value2 OR `f1`.`value` IS :_null_) OR (`f1`.`handle` != :f1_handle2 OR `f1`.`handle` IS :_null_) OR `f1`.`relation_id` IS :_null_))",
            $q->generateSQL(),
            'Not filter ->filter([sql-null-or-not: test, tata])'
        );
        $values = $q->getValues();
        $this->assertEquals('test', $values['f1_value'], 'f1_value is test');
        $this->assertEquals('test', $values['f1_handle'], 'f1_handle is test');
        $this->assertEquals('tata', $values['f1_value2'], 'f1_value2 is tata');
        $this->assertEquals('tata', $values['f1_handle2'], 'f1_handle2 is tata');
        $this->assertEquals(null, $values['_null_'], '_null_ is NULL');
        $this->assertEquals(5, count($values), '4 values');
    }

    public function testRegExpFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['regexp: ^test[\d]']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE (`f1`.`value` REGEXP :f1_value OR `f1`.`handle` REGEXP :f1_handle)",
            $q->generateSQL(),
            'RegExp match ->filter([regexp: test])'
        );
        $values = $q->getValues();
        $this->assertEquals('^test[\d]', $values['f1_value'], 'f1_value is ^test[\d]');
        $this->assertEquals('^test[\d]', $values['f1_handle'], 'f1_value is ^test[\d]');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testSQLFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['sql: null']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE (`f1`.`value` IS :_null_ OR `f1`.`handle` IS :_null_)",
            $q->generateSQL(),
            'SQL null match ->filter([sql: null])'
        );
        $values = $q->getValues();
        $this->assertEquals(null, $values['_null_'], '_null_ is null');
        $this->assertEquals(1, count($values), '1 value');
    }
}
