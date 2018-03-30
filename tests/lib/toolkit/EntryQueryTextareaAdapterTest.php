<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers EntryQueryTextareaAdapter
 */
final class EntryQueryTextareaAdapterTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    /**
     * @param Field $f
     * @return EntryQueryTextareaAdapter
     */
    private function createAdapter(Field $f = null)
    {
        if (!$f) {
            $f = new \Field();
            $f->set('id', 1);
        }
        return new \EntryQueryTextareaAdapter($f);
    }

    public function testExactFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['test']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE MATCH (`f1`.`value`) AGAINST (:f1_value IN BOOLEAN MODE)",
            $q->generateSQL(),
            'Simple exact match ->filter([test])'
        );
        $values = $q->getValues();
        $this->assertEquals('test', $values['f1_value'], 'f1_value is test');
        $this->assertEquals(1, count($values), '1 value');
    }
}
