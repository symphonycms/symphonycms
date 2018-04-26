<?php

namespace Symphony\Crypto\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers Cryptography
 */
final class CryptographyTest extends TestCase
{
    public function testDefaultRandomBytes()
    {
        $r1 = \Cryptography::randomBytes();
        $r2 = \Cryptography::randomBytes();
        $this->assertEquals(20, strlen($r1));
        $this->assertEquals(20, strlen($r2));
        $this->assertNotEquals($r1, $r2);
    }

    public function test32RandomBytes()
    {
        $length = 32;
        $r1 = \Cryptography::randomBytes($length);
        $r2 = \Cryptography::randomBytes($length);
        $this->assertEquals($length, strlen($r1));
        $this->assertEquals($length, strlen($r2));
        $this->assertNotEquals($r1, $r2);
    }

    public function testGenerateSalt()
    {
        $length = 32;
        $r1 = \Cryptography::generateSalt($length);
        $r2 = \Cryptography::generateSalt($length);
        $this->assertEquals($length, strlen($r1));
        $this->assertEquals($length, strlen($r2));
        $this->assertNotEquals($r1, $r2);
    }
}
