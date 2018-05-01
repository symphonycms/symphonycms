<?php

namespace Symphony\XML\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers XMLDocument
 */
final class XMLDocumentTest extends TestCase
{
    public function testDefaultValues()
    {
        $x = new \XMLDocument('xml');
        $this->assertEquals('xml', $x->getName());
        $this->assertEmpty($x->getValue());
        $this->assertEquals(0, $x->getNumberOfChildren());
        $this->assertEmpty($x->getChildren());
        $this->assertEmpty($x->getAttributes());
        $this->assertEquals('1.0', $x->getVersion());
        $this->assertEquals('utf-8', $x->getEncoding());
        $this->assertEquals('<xml />', $x->generate());
    }

    public function testSetVersion()
    {
        $x = (new \XMLDocument('xml'))->setVersion('test')->renderHeader();
        $this->assertEquals('test', $x->getVersion());
        $this->assertEquals('<?xml version="test" encoding="utf-8" ?><xml />', $x->generate());
    }

    public function testSetEncoding()
    {
        $x = (new \XMLDocument('xml'))->setEncoding('test')->renderHeader();
        $this->assertEquals('test', $x->getEncoding());
        $this->assertEquals('<?xml version="1.0" encoding="test" ?><xml />', $x->generate());
    }

    public function testGenerateWithHeader()
    {
        $x = (new \XMLDocument('xml', 'value'));
        $this->assertEquals('<xml>value</xml>', $x->generate());
        $x->renderHeader();
        $this->assertEquals('<?xml version="1.0" encoding="utf-8" ?><xml>value</xml>', $x->generate());
    }

    public function testAddProcessingInstruction()
    {
        $x = (new \XMLDocument('xml'))
            ->addProcessingInstruction('this is a test')
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child'))->setValue('2'));
        $this->assertEquals('this is a test<xml><child>1</child><child>2</child></xml>', $x->generate());
    }

    public function testSetDTD()
    {
        $x = (new \XMLDocument('xml'))
            ->setDTD('test-dtd')
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child'))->setValue('2'));
        $this->assertEquals('test-dtd<xml><child>1</child><child>2</child></xml>', $x->generate());
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidAppend()
    {
        $x = new \XMLDocument('xml');
        $c = new \XMLElement('child');
        $c->appendChild($x);
    }

    public function testFromDOMDocument()
    {
        $xml = '<xml test="dom-doc"><child>1</child><child>4</child><child>3</child></xml>';
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $x = \XMLDocument::fromDOMDocument($doc);
        $this->assertTrue($x instanceof \XMLElement);
        $this->assertTrue($x instanceof \XMLDocument);
        $this->assertNotEmpty($x);
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('xml', $x->getName());
        $this->assertEquals('dom-doc', $x->getAttribute('test'));
        $this->assertEquals('4', $x->getChild(1)->getValue());
    }
}
