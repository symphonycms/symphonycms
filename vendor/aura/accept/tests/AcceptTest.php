<?php
namespace Aura\Accept;

class AcceptTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $factory = new AcceptFactory(array(
            'HTTP_ACCEPT' => 'application/json, application/xml, text/*, */*',
            'HTTP_ACCEPT_CHARSET' => 'iso-8859-5, unicode-1-1;q=0.8',
            'HTTP_ACCEPT_ENCODING' => 'compress;q=0.5, gzip;q=1.0',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US, en-GB, en, *',
        ));
        $this->accept = $factory->newInstance();
    }

    public function testNegotiateCharset()
    {
        $actual = $this->accept->negotiateCharset(array('unicode-1-1'));
        $expect = 'unicode-1-1';
        $this->assertSame($expect, $actual->getValue());
    }

    public function testNegotiateEncoding()
    {
        $actual = $this->accept->negotiateEncoding(array());
        $this->assertFalse($actual);
    }

    public function testNegotiateLanguage()
    {
        $actual = $this->accept->negotiateLanguage(array('pt-BR', 'fr-FR'));
        $expect = 'pt-BR';
        $this->assertSame($expect, $actual->getValue());
    }

    public function testNegotiateMedia()
    {
        $actual = $this->accept->negotiateMedia(array(
            'application/xml',
            'application/json',
        ));
        $expect = 'application/json';
        $this->assertSame($expect, $actual->getValue());
    }
}
