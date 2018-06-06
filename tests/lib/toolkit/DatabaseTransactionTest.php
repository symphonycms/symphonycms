<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseTransaction
 */
final class DatabaseTransactionTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = $this->createMockDatabase();
    }

    private function createMockDatabase($beginReturn = true)
    {
        $db = $this->createMock(Database::class);
        $db->method('beginTransaction')->willReturn($beginReturn);
        $db->method('commit')->willReturn(true);
        $db->method('rollBack')->willReturn(true);
        return $db;
    }

    public function testExecute()
    {
        $called = false;
        $tx = function (\Database $db) use (&$called) {
            $called = true;
        };
        $tx = new DatabaseTransaction($this->db, $tx);
        $ret = $tx->execute();
        $this->assertEquals(true, $ret->success(), 'The transaction is successful');
        $this->assertEquals(true, $called, 'The transaction callable was called');
    }

    /**
     * @expectedException Exception
     */
    public function testRollback()
    {
        $tx = function () {
            throw new Exception('Crashed!');
        };
        $tx = new DatabaseTransaction($this->db, $tx);
        $ret = $tx->execute();
        $this->assertEquals(false, $ret->success(), 'The transaction is unsuccessful');
    }

    /**
     * @expectedException DatabaseTransactionException
     */
    public function testNotCallable()
    {
        $tx = new DatabaseTransaction($this->db, 1);
    }

    /**
     * @expectedException DatabaseTransactionException
     */
    public function testExecuteBeginFailed()
    {
        $called = false;
        $tx = function (\Database $db) use (&$called) {
            $called = true;
        };
        $db = $this->createMockDatabase(false);
        $tx = new DatabaseTransaction($db, $tx);
        $ret = $tx->execute();
    }
}
