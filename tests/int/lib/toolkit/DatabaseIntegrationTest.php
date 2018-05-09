<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers Database
 */
final class DatabaseIntegrationTest extends TestCase
{
    public function testIsConnected()
    {
        $this->assertTrue(Symphony::Database()->isConnected());
    }

    public function testSetTimezone()
    {
        $this->assertTrue(Symphony::Database()->setTimeZone('utc'));
    }

    public function testTableContainsField()
    {
        $this->assertTrue(Symphony::Database()->tableContainsField('tbl_pages', 'id'));
        $this->assertTrue(Symphony::Database()->tableContainsField('tbl_sections', 'id'));
        $this->assertFalse(Symphony::Database()->tableContainsField('tbl_sections', 'not-found'));
    }

    public function testTableExists()
    {
        $this->assertTrue(Symphony::Database()->tableExists('tbl_pages'));
        $this->assertTrue(Symphony::Database()->tableExists('tbl_sections'));
        $this->assertFalse(Symphony::Database()->tableExists('tbl-not-found'));
    }

    public function testGetStatistics()
    {
        $this->assertTrue(is_array(Symphony::Database()->getStatistics()));
    }
}
