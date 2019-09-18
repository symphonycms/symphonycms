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
            $this->assertEquals(-1, \General::intval(PHP_INT_MIN));
        }
        if (defined('PHP_INT_MAX')) {
            $this->assertEquals(PHP_INT_MAX, \General::intval(PHP_INT_MAX));
        }
        if (defined('PHP_INT_MAX') && defined('PHP_INT_MIN')) {
            if (!defined('SYM_DISABLE_INT_OVERFLOW_TEST') && !getenv('SYM_DISABLE_INT_OVERFLOW_TEST')) {
                $this->assertEquals(-1, \General::intval(PHP_INT_MAX + 1));
            }
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

    /*
     * \General::createHandle() tests
     */
    public function testCreateHandleDefaultValues()
    {
        $this->assertEquals('', \General::createHandle(''));
        $this->assertEquals('', \General::createHandle(null));
        $this->assertEquals('', \General::createHandle('      '));
        $this->assertEquals('test', \General::createHandle('test'));
        $this->assertEquals('t-e-st', \General::createHandle("t\re\ns\0t"));
        $this->assertEquals('this-is-a-test', \General::createHandle('This is a test'));
        $this->assertEquals('this-is-a-test', \General::createHandle('This    is---a    test'));
        $this->assertEquals('this-is-a-pooh-emoji', \General::createHandle('This is a 	💩 pooh emoji'));
        $this->assertEquals('this-is-a-test-test', \General::createHandle('- This ,   is-,-a. ! test   test '));
        $this->assertEquals('4255b30b30c7002fc7dacb74523f9516182142ed', \General::createHandle('💩💩💩'));
    }

    public function testCreateLoooooooongHandleDefaultValues()
    {
        $longHandle = \General::createHandle('TThis is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test');
        $this->assertEquals(
            'tthis-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test',
            $longHandle
        );
        $this->assertEquals(255, strlen($longHandle));

        $longHandleWithDelimAtThenEnd = \General::createHandle('This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test');
        $this->assertEquals(
            'this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test',
            $longHandleWithDelimAtThenEnd
        );
        $this->assertEquals(254, strlen($longHandleWithDelimAtThenEnd));
    }

    public function testCreateShortHandleShortLimit()
    {
        $this->assertEquals('r', \General::createHandle('r', 20));
        $this->assertEquals('this', \General::createHandle('This is a test', 5));
        $this->assertEquals('this-is', \General::createHandle('This    is---a    test', 7));
    }

    public function testCreateHandleCustomDelimiter()
    {
        $this->assertEquals('this_is_a', \General::createHandle('This is a test', 10, '_'));
        $this->assertEquals('this@is', \General::createHandle('This    is---a    test', 7, '@'));
        $this->assertEquals('this&is', \General::createHandle('This    is---a    test', 7, '&'));
    }


    public function testCreateHandleUriEncode()
    {
        $this->assertEquals('this_is_a', \General::createHandle('This is a test', 10, '_', true));
        $this->assertEquals('this+is+a', \General::createHandle('This is a test', 10, ' ', true));
        $this->assertEquals('this%2Bis%2Ba', \General::createHandle('This is a test', 10, '+', true));
    }

    public function testCreateHandleCustomRules()
    {
        $this->assertEquals('that+is+a+', \General::createHandle('This is a test', 10, '_', false, [
            '/_/' => '+',
            '/this/i' => 'That',
        ]));
    }

    public function testFlattenArray()
    {
        $flat = ['url-test' => [['test' => 1]]];
        \General::flattenArray($flat);
        $this->assertEquals(1, $flat['url-test.1.test']);

        $flat = array_merge($flat, [
            'another-test' => ['hi!'],
            'other-simple-key' => ['key' => 'value1'],
            'other-multi-key' => ['key' => ['key' => 'value2']],
        ]);
        \General::flattenArray($flat);
        $this->assertEquals('hi!', $flat['another-test.1']);
        $this->assertEquals('value1', $flat['other-simple-key.key']);
        $this->assertEquals('value2', $flat['other-multi-key.key.key']);
    }
}
