<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers EntryQueryInputAdapter
 */
final class EntryQueryInputAdapterTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    /**
     * @param Field $f
     * @return EntryQueryInputAdapter
     */
    private function createAdapter(Field $f = null)
    {
        if (!$f) {
            $f = new \Field();
            $f->set('id', 1);
        }
        return new \EntryQueryInputAdapter($f);
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
}
