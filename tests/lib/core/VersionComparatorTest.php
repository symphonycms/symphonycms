<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers VersionComparator
 */
final class VersionComparatorTest extends TestCase
{
    public function testFixVersion()
    {
        $vc = new VersionComparator('');
        $this->assertEquals('1.0.0', $vc->fixVersion('1.0.0'));
        $this->assertEquals('1.0.*', $vc->fixVersion('1.0.*'));
        $this->assertEquals('1.0.999999999', $vc->fixVersion('1.0.x'));
        $this->assertEquals('1.*.*', $vc->fixVersion('1.*.*'));
        $this->assertEquals('1.999999999.999999999', $vc->fixVersion('1.x.x'));
    }

    public function testLessThan()
    {
        $vc = new VersionComparator('1.0.0');
        $this->assertTrue($vc->lessThan('1.0.1'));
        $this->assertFalse($vc->lessThan('1.0.0'));
        $this->assertTrue($vc->lessThan('1.0.x'));
        $this->assertTrue($vc->lessThan('2.7.x'));
        $this->assertTrue($vc->lessThan('1.x.x'));
        $this->assertFalse($vc->lessThan('0.999'));
        $vc = new VersionComparator('3.x.x');
        $this->assertFalse($vc->lessThan('2.7.x'));
        $this->assertTrue($vc->lessThan('3.0.0'));
        $vc = new VersionComparator('3.0.0');
        $this->assertFalse($vc->lessThan('2.7.x'));
        $this->assertTrue($vc->lessThan('3.x.x'));
    }

    public function testGreaterThan()
    {
        $vc = new VersionComparator('1.0.0');
        $this->assertFalse($vc->greaterThan('1.0.1'));
        $this->assertFalse($vc->greaterThan('1.0.0'));
        $this->assertFalse($vc->greaterThan('1.0.x'));
        $this->assertFalse($vc->greaterThan('2.7.x'));
        $this->assertFalse($vc->greaterThan('1.x.x'));
        $this->assertTrue($vc->greaterThan('0.999'));
        $vc = new VersionComparator('3.x.x');
        $this->assertTrue($vc->greaterThan('2.7.x'));
        $this->assertFalse($vc->greaterThan('3.0.0'));
        $vc = new VersionComparator('3.0.0');
        $this->assertTrue($vc->greaterThan('2.7.x'));
        $this->assertFalse($vc->greaterThan('3.x.x'));
    }

    public function testCompare()
    {
        $this->assertEquals(0, VersionComparator::compare('1.0.0', '1.0.0'));
        $this->assertEquals(-1, VersionComparator::compare('1.0.0', '1.0.1'));
        $this->assertEquals(-1, VersionComparator::compare('1.0.0', '1.1.0'));
        $this->assertEquals(-1, VersionComparator::compare('1.0.0', '2.0.0'));
        $this->assertEquals(-1, VersionComparator::compare('1.0.0', '1.0.x'));
        $this->assertEquals(-1, VersionComparator::compare('1.0.0', '1.x.0'));
        $this->assertEquals(-1, VersionComparator::compare('1.0.0', '2.x.x'));
        $this->assertEquals(1, VersionComparator::compare('1.0.1', '1.0.0'));
        $this->assertEquals(1, VersionComparator::compare('1.1.0', '1.0.0'));
        $this->assertEquals(1, VersionComparator::compare('2.0.0', '1.0.0'));
        $this->assertEquals(-1, VersionComparator::compare('1.0.x', '1.0.0'));
        $this->assertEquals(-1, VersionComparator::compare('1.x.0', '1.0.0'));
        $this->assertEquals(1, VersionComparator::compare('2.x.x', '1.0.0'));
    }
}
