<?php
namespace Aura\Accept;

class CharsetTest extends AcceptTestCase
{
    protected $charset;

    protected function newCharset($server = array())
    {
        return new Charset\CharsetNegotiator(new ValueFactory, $server);
    }

    /**
     * @dataProvider charsetProvider
     * @param $accept
     * @param $expect
     * @param $negotiator_class
     * @param $value_class
     */
    public function testGetCharset($server, $expect, $negotiator_class, $value_class)
    {
        $charset = $this->newCharset($server);
        $this->assertAcceptValues($charset, $expect, $negotiator_class, $value_class);
    }

    /**
     * @dataProvider charsetNegotiateProvider
     * @param $accept
     * @param $available
     * @param $expected
     */
    public function testGetCharset_negotiate($server, $available, $expected)
    {
        $charset = $this->newCharset($server);
        $actual = $charset->negotiate($available);

        if ($expected === false) {
            $this->assertFalse($expected, $actual);
        } else {
            $this->assertInstanceOf('Aura\Accept\Charset\CharsetValue', $actual->available);
            $this->assertSame($expected, $actual->available->getValue());
        }
    }

    public function charsetProvider()
    {
        return array(
            array(
                'server' => array(
                    'HTTP_ACCEPT_CHARSET' => 'iso-8859-5, unicode-1-1;q=0.8',
                ),
                'expected' => array(
                    array(
                        'value' => 'iso-8859-5',
                        'quality' => 1.0,
                    ),
                    array(
                        'value' => 'ISO-8859-1',
                        'quality' => 1.0,
                    ),
                    array(
                        'value' => 'unicode-1-1',
                        'quality' => 0.8,
                    ),
                ),
                'negotiator_class' => 'Aura\Accept\Charset\CharsetNegotiator',
                'value_class' => 'Aura\Accept\Charset\CharsetValue',
            )
        );
    }

    public function charsetNegotiateProvider()
    {
        return array(
            array(
                'server' => array('HTTP_ACCEPT_CHARSET' => 'iso-8859-5, unicode-1-1, *'),
                'available' => array(),
                'expected' => false,
            ),
            array(
                'server' => array('HTTP_ACCEPT_CHARSET' => 'iso-8859-5, unicode-1-1, *'),
                'available' => array('foo', 'bar'),
                'expected' => 'foo'
            ),
            array(
                'server' => array('HTTP_ACCEPT_CHARSET' => 'iso-8859-5, unicode-1-1, *'),
                'available' => array('foo', 'UniCode-1-1'),
                'expected' => 'UniCode-1-1'
            ),
            array(
                'server' => array(),
                'available' => array('ISO-8859-5', 'foo'),
                'expected' => 'ISO-8859-5'
            ),
            array(
                'server' => array('HTTP_ACCEPT_CHARSET' => 'ISO-8859-1, baz;q=0'),
                'available' => array('baz'),
                'expected' => false
            ),
            array(
                'server' => array('HTTP_ACCEPT_CHARSET' => 'iso-8859-5, unicode-1-1'),
                'available' => array('*'),
                'expected' => '*'
            ),
        );
    }
}
