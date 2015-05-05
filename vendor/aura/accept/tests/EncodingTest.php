<?php
namespace Aura\Accept;

class EncodingTest extends AcceptTestCase
{
    protected function newEncoding($server = array())
    {
        return new Encoding\EncodingNegotiator(new ValueFactory, $server);
    }

    /**
     * @dataProvider encodingProvider
     * @param $accept
     * @param $expect
     * @param $negotiator_class
     * @param $value_class
     */
    public function testGetEncoding($server, $expect, $negotiator_class, $value_class)
    {
        $encoding = $this->newEncoding($server);
        $this->assertAcceptValues($encoding, $expect, $negotiator_class, $value_class);
    }

    /**
     * @dataProvider encodingNegotiateProvider
     * @param $accept
     * @param $available
     * @param $expected
     */
    public function testGetEncoding_negotiate($server, $available, $expected)
    {
        $encoding = $this->newEncoding($server);
        $actual = $encoding->negotiate($available);

        if ($expected === false) {
            $this->assertFalse($actual);
        } else {
            $this->assertInstanceOf('Aura\Accept\Encoding\EncodingValue', $actual->available);
            $this->assertSame($expected, $actual->available->getValue());
        }
    }

    public function encodingProvider()
    {
        return array(
            array(
                'server' => array('HTTP_ACCEPT_ENCODING' => 'compress;q=0.5, gzip;q=1.0'),
                'expect' => array(
                    array('value' => 'gzip', 'quality' => 1.0),
                    array('value' => 'compress', 'quality' => 0.5)
                ),
                'negotiator_class' => 'Aura\Accept\Encoding\EncodingNegotiator',
                'value_class' => 'Aura\Accept\Encoding\EncodingValue',
            )
        );
    }

    public function encodingNegotiateProvider()
    {
        return array(
            array(
                'server' => array('HTTP_ACCEPT_ENCODING' => 'gzip, compress, *',),
                'available' => array(),
                'expected' => false,
            ),
            array(
                'server' => array('HTTP_ACCEPT_ENCODING' => 'gzip, compress, *'),
                'available' => array('foo', 'bar'),
                'expected' => 'foo',
            ),
            array(
                'server' => array('HTTP_ACCEPT_ENCODING' => 'gzip, compress, *',),
                'available' => array('foo', 'GZIP'),
                'expected' => 'GZIP',
            ),
            array(
                'server' => array('HTTP_ACCEPT_ENCODING' => 'gzip, compress, *',),
                'available' => array('gzip', 'compress'),
                'expected' => 'gzip',
            ),
            array(
                'server' => array('HTTP_ACCEPT_ENCODING' => 'gzip, compress, foo;q=0'),
                'available' => array('foo'),
                'expected' => false,
            ),
            array(
                'server' => array('HTTP_ACCEPT_ENCODING' => 'gzip, compress, foo;q=0'),
                'available' => array('*'),
                'expected' => '*',
            ),
        );
    }
}
