<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers Database
 */
class DatabaseTest extends TestCase
{
    protected $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database([]);
    }

    public function testSetTimezoneToNull()
    {
        $this->assertTrue($this->db->setTimeZone());
        $this->assertTrue($this->db->setTimeZone(null));
    }

    public function testGetLogs()
    {
        $this->assertTrue(is_array($this->db->getLogs()));
        $this->assertEmpty($this->db->getLogs());
    }

    public function testGetStatistics()
    {
        $stats = $this->db->getStatistics();
        $this->assertTrue(is_array($stats));
        $this->assertNotEmpty($stats);
        $this->assertTrue(is_array($stats['slow-queries']));
        $this->assertEmpty($stats['slow-queries']);
        $this->assertEquals(0, $stats['queries']);
        $this->assertEquals('0.00000', $stats['total-query-time']);
    }

    public function testGetDSN_TCP()
    {
        $db = new Database([
            'host' => '127.0.0.1',
            'port' => '3306',
            'db' => 'test',
            'driver' => 'mysql',
            'charset' => 'utf8mb4',
        ]);

        $this->assertEquals('mysql:dbname=test;host=127.0.0.1;port=3306;charset=utf8mb4', $db->getDSN());
    }

    public function testGetDSN_UNIXSOCK()
    {
        $db = new Database([
            'host' => 'unix_socket',
            'port' => '3306',
            'db' => 'test',
            'driver' => 'mysql',
            'charset' => 'utf8mb4',
        ]);

        $this->assertEquals('mysql:unix_socket=;dbname=test;charset=utf8mb4', $db->getDSN());

        $db = new Database([
            'host' => 'unix_socket',
            'port' => '/var/tmp/mysql.sock',
            'db' => 'test',
            'driver' => 'mysql',
            'charset' => 'utf8mb4',
        ]);

        $this->assertEquals('mysql:unix_socket=/var/tmp/mysql.sock;dbname=test;charset=utf8mb4', $db->getDSN());
    }

    public function testQueryCount()
    {
        $this->assertEquals(0, $this->db->queryCount());
    }

    public function testCaching()
    {
        $db = new Database();
        $this->assertFalse($db->isCachingEnabled());
        $db = new Database([
            'query_caching' => 'on'
        ]);
        $this->assertTrue($db->isCachingEnabled());
        $this->assertEquals($db, $db->disableCaching());
        $this->assertFalse($db->isCachingEnabled());
        $this->assertEquals($db, $db->enableCaching());
        $this->assertTrue($db->isCachingEnabled());
    }

    public function testPrefix()
    {
        $db = new Database();
        $this->assertNull($db->getPrefix());
        $db->setPrefix('tbl_');
        $this->assertEquals('tbl_', $db->getPrefix());
        $db = new Database([
            'tbl_prefix' => 'sym_'
        ]);
        $this->assertEquals('sym_', $db->getPrefix());
    }

    public function testLogging()
    {
        $db = new Database();
        $this->assertFalse($db->isLoggingEnabled());
        $db = new Database([
            'query_logging' => 'on'
        ]);
        $this->assertTrue($db->isLoggingEnabled());
        $this->assertEquals($db, $db->disableLogging());
        $this->assertFalse($db->isLoggingEnabled());
        $this->assertEquals($db, $db->enableLogging());
        $this->assertTrue($db->isLoggingEnabled());
    }

    public function testDeducePDOParamType()
    {
        $this->assertEquals(\PDO::PARAM_STR, $this->db->deducePDOParamType(''), "''");
        $this->assertEquals(\PDO::PARAM_STR, $this->db->deducePDOParamType('test'), "'test'");
        $this->assertEquals(\PDO::PARAM_STR, $this->db->deducePDOParamType('0004543'), "'0004543'");
        $this->assertEquals(\PDO::PARAM_INT, $this->db->deducePDOParamType(0004543), '0004543');
        $this->assertEquals(\PDO::PARAM_INT, $this->db->deducePDOParamType(0004543.000), '0004543.000');
        $this->assertEquals(\PDO::PARAM_STR, $this->db->deducePDOParamType(0004543.001), '0004543.001');
        $this->assertEquals(\PDO::PARAM_STR, $this->db->deducePDOParamType([]), '[]');
        $this->assertEquals(\PDO::PARAM_STR, $this->db->deducePDOParamType($this), '$this');
        $this->assertEquals(\PDO::PARAM_INT, $this->db->deducePDOParamType(0), '0');
        $this->assertEquals(\PDO::PARAM_INT, $this->db->deducePDOParamType(1), '1');
        if (!defined('SYM_DISABLE_INT_OVERFLOW_TEST') && !getenv('SYM_DISABLE_INT_OVERFLOW_TEST')) {
            $this->assertEquals(\PDO::PARAM_INT, $this->db->deducePDOParamType(2 ** 31), '2 ** 31');
            $this->assertEquals(\PDO::PARAM_INT, $this->db->deducePDOParamType(2 ** 32), '2 ** 32');
            $this->assertEquals(\PDO::PARAM_INT, $this->db->deducePDOParamType(2 ** 52), '2 ** 52');
            $this->assertEquals(\PDO::PARAM_INT, $this->db->deducePDOParamType(2 ** 62), '2 ** 62');
            $this->assertEquals(\PDO::PARAM_STR, $this->db->deducePDOParamType(2 ** 63), '2 ** 63 int overflow');
        }
        $this->assertEquals(\PDO::PARAM_INT, $this->db->deducePDOParamType(-1), '-1');
        $this->assertEquals(\PDO::PARAM_STR, $this->db->deducePDOParamType(1.00001), '1.00001');
        $this->assertEquals(\PDO::PARAM_NULL, $this->db->deducePDOParamType(null), 'null');
        $this->assertEquals(\PDO::PARAM_BOOL, $this->db->deducePDOParamType(true), 'true');
        $this->assertEquals(\PDO::PARAM_BOOL, $this->db->deducePDOParamType(false), 'false');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testValidateSQLQueryCommonInjection()
    {
        $this->db->validateSQLQuery(' \'--; ', true);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testValidateSQLQueryComments()
    {
        $this->db->validateSQLQuery('--', true);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testValidateSQLQuerySingleQuote()
    {
        $this->db->validateSQLQuery('\'', true);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testValidateSQLQuerySemiColon()
    {
        $this->db->validateSQLQuery(';', true);
    }


    public function testStatement()
    {
        $this->assertInstanceOf('\DatabaseStatement', $this->db->statement());
        $this->assertInstanceOf('\DatabaseStatement', $this->db->statement('TEST'));
    }

    public function testSelect()
    {
        $this->assertInstanceOf('\DatabaseQuery', $this->db->select());
        $this->assertInstanceOf('\DatabaseQuery', $this->db->select(['*']));
    }

    public function testShow()
    {
        $this->assertInstanceOf('\DatabaseShow', $this->db->show());
        $this->assertInstanceOf('\DatabaseShow', $this->db->showColumns());
        $this->assertInstanceOf('\DatabaseShow', $this->db->showIndex());
    }

    public function testInsert()
    {
        $this->assertInstanceOf('\DatabaseInsert', $this->db->insert('TEST'));
    }

    public function testUpdate()
    {
        $this->assertInstanceOf('\DatabaseUpdate', $this->db->update('TEST'));
    }

    public function testDelete()
    {
        $this->assertInstanceOf('\DatabaseDelete', $this->db->delete('TEST'));
    }

    public function testDrop()
    {
        $this->assertInstanceOf('\DatabaseDrop', $this->db->drop('TEST'));
    }

    public function testDescribe()
    {
        $this->assertInstanceOf('\DatabaseDescribe', $this->db->describe('TEST'));
    }

    public function testCreate()
    {
        $this->assertInstanceOf('\DatabaseCreate', $this->db->create('TEST'));
    }

    public function testAlter()
    {
        $this->assertInstanceOf('\DatabaseAlter', $this->db->alter('TEST'));
    }

    public function testOptimize()
    {
        $this->assertInstanceOf('\DatabaseOptimize', $this->db->optimize('TEST'));
    }

    public function testTruncate()
    {
        $this->assertInstanceOf('\DatabaseTruncate', $this->db->truncate('TEST'));
    }

    public function testTransaction()
    {
        $this->assertInstanceOf('\DatabaseTransaction', $this->db->transaction(function ($db) {
            // noop
        }));
    }
}
