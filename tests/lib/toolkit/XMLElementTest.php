<?php

namespace Symphony\XML\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers XMLElement
 */
final class XMLElementTest extends TestCase
{
    public function testDefaultValues()
    {
        $x = new \XMLElement('xml');
        $this->assertEquals('xml', $x->getName());
        $this->assertEmpty($x->getValue());
        $this->assertEquals(0, $x->getNumberOfChildren());
        $this->assertEmpty($x->getChildren());
        $this->assertEmpty($x->getAttributes());
        //$this->assertEquals('1.0', $x->getVersion());
        //$this->assertEquals('utf-8', $x->getEncoding());
        //$this->assertEquals('xml', $x->getElementStyle());
        $this->assertEquals('<xml />', $x->generate());
    }

    public function testValueInConstructor()
    {
        $x = new \XMLElement('xml', 'value');
        $this->assertEquals('xml', $x->getName());
        $this->assertEquals('value', $x->getValue());
        $this->assertEquals(1, $x->getNumberOfChildren());
        $this->assertNotEmpty($x->getChildren());
        $this->assertEmpty($x->getAttributes());
        $this->assertEquals('<xml>value</xml>', $x->generate());
    }

    public function testAttributesAndValueInConstructor()
    {
        $x = new \XMLElement('xml', 'value', ['attr' => 'yes', 'null' => null]);
        $this->assertEquals('xml', $x->getName());
        $this->assertEquals('value', $x->getValue());
        $this->assertEquals(1, $x->getNumberOfChildren());
        $this->assertNotEmpty($x->getChildren());
        $this->assertNotEmpty($x->getAttributes());
        $this->assertEquals('yes', $x->getAttribute('attr'));
        $this->assertEquals('<xml attr="yes" null="">value</xml>', $x->generate());
    }

    public function testAttributesAndValueInConstructorWithHandles()
    {
        $x = new \XMLElement('x m l', 'value', ['attr' => 'yes', 'null' => null], true);
        $this->assertEquals('x-m-l', $x->getName());
        $this->assertEquals('value', $x->getValue());
        $this->assertEquals(1, $x->getNumberOfChildren());
        $this->assertNotEmpty($x->getChildren());
        $this->assertNotEmpty($x->getAttributes());
        $this->assertEquals('yes', $x->getAttribute('attr'));
        $this->assertEquals('<x-m-l attr="yes" null="">value</x-m-l>', $x->generate());
    }

    public function testNoEmptyAttributes()
    {
        $x = (new \XMLElement('xml'))
            ->setAttribute('null', null)
            ->setAttributeArray(['empty' => '', 'not-empty' => '1'])
            ->setAllowEmptyAttributes(false);
        $this->assertNotEmpty($x->getAttributes());
        $this->assertEquals('<xml not-empty="1" />', $x->generate());
    }

    public function testGenerateWithHeader()
    {
        $x = (new \XMLElement('xml', 'value'));
        $this->assertEquals('<xml>value</xml>', $x->generate());
        $x->renderHeader();
        $this->assertEquals('<?xml version="1.0" encoding="utf-8" ?><xml>value</xml>', $x->generate());
    }

    public function testGenerateWithSelfClosing()
    {
        $x = (new \XMLElement('xml', 'value'));
        $this->assertEquals('<xml>value</xml>', $x->generate());
        $x->renderSelfClosingTag();
        $this->assertEquals('<xml>value</xml>', $x->generate());
        $x = (new \XMLElement('xml', null));
        $this->assertEquals('<xml />', $x->generate());
        $x->setSelfClosingTag(false);
        $this->assertEquals('<xml></xml>', $x->generate());
    }

    public function testGenerateForceNoEndTag()
    {
        $x = (new \XMLElement('br', null));
        $this->assertEquals('<br />', $x->generate());
        $x->setElementStyle('html');
        $this->assertEquals('<br>', $x->generate());
    }

    public function testWithChildrenFormatted()
    {
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('x'))
            ->appendChild((new \XMLElement('child'))->setValue('y'));
        $this->assertEquals("<xml>\n\t<child>x</child>\n\t<child>y</child>\n</xml>\n", $x->generate(true));
    }
}
