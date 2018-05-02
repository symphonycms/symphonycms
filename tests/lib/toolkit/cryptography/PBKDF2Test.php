<?php

namespace Symphony\Crypto\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers PBKDF2
 */
final class PBKDF2Test extends TestCase
{
    public function testHash()
    {
        $this->assertEquals('PBKDF2v1|sha256|1000|salt|uV3lj3ZG2jst5kRms0KSRIha3aw=', \PBKDF2::hash('test', [
            'algorithm' => 'sha256',
            'salt' => 'salt',
            'keylength' => 20,
            'iterations' => 1000,
        ]));
    }

    public function testCompare()
    {
        $this->assertTrue(\PBKDF2::compare('test', 'PBKDF2v1|sha256|1000|salt|uV3lj3ZG2jst5kRms0KSRIha3aw='));
        $this->assertTrue(\PBKDF2::compare('test', 'PBKDF2v1|1000|salt|uV3lj3ZG2jst5kRms0KSRIha3aw='));
        $this->assertFalse(\PBKDF2::compare('test', 'PBKDF2v1|sha256|100|salt|uV3lj3ZG2jst5kRms0KSRIha3aw='));
        $this->assertFalse(\PBKDF2::compare('tesu', 'PBKDF2v1|sha256|1000|salt|uV3lj3ZG2jst5kRms0KSRIha3aw='));
    }
}
