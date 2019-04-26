<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers EntryQuery
 */
final class EntryQueryIntegrationTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = Symphony::Database();
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSortInvalid()
    {
        $q = (new \EntryQuery($this->db))->sort('<invalid>');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with ->sort(<invalid>)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
