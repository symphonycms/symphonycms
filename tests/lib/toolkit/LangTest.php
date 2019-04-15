<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers Lang
 */
final class LangTest extends TestCase
{
    /*
     * \Lang::createHandle() tests
     */
    public function testCreateHandleDefaultValues()
    {
        $this->assertEquals('test', \Lang::createHandle('test'));
        $this->assertEquals('this-is-a-test', \Lang::createHandle('This is a test'));
        $this->assertEquals('this-is-a-test', \Lang::createHandle('This    is---a    test'));
        $this->assertEquals('this-is-a-pooh-emoji', \Lang::createHandle('This is a 	💩 pooh emoji'));
        $this->assertEquals('this-is-a-test-test', \Lang::createHandle('- This ,   is-,-a. ! test   test '));
    }

    public function testCreateLoooooooongHandleDefaultValues()
    {
        $longHandle = \Lang::createHandle('TThis is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test');
        $this->assertEquals(
            'tthis-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test',
            $longHandle
        );
        $this->assertEquals(255, strlen($longHandle));

        $longHandleWithDelimAtThenEnd = \Lang::createHandle('This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test-This is a test');
        $this->assertEquals(
            'this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test-this-is-a-test',
            $longHandleWithDelimAtThenEnd
        );
        $this->assertEquals(254, strlen($longHandleWithDelimAtThenEnd));
    }

    public function testCreateShortHandleShortLimit()
    {
        $this->assertEquals('r', \Lang::createHandle('r', 20));
        $this->assertEquals('this', \Lang::createHandle('This is a test', 5));
        $this->assertEquals('this-is', \Lang::createHandle('This    is---a    test', 7));
    }

    public function testCreateHandleCustomDelimiter()
    {
        $this->assertEquals('this_is_a', \Lang::createHandle('This is a test', 10, '_'));
        $this->assertEquals('this@is', \Lang::createHandle('This    is---a    test', 7, '@'));
        $this->assertEquals('this&is', \Lang::createHandle('This    is---a    test', 7, '&'));
    }


    public function testCreateHandleUriEncode()
    {
        $this->assertEquals('this_is_a', \Lang::createHandle('This is a test', 10, '_', true));
        $this->assertEquals('this+is+a', \Lang::createHandle('This is a test', 10, ' ', true));
        $this->assertEquals('this%2Bis%2Ba', \Lang::createHandle('This is a test', 10, '+', true));
    }

    public function testCreateHandleCustomRules()
    {
        $this->assertEquals('that+is+a+', \Lang::createHandle('This is a test', 10, '_', false, false, [
            '/_/' => '+',
            '/this/i' => 'That',
        ]));
    }

    public function testCreateHandleWithTransliterations()
    {
        $this->assertEquals('this_is_a', \Lang::createHandle('This is a test', 10, '_', false, true));
        $this->assertEquals('this_is_a', \Lang::createHandle('This ïs à test', 10, '_', false, true));
        $this->assertEquals('this-is-and-test', \Lang::createHandle('This is & test', 17, '-', false, true));
        // Non-breaking space
        $this->assertEquals('this_is_a', \Lang::createHandle('This is a test', 10, '_', false, true));
        $this->assertEquals('this_is_a', \Lang::createHandle('This is a test', 10, '_', false, false));
    }
}
