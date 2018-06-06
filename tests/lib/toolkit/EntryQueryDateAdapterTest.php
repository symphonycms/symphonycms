<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers EntryQueryDateAdapter
 */
final class EntryQueryDateAdapterTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
        \Symphony::initialiseConfiguration();
        \Lang::initialize();
    }

    /**
     * @param Field $f
     * @return EntryQueryDateAdapter
     */
    private function createAdapter(Field $f = null)
    {
        if (!$f) {
            $f = new \Field();
            $f->set('id', 1);
        }
        return new \EntryQueryDateAdapter($f);
    }

    public function testExactFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['2018-03-28']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE (`f1`.`value` >= :f1_value AND `f1`.`value` <= :f1_value2)",
            $q->generateSQL(),
            'Simple exact match ->filter([2018-03-28])'
        );
        $values = $q->getValues();
        $this->assertEquals('2018-03-28 00:00:00', $values['f1_value'], 'f1_value is 2018-03-28 00:00:00');
        $this->assertEquals('2018-03-28 23:59:59', $values['f1_value2'], 'f1_value2 is 2018-03-28 23:59:59');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testYearMonthExactFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['2018/02']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE (`f1`.`value` >= :f1_value AND `f1`.`value` <= :f1_value2)",
            $q->generateSQL(),
            'Simple exact match ->filter([2018/02])'
        );
        $values = $q->getValues();
        $this->assertEquals('2018-02-01 00:00:00', $values['f1_value'], 'f1_value is 2018-02-01 00:00:00');
        $this->assertEquals('2018-02-28 23:59:59', $values['f1_value2'], 'f1_value2 is 2018-02-28 23:59:59');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testEarlierFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['earlier than 2018-03-28']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE `f1`.`value` < :f1_value",
            $q->generateSQL(),
            'Simple exact match ->filter([earlier than 2018-03-28])'
        );
        $values = $q->getValues();
        $this->assertEquals('2018-03-28 00:00:00', $values['f1_value'], 'f1_value is 2018-03-28 00:00:00');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testEqualToOrLaterFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['equal to or later than 2018-03-28']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE `f1`.`value` >= :f1_value",
            $q->generateSQL(),
            'Simple exact match ->filter([earlier than 2018-03-28])'
        );
        $values = $q->getValues();
        $this->assertEquals('2018-03-28 00:00:00', $values['f1_value'], 'f1_value is 2018-03-28 00:00:00');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testNotFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['not: 2018-03-28', 'tata']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE (`f1`.`value` != :f1_value AND `f1`.`value` != :f1_value2)",
            $q->generateSQL(),
            'Not filter ->filter([not: 2018-03-28, tata])'
        );
        $values = $q->getValues();
        $this->assertEquals('2018-03-28', $values['f1_value'], 'f1_value is 2018-03-28');
        $this->assertEquals('tata', $values['f1_value2'], 'f1_value2 is tata');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testRangesFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['2017-03-28 to 2018-03', 'from 2017 to 2018']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE ((`f1`.`value` >= :f1_value AND `f1`.`value` <= :f1_value2) OR (`f1`.`value` >= :f1_value3 AND `f1`.`value` <= :f1_value4))",
            $q->generateSQL(),
            'Simple exact match ->filter([2017-03-28 to 2018-03-28, from 2017 to 2018])'
        );
        $values = $q->getValues();
        $this->assertEquals('2017-03-28 00:00:00', $values['f1_value'], 'f1_value is 2017-03-28 00:00:00');
        $this->assertEquals('2018-03-31 23:59:59', $values['f1_value2'], 'f1_value2 is 2018-03-28 23:59:59');
        $this->assertEquals('2017-01-01 00:00:00', $values['f1_value3'], 'f1_value3 is 2017-01-01 00:00:00');
        $this->assertEquals('2018-12-31 23:59:59', $values['f1_value4'], 'f1_value4 is 2018-12-31 23:59:59');
        $this->assertEquals(4, count($values), '4 values');
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
}
