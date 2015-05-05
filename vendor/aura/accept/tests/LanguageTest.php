<?php
namespace Aura\Accept;

class LanguageTest extends AcceptTestCase
{
    protected function newLanguage($server = array())
    {
        return new Language\LanguageNegotiator(new ValueFactory, $server);
    }

    /**
     * @dataProvider languageProvider
     * @param $accept
     * @param $expect
     * @param $negotiator_class
     * @param $value_class
     */
    public function testGetLanguage($server, $expect, $negotiator_class, $value_class)
    {
        $language = $this->newLanguage($server);
        $this->assertAcceptValues($language, $expect, $negotiator_class, $value_class);
    }

    /**
     * @dataProvider languageNegotiateProvider
     * @param $accept
     * @param $available
     * @param $expected
     */
    public function testGetLanguage_negotiate($server, $available, $expected)
    {
        $language = $this->newLanguage($server);
        $actual = $language->negotiate($available);

        if ($expected === false) {
            $this->assertFalse($actual);
        } else {
            $this->assertInstanceOf('Aura\Accept\Language\LanguageValue', $actual->available);
            $this->assertSame($expected, $actual->available->getValue());
        }
    }

    public function languageProvider()
    {
        return array(
            array(
                'server' => array(),
                'expect' => array(),
                'negotiator_class' => 'Aura\Accept\Language\LanguageNegotiator',
                'value_class' => 'Aura\Accept\Language\LanguageValue',
            ),
            array(
                'server' => array(
                    'HTTP_ACCEPT_LANGUAGE' => '*',
                ),
                'expect' => array(
                    array('type' => '*', 'subtype' => false, 'value' => '*',  'quality' => 1.0, 'parameters' => array())
                ),
                'negotiator_class' => 'Aura\Accept\Language\LanguageNegotiator',
                'value_class' => 'Aura\Accept\Language\LanguageValue',
            ),
            array(
                'server' => array(
                    'HTTP_ACCEPT_LANGUAGE' => 'en-US, en-GB, en, *',
                ),
                'expect' => array(
                    array('type' => 'en', 'subtype' => 'US', 'value' => 'en-US', 'quality' => 1.0, 'parameters' => array()),
                    array('type' => 'en', 'subtype' => 'GB', 'value' => 'en-GB', 'quality' => 1.0, 'parameters' => array()),
                    array('type' => 'en', 'subtype' => false, 'value' => 'en', 'quality' => 1.0, 'parameters' => array()),
                    array('type' => '*', 'subtype' => false, 'value' => '*',  'quality' => 1.0, 'parameters' => array())
                ),
                'negotiator_class' => 'Aura\Accept\Language\LanguageNegotiator',
                'value_class' => 'Aura\Accept\Language\LanguageValue',
            ),
        );
    }

    public function languageNegotiateProvider()
    {
        return array(
            array(
                'server' => array('HTTP_ACCEPT_LANGUAGE' => 'en-US, en-GB, en, *'),
                'available' => array(),
                'expected' => false,
            ),
            array(
                'server' => array('HTTP_ACCEPT_LANGUAGE' => 'en-US, en-GB, en, *'),
                'available' => array('foo-bar' , 'baz-dib'),
                'expected' => 'foo-bar',
            ),
            array(
                'server' => array('HTTP_ACCEPT_LANGUAGE' => 'en-US, en-GB, en, *'),
                'available' => array('en-gb', 'fr-FR'),
                'expected' => 'en-gb',
            ),
            array(
                'server' => array('HTTP_ACCEPT_LANGUAGE' => 'en-US, en-GB, en, *'),
                'available' => array('foo-bar', 'en-zo', 'baz-qux'),
                'expected' => 'en-zo',
            ),
            array(
                'server' => array(),
                'available' => array('en-us', 'en-gb'),
                'expected' => 'en-us',
            ),
            array(
                'server' => array('HTTP_ACCEPT_LANGUAGE' => 'en-us, en-gb, en, foo-bar;q=0'),
                'available' => array('foo-bar'),
                'expected' => false
            ),
            array(
                'server' => array('HTTP_ACCEPT_LANGUAGE' => 'en-us, en-gb, en, foo-bar;q=0'),
                'available' => array('*'),
                'expected' => '*'
            )
        );
    }
}
