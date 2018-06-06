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
        $this->assertEquals(40, strlen($r1));
        $this->assertEquals(40, strlen($r2));
        $this->assertNotEquals($r1, $r2);
    }

    public function test16RandomBytes()
    {
        $length = 16;
        $r1 = \Cryptography::randomBytes($length);
        $r2 = \Cryptography::randomBytes($length);
        $this->assertEquals($length, strlen($r1));
        $this->assertEquals($length, strlen($r2));
        $this->assertNotEquals($r1, $r2);
    }

    /**
     * @expectedException Exception
     */
    public function test15RandomBytes()
    {
        $length = 15;
        $r1 = \Cryptography::randomBytes($length);
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

    public function testHash()
    {
        $this->assertStringStartsWith('PBKDF2v1|sha256|100000|', \Cryptography::hash('test'));
    }

    public function testCompare()
    {
        $this->assertTrue(\Cryptography::compare('test', 'PBKDF2v1|sha256|10000|8cbf91f04f2604380122|OXQB+sx6n4nE14xpyDTdODUZyGROAYBKhFMP7DgqOa3RxLWavSU41w=='));
        $this->assertTrue(\Cryptography::compare('test', 'PBKDF2v1|10000|8cbf91f04f2604380122|OXQB+sx6n4nE14xpyDTdODUZyGROAYBKhFMP7DgqOa3RxLWavSU41w=='));
        $this->assertTrue(\Cryptography::compare('PBKDF2v1|10000|8cbf91f04f2604380122|OXQB+sx6n4nE14xpyDTdODUZyGROAYBKhFMP7DgqOa3RxLWavSU41w==', 'PBKDF2v1|10000|8cbf91f04f2604380122|OXQB+sx6n4nE14xpyDTdODUZyGROAYBKhFMP7DgqOa3RxLWavSU41w==', true));
        $this->assertTrue(\Cryptography::compare('PBKDF2v1|sha256|10000|8cbf91f04f2604380122|OXQB+sx6n4nE14xpyDTdODUZyGROAYBKhFMP7DgqOa3RxLWavSU41w==', 'PBKDF2v1|sha256|10000|8cbf91f04f2604380122|OXQB+sx6n4nE14xpyDTdODUZyGROAYBKhFMP7DgqOa3RxLWavSU41w==', true));
        $this->assertFalse(\Cryptography::compare('PBKDF2v1|sha256|10000|8cbf91f04f2604380122|OXQB+sx6n4nE14xpyDTdODUZyGROAYBKhFMP7DgqOa3RxLWavSU41w==', '', true));
        $this->assertFalse(\Cryptography::compare(null, 'PBKDF2v1|sha256|10000|8cbf91f04f2604380122|OXQB+sx6n4nE14xpyDTdODUZyGROAYBKhFMP7DgqOa3RxLWavSU41w==', true));
        $this->assertFalse(\Cryptography::compare(null, null, true));
        $this->assertFalse(\Cryptography::compare(null, null, false));
        $this->assertTrue(\Cryptography::compare('test', 'test', true));
        $this->assertFalse(\Cryptography::compare('test', 'test', false));
    }

    public function testRequiresMigration()
    {
        $this->assertTrue(\Cryptography::requiresMigration('PBKDF2v1|sha256|10000|8cbf91f04f2604380122|OXQB+sx6n4nE14xpyDTdODUZyGROAYBKhFMP7DgqOa3RxLWavSU41w=='));
        $this->assertTrue(\Cryptography::requiresMigration('PBKDF2v1|10000|8cbf91f04f2604380122|OXQB+sx6n4nE14xpyDTdODUZyGROAYBKhFMP7DgqOa3RxLWavSU41w=='));
        $this->assertFalse(\Cryptography::requiresMigration('PBKDF2v1|sha256|100000|this is 20 char salt|' . base64_encode('this is a hash xxxxxxxxxxxxxxxxxxxxxxxxx')));
        $this->assertTrue(\Cryptography::requiresMigration(null));
        $this->assertTrue(\Cryptography::requiresMigration(''));
        $this->assertTrue(\Cryptography::requiresMigration('test'));
    }
}
