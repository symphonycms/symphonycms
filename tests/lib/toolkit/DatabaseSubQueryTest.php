<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseSubQuery
 */
final class DatabaseSubQueryTest extends TestCase
{
    public function testFormatParameterName()
    {
        $db = new Database([]);
        $sql = new DatabaseSubQuery($db, 4);
        $class = new ReflectionClass($sql);
        $method = $class->getMethod('formatParameterName');
        $method->setAccessible(true);
        $this->assertEquals('i4_x', $method->invokeArgs($sql, ['x']));
    }
}
