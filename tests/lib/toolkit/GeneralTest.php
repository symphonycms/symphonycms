<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers General
 */
final class GeneralTest extends TestCase
{
    /*
     * \General::intval() tests
     */

    public function testIntval()
    {
        $this->assertEquals(0, \General::intval(0));
        $this->assertEquals(1, \General::intval(1));
        $this->assertEquals(1000, \General::intval(1000));
        if (defined('PHP_INT_MIN')) {
            $this->assertEquals(PHP_INT_MIN, \General::intval(PHP_INT_MIN));
        }
        if (defined('PHP_INT_MAX')) {
            $this->assertEquals(PHP_INT_MAX, \General::intval(PHP_INT_MAX));
        }
        if (defined('PHP_INT_MAX') && defined('PHP_INT_MIN')) {
            $this->assertEquals(PHP_INT_MIN, \General::intval(PHP_INT_MAX + 1));
        }
        $this->assertEquals(1, \General::intval('1'));
        $this->assertEquals(-1, \General::intval(-1));
        $this->assertEquals(-1, \General::intval('-1'));
        $this->assertEquals(-1, \General::intval('-10'));
        $this->assertEquals(1, \General::intval(1.0));
        $this->assertEquals(-1, \General::intval('1.0'));
        $this->assertEquals(-1, \General::intval('this10'));
        $this->assertEquals(-1, \General::intval('10this'));
        $this->assertEquals(-1, \General::intval('this10this'));
        $this->assertEquals(-1, \General::intval([]));
        $this->assertEquals(-1, \General::intval(null));
        $this->assertEquals(-1, \General::intval(true));
        $this->assertEquals(-1, \General::intval(false));
        $this->assertEquals(-1, \General::intval(new \stdClass));
    }

    /*
     * \General::limitWords() tests
     */

    public function testLimitWordWithSpacesOnly()
    {
        $limit = 22;
        $limited = \General::limitWords(
            'This is a very very very long string with nothing but chars and spaces',
            $limit
        );
        $this->assertEquals('This is a very very', $limited);
        $this->assertLessThanOrEqual($limit, strlen($limited));
    }

    public function testLimitWordWithSinglePunctuation()
    {
        $limit = 22;
        $limited = \General::limitWords(
            'This is a very-very-very very long string with nothing but chars and spaces',
            $limit
        );
        $this->assertEquals('This is a very-very', $limited);
        $this->assertLessThanOrEqual($limit, strlen($limited));
    }

    public function testLimitWordWithMultiplePunctuation()
    {
        $limit = 26;
        $limited = \General::limitWords(
            'This is a very! very. very?very long string with nothing but chars and spaces',
            $limit
        );
        $this->assertEquals('This is a very! very. very', $limited);
        $this->assertLessThanOrEqual($limit, strlen($limited));
    }

    public function testLimitWordWithHyphenOnly()
    {
        $limit = 22;
        $limited = \General::limitWords(
            'This-is-a-very-very-very-long-string-with-nothing-but-chars-and-spaces',
            $limit
        );
        $this->assertEquals('This-is-a-very-very', $limited);
        $this->assertLessThanOrEqual($limit, strlen($limited));
    }
}
