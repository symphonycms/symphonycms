<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseStatementException
 */
final class DatabaseStatementExceptionTest extends TestCase
{
    public function testSql()
    {
        $e = new DatabaseStatementException('MSG');
        $e->sql('SQL >');
        $this->assertEquals('MSG <pre><code>SQL &gt;</code></pre>', $e->getMessage());
    }
}
