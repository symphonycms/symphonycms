<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers EntryQueryCheckboxAdapter
 */
final class EntryQueryCheckboxAdapterTest extends TestCase
{
    private $db;
    private $defaultState = null;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    /**
     * @param Field $f
     * @return EntryQueryCheckboxAdapter
     */
    private function createAdapter(Field $f = null)
    {
        if (!$f) {
            $f = new \FieldCheckbox();
            $f->set('id', 1);
            if ($this->defaultState) {
                $f->set('default_state', $this->defaultState);
            }
        }
        return new \EntryQueryCheckboxAdapter($f);
    }

    public function testExactYesFilter()
    {
        $q = (new \EntryQuery($this->db));
        $this->defaultState = null;
        $o = $this->createAdapter();
        $o->filter($q, ['yes']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE `f1`.`value` = :f1_value",
            $q->generateSQL(),
            'Simple exact match ->filter([yes])'
        );
        $values = $q->getValues();
        $this->assertEquals('yes', $values['f1_value'], 'f1_value is yes');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testExactYesDefaultNoFilter()
    {
        $q = (new \EntryQuery($this->db));
        $this->defaultState = 'off';
        $o = $this->createAdapter();
        $o->filter($q, ['yes']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE `f1`.`value` = :f1_value",
            $q->generateSQL(),
            'Exact match ->filter([yes]) with default value off (no)'
        );
        $values = $q->getValues();
        $this->assertEquals('yes', $values['f1_value'], 'f1_value is yes');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testExactYesDefaultYesFilter()
    {
        $q = (new \EntryQuery($this->db));
        $this->defaultState = 'on';
        $o = $this->createAdapter();
        $o->filter($q, ['yes']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE (`f1`.`value` = :f1_value OR `f1`.`value` IS :_null_)",
            $q->generateSQL(),
            'Exact match ->filter([yes]) with default value on (yes)'
        );
        $values = $q->getValues();
        $this->assertEquals('yes', $values['f1_value'], 'f1_value is yes');
        $this->assertEquals(null, $values['_null_'], '_null_ is NULL');
        $this->assertEquals(2, count($values), '1 value');
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
