<?php

require './tests/lib/toolkit/DatabaseTest.php';

/**
 * @covers Database
 */
final class DatabaseIntegrationTest extends DatabaseTest
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = Symphony::Database();
    }

    public function testIsConnected()
    {
        $this->assertTrue($this->db->isConnected());
    }

    public function testSetTimezone()
    {
        $this->assertTrue($this->db->setTimeZone('utc'));
    }

    public function testGetLogs()
    {
        $this->assertTrue(is_array($this->db->getLogs()));
        $this->assertNotEmpty($this->db->getLogs());
    }

    public function testGetStatistics()
    {
        $stats = $this->db->getStatistics();
        $this->assertTrue(is_array($stats));
        $this->assertNotEmpty($stats);
        $this->assertTrue(is_array($stats['slow-queries']));
        $this->assertEmpty($stats['slow-queries']);
        $this->assertGreaterThan(0, $stats['queries']);
        $this->assertGreaterThan(0, floatval($stats['total-query-time']));
    }

    public function testQueryCount()
    {
        $this->assertGreaterThan(0, $this->db->queryCount());
    }

    public function testTableContainsField()
    {
        $this->assertTrue($this->db->tableContainsField('tbl_pages', 'id'));
        $this->assertTrue($this->db->tableContainsField('tbl_sections', 'id'));
        $this->assertFalse($this->db->tableContainsField('tbl_sections', 'not-found'));
    }

    public function testTableExists()
    {
        $this->assertTrue($this->db->tableExists('tbl_pages'));
        $this->assertTrue($this->db->tableExists('tbl_sections'));
        $this->assertFalse($this->db->tableExists('tbl-not-found'));
    }

    public function testGetVersion()
    {
        $this->assertNotEmpty($this->db->getVersion());
    }
}
