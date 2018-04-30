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

    public function testGetChildrenByName()
    {
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child-not'))->setValue('2'))
            ->appendChild((new \XMLElement('child'))->setValue('3'));
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('3', $x->getChildByName('child', 1)->getValue());
    }

    public function testGetChildrenByNameWithValue()
    {
        $x = (new \XMLElement('xml', 'value'))
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child-not'))->setValue('2'))
            ->appendChild((new \XMLElement('child'))->setValue('3'));
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(4, $x->getNumberOfChildren());
        $this->assertEquals('3', $x->getChildByName('child', 1)->getValue());
    }

    public function testWithChildrenFormatted()
    {
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('x'))
            ->appendChild((new \XMLElement('child'))->setValue('y'));
        $this->assertEquals("<xml>\n\t<child>x</child>\n\t<child>y</child>\n</xml>\n", $x->generate(true));
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidSetChildren()
    {
        $x = (new \XMLElement('xml'));
        $x->setChildren([$x]);
    }

    public function testAppend()
    {
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child'))->setValue('2'))
            ->appendChild((new \XMLElement('child'))->setValue('3'));
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('<xml><child>1</child><child>2</child><child>3</child></xml>', $x->generate());
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidAppend()
    {
        $x = (new \XMLElement('xml'));
        $x->appendChild($x);
    }

    public function testAppendArray()
    {
        $x = (new \XMLElement('xml'))
            ->appendChildArray([
                (new \XMLElement('child'))->setValue('1'),
                (new \XMLElement('child'))->setValue('2'),
                (new \XMLElement('child'))->setValue('3'),
            ]);
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('<xml><child>1</child><child>2</child><child>3</child></xml>', $x->generate());
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidAppendArray()
    {
        $x = (new \XMLElement('xml'));
        $x->appendChildArray([$x]);
    }

    public function testPrepend()
    {
        $x = (new \XMLElement('xml'))
            ->prependChild((new \XMLElement('child'))->setValue('3'))
            ->prependChild((new \XMLElement('child'))->setValue('2'))
            ->prependChild((new \XMLElement('child'))->setValue('1'));
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('<xml><child>1</child><child>2</child><child>3</child></xml>', $x->generate());
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidPrepend()
    {
        $x = (new \XMLElement('xml'));
        $x->prependChild($x);
    }

    public function testRemoveAt()
    {
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child'))->setValue('2'))
            ->appendChild((new \XMLElement('child'))->setValue('3'))
            ->removeChildAt(1);
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(2, $x->getNumberOfChildren());
        $this->assertEquals('<xml><child>1</child><child>3</child></xml>', $x->generate());
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidRemoveAt()
    {
        $x = (new \XMLElement('xml'));
        $x->removeChildAt(0);
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidRemoveAtUnsetIndex()
    {
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child'))->setValue('2'))
            ->appendChild((new \XMLElement('child'))->setValue('3'))
            ->removeChildAt(1);
        $x->removeChildAt(1);
    }

    public function testInsertAt()
    {
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child'))->setValue('2'))
            ->appendChild((new \XMLElement('child'))->setValue('3'))
            ->removeChildAt(1);
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(2, $x->getNumberOfChildren());
        $this->assertEquals('<xml><child>1</child><child>3</child></xml>', $x->generate());
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidInsertAt()
    {
        $x = (new \XMLElement('xml'));
        $x->insertChildAt(2, $x);
    }

    public function testReplaceAt()
    {
        $x = (new \XMLElement('xml'))
            ->appendChild((new \XMLElement('child'))->setValue('1'))
            ->appendChild((new \XMLElement('child'))->setValue('2'))
            ->appendChild((new \XMLElement('child'))->setValue('3'))
            ->replaceChildAt(1, (new \XMLElement('child'))->setValue('4'));
        $this->assertNotEmpty($x->getChildren());
        $this->assertEquals(3, $x->getNumberOfChildren());
        $this->assertEquals('<xml><child>1</child><child>4</child><child>3</child></xml>', $x->generate());
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidReplaceAt()
    {
        $x = (new \XMLElement('xml'));
        $x->removeChildAt(2, $x);
    }
}
