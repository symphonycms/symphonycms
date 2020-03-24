<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseStatementCachedResult
 * @covers DatabaseCache
 */
final class DatabaseStatementCachedResultTest extends TestCase
{
    protected function createResultTestObject()
    {
        $dc = new DatabaseCache;
        $testKey = 'testkey';
        $dc->append($testKey, [1, 2, 3]);
        $dc->append($testKey, [4, 5, 6]);
        $dc->appendAll($testKey, [[7, 8, 9]]);
        return new DatabaseStatementCachedResult(null, $dc, $testKey);
    }

    public function testCachedNext()
    {
        $dscr = $this->createResultTestObject();
        $this->assertEquals([1, 2, 3], $dscr->next());
        $this->assertEquals([4, 5, 6], $dscr->next());
        $this->assertEquals([7, 8, 9], $dscr->next());
    }

    public function testCachedRows()
    {
        $dscr = $this->createResultTestObject();
        $this->assertEquals([[1, 2, 3], [4, 5, 6], [7, 8, 9]], $dscr->rows());
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testCachedConsumedRows()
    {
        $dscr = $this->createResultTestObject();
        // Increment position
        $dscr->next();
        // This throws, a rows as been consumed
        $dscr->rows();
    }

    public function testCachedRemainingRows()
    {
        $dscr = $this->createResultTestObject();
        // Increment position
        $dscr->next();
        // Safe to get "the rest"
        $this->assertEquals([[4, 5, 6], [7, 8, 9]], $dscr->remainingRows());
    }

    public function testColumnCount()
    {
        $dscr = $this->createResultTestObject();
        $this->assertEquals(3, $dscr->columnCount());
    }
}
