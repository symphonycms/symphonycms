<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DateRangeParser
 */
final class DateRangeParserTest extends TestCase
{
    public function testSimpleDate()
    {
        $dp = new \DateRangeParser('2018-03-31');
        $d = $dp->parse();
        $this->assertEquals('2018-03-31 00:00:00', $d['start']);
        $this->assertEquals('2018-03-31 23:59:59', $d['end']);
    }

    public function testSimpleDateTime()
    {
        $dp = new \DateRangeParser('2018-03-31 12:00:00');
        $d = $dp->parse();
        $this->assertEquals('2018-03-31 12:00:00', $d['start']);
        $this->assertEquals('2018-03-31 12:00:00', $d['end']);
    }

    public function testFeb2016()
    {
        $dp = new \DateRangeParser('2016-02'); // This is a leap year
        $d = $dp->parse();
        $this->assertEquals('2016-02-01 00:00:00', $d['start']);
        $this->assertEquals('2016-02-29 23:59:59', $d['end']);
    }

    public function testYear()
    {
        $dp = new \DateRangeParser('2018');
        $d = $dp->parse();
        $this->assertEquals('2018-01-01 00:00:00', $d['start']);
        $this->assertEquals('2018-12-31 23:59:59', $d['end']);
    }

    public function test2016toMarch2018WithLimits()
    {
        $dp = new \DateRangeParser('from 2016 to 2018-03');
        $d = $dp->parse();
        $this->assertEquals('2016-01-01 00:00:00', $d['start']);
        $this->assertEquals('2018-03-31 23:59:59', $d['end']);
    }

    public function testRelativeDateNoDirection()
    {
        $dp = new \DateRangeParser('+1 year');
        $d = $dp->parse();
        $this->assertEquals($d['end'], $d['start']);
    }

    public function testEarlierThan2016()
    {
        $dp = new \DateRangeParser('earlier than 2016');
        $d = $dp->parse();
        $this->assertEquals(null, $d['start']);
        $this->assertEquals('2016-01-01 00:00:00', $d['end']);
    }

    public function testEqualOrLaterThan2019()
    {
        $dp = new \DateRangeParser('equal to or later than 2019');
        $d = $dp->parse();
        $this->assertEquals('2019-01-01 00:00:00', $d['start']);
        $this->assertEquals(null, $d['end']);
    }

    public function testLaterThan2018()
    {
        $dp = new \DateRangeParser('later than 2018');
        $d = $dp->parse();
        $this->assertEquals('2018-12-31 23:59:59', $d['start']);
        $this->assertEquals(null, $d['end']);
    }

    public function testLaterThanMarch312018()
    {
        $dp = new \DateRangeParser('later than 2018-03-31');
        $d = $dp->parse();
        $this->assertEquals('2018-03-31 23:59:59', $d['start']);
        $this->assertEquals(null, $d['end']);
    }

    public function testEarlierThan2018()
    {
        $dp = new \DateRangeParser('earlier than 2018');
        $d = $dp->parse();
        $this->assertEquals('2018-01-01 00:00:00', $d['end']);
        $this->assertEquals(null, $d['start']);
    }

    public function testEarlierThanMarch312018()
    {
        $dp = new \DateRangeParser('earlier than 2018-03-31');
        $d = $dp->parse();
        $this->assertEquals('2018-03-31 00:00:00', $d['end']);
        $this->assertEquals(null, $d['start']);
    }
}
