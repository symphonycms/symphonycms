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
        $this->db = $this->createMock(Database::class);
        $this->db
            ->method('beginTransaction')
            ->willReturn(true);
        $this->db
            ->method('commit')
            ->willReturn(true);
        $this->db
            ->method('rollBack')
            ->willReturn(true);
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
}
