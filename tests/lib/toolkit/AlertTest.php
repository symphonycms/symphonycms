<?php

namespace Symphony\Toolkit\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers Alert
 */
final class AlertTest extends TestCase
{
    public function testDefaultValues()
    {
        $a = new \Alert('test');
        $this->assertEquals('test', $a->message);
        $this->assertEquals('notice', $a->type);
    }

    public function testGetters()
    {
        $a = new \Alert('test', 'test');
        $this->assertEquals('test', $a->message);
        $this->assertEquals('test', $a->type);
    }

    public function testSetters()
    {
        $a = new \Alert('test', 'test');
        $a->message = 't';
        $a->type = 'tt';
        $this->assertEquals('t', $a->message);
        $this->assertEquals('tt', $a->type);
    }

    public function testIsset()
    {
        $a = new \Alert('test');
        $this->assertTrue(isset($a->message));
        $this->assertTrue(isset($a->type));
        $this->assertFalse(isset($a->undefined));
    }

    public function testAsXml()
    {
        $a = new \Alert('test', 'test');
        $this->assertEquals('<p role="alert" class="notice test">test</p>', $a->asXML()->generate());
    }
}
