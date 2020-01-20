<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers ArraySerializer
 */
final class ArraySerializerTest extends TestCase
{
    public function testSimpleSerialize()
    {
        $string = (new ArraySerializer([
            'test' => [
                'test1' => 1,
                'test2' => true,
                'test3' => 'true',
                'test4' => [1,2,3],
            ],
            'test2' => [
                'test1' => 0,
                'test2' => false,
                'test3' => 'false',
                'test4' => [],
            ],
        ]))->serialize();
        $expected = <<<EOT
array(


        ###### TEST ######
        'test' => array(
            'test1' => 1,
            'test2' => true,
            'test3' => 'true',
            'test4' => array(
                 0 => 1,
                 1 => 2,
                 2 => 3,
            ),
        ),
        ########


        ###### TEST2 ######
        'test2' => array(
            'test1' => 0,
            'test2' => null,
            'test3' => 'false',
            'test4' => array(),
        ),
        ########
    )
EOT;
        // This is needed to convert the source code (which is in LF only)
        // into the proper value of PHP_EOL
        $expected = str_replace("\n", PHP_EOL, $expected);
        // TODO: There is currently two known bugs:
        //  1. false gets serialized as null
        //  2. numeric keys indentation is offset-ed by 1
        $this->assertEquals($expected, $string);
    }

    public function testasPHPFile()
    {
        $string = (new ArraySerializer([
            'test' => [
                'test1' => 1,
            ],
        ]))->asPHPFile('test');

        $this->assertStringStartsWith('<?php' . PHP_EOL . '    $test = array(', $string);
    }
}
