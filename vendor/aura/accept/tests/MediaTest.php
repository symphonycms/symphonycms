<?php
namespace Aura\Accept;

class MediaTest extends AcceptTestCase
{
    protected function newMedia($server = array())
    {
        return new Media\MediaNegotiator(new ValueFactory,$server);
    }

    /**
     * @dataProvider mediaProvider
     * @param $accept
     * @param $expect
     * @param $negotiator_class
     * @param $value_class
     */
    public function testGetMedia($server, $expected, $negotiator_class, $value_class)
    {
        $media = $this->newMedia($server);
        $this->assertAcceptValues($media, $expected, $negotiator_class, $value_class);
    }

    /**
     * @dataProvider mediaNegotiateProvider
     * @param $accept
     * @param $available
     * @param $expected_value
     * @param $expected_params
     */
    public function testGetMedia_negotiate($server, $available, $expected_value, $expected_params)
    {
        $media = $this->newMedia($server);
        $actual = $media->negotiate($available);

        if ($expected_value === false) {
            $this->assertFalse($actual);
        } else {
            $this->assertInstanceOf('Aura\Accept\Media\MediaValue', $actual->available);
            $this->assertSame($expected_value, $actual->available->getValue());
            $this->assertSame($expected_params, $actual->available->getParameters());
        }
    }

    public function mediaNegotiateProvider()
    {
        return array(
            array(
                // nothing available
                'server' => array('HTTP_ACCEPT' => 'application/json, application/xml, text/*, */*'),
                'available' => array(),
                'expected_value' => false,
                'expected_params' => array(),
            ),
            array(
                // explicitly accepts */*, and no matching media are available
                'server' => array('HTTP_ACCEPT' => 'application/json, application/xml, text/*, */*'),
                'available' => array('foo/bar', 'baz/dib'),
                'expected_value' => 'foo/bar',
                'expected_params' => array(),
            ),
            array(
                // explictly accepts application/xml, which is explictly available.
                // note that it returns the *available* value, which is determined
                // by the developer, not the acceptable value, which is determined
                // by the user/client/headers.
                'server' => array('HTTP_ACCEPT' => 'application/json, application/xml, text/*, */*'),
                'available' => array('application/XML', 'text/csv'),
                'expected_value' => 'application/XML',
                'expected_params' => array(),
            ),
            array(
                // a subtype is available
                'server' => array('HTTP_ACCEPT' => 'application/json, application/xml, text/*, */*'),
                'available' => array('foo/bar', 'text/csv', 'baz/qux'),
                'expected_value' => 'text/csv',
                'expected_params' => array(),
            ),
            array(
                // no acceptable media specified, use first available
                'server' => array(),
                'available' => array('application/json', 'application/xml'),
                'expected_value' => 'application/json',
                'expected_params' => array(),
            ),
            array(
                // media is available but quality level is not acceptable
                'server' => array('HTTP_ACCEPT' => 'application/json, application/xml, text/*, foo/bar;q=0'),
                'available' => array('foo/bar'),
                'expected_value' => false,
                'expected_params' => array(),
            ),
            array(
                // override with file extension
                'server' => array(
                    'HTTP_ACCEPT' => 'text/html, text/xhtml, text/plain',
                    'REQUEST_URI' => '/path/to/resource.json',
                ),
                'available' => array('text/html', 'application/json'),
                'expected_value' => 'application/json',
                'expected_params' => array(),
            ),
            array(
                // check against parameters when they are available
                'server' => array('HTTP_ACCEPT' => 'text/html;level=2, text/html;level=1;q=0.5'),
                'available' => array('text/html;level=1'),
                'expected_value' => 'text/html',
                'expected_params' => array('level' => '1'),
            ),
            array(
                // check against parameters when they are not available
                'server' => array('HTTP_ACCEPT' => 'text/html;level=2, text/html;level=1;q=0.5'),
                'available' => array('text/html;level=3'),
                'expected_value' => false,
                'expected_params' => array(),
            ),
            array(
                // */* is available
                'server' => array('HTTP_ACCEPT' => 'application/json, application/xml, text/csv'),
                'available' => array('*/*'),
                'expected_value' => '*/*',
                'expected_params' => array(),
            ),
        );
    }

    public function mediaProvider()
    {
        return array(
            array(
                'server' => array('HTTP_ACCEPT' => 'text/*;q=0.9, text/html, text/xhtml;q=0.8'),
                'expect' => array(
                    array(
                        'type' => 'text',
                        'subtype' => 'html',
                        'value' => 'text/html',
                        'quality' => 1.0,
                        'parameters' => array(),
                    ),
                    array(
                        'type' => 'text',
                        'subtype' => '*',
                        'value' => 'text/*',
                        'quality' => 0.9,
                        'parameters' => array(),
                    ),
                    array(
                        'type' => 'text',
                        'subtype' => 'xhtml',
                        'value' => 'text/xhtml',
                        'quality' => 0.8,
                        'parameters' => array(),
                    ),
                ),
                'negotiator_class' => 'Aura\Accept\Media\MediaNegotiator',
                'value_class' => 'Aura\Accept\Media\MediaValue',
            ),
            array(
                'server' => array('HTTP_ACCEPT' => 'text/json;version=1,text/html;q=1;version=2,application/xml+xhtml;q=0'),
                'expect' => array(
                    array(
                        'type' => 'text',
                        'subtype' => 'json',
                        'value' => 'text/json',
                        'quality' => 1.0,
                        'parameters' => array('version' => 1),
                    ),
                    array(
                        'type' => 'text',
                        'subtype' => 'html',
                        'value' => 'text/html',
                        'quality' => 1.0,
                        'parameters' => array('version' => 2),
                    ),
                    array(
                        'type' => 'application',
                        'subtype' => 'xml+xhtml',
                        'value' => 'application/xml+xhtml',
                        'quality' => 0,
                        'parameters' => array(),
                    ),
                ),
                'negotiator_class' => 'Aura\Accept\Media\MediaNegotiator',
                'value_class' => 'Aura\Accept\Media\MediaValue',
            ),
            array(
                'server' => array('HTTP_ACCEPT' => 'text/json;version=1;foo=bar,text/html;version=2,application/xml+xhtml'),
                'expect' => array(
                    array(
                        'type' => 'text',
                        'subtype' => 'json',
                        'value' => 'text/json',
                        'quality' => 1.0,
                        'parameters' => array('version' => 1, 'foo' => 'bar'),
                    ),
                    array(
                        'type' => 'text',
                        'subtype' => 'html',
                        'value' => 'text/html',
                        'quality' => 1.0,
                        'parameters' => array('version' => 2),
                    ),
                    array(
                        'type' => 'application',
                        'subtype' => 'xml+xhtml',
                        'value' => 'application/xml+xhtml',
                        'quality' => 1.0,
                        'parameters' => array(),
                    ),
                ),
                'negotiator_class' => 'Aura\Accept\Media\MediaNegotiator',
                'value_class' => 'Aura\Accept\Media\MediaValue',
            ),
            array(
                'server' => array('HTTP_ACCEPT' => 'text/json;q=0.9;version=1;foo="bar"'),
                'expect' => array(
                    array(
                        'type' => 'text',
                        'subtype' => 'json',
                        'value' => 'text/json',
                        'quality' => 0.9,
                        'parameters' => array('version' => 1, 'foo' => 'bar'),
                    ),
                ),
                'negotiator_class' => 'Aura\Accept\Media\MediaNegotiator',
                'value_class' => 'Aura\Accept\Media\MediaValue',
            ),
        );
    }
}
