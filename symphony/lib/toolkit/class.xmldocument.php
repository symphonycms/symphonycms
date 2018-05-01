<?php

/**
 * @package toolkit
 */
/**
 * `XMLDocument` is a class used to simulate PHP's `DOMDocument`
 * class. Each object is a representation of a XML document
 * and can store it's children in an array. When an `XMLElement`
 * is generated, it is output as an XML string.
 * The `XMLDocument` extends `XMLElement` and adds properties the are specific
 * to documents.
 *
 * @since Symphony 3.0.0
 */

class XMLDocument extends XMLElement
{
    /**
     * Any processing instructions that the XSLT should know about when a
     * `XMLElement` is generated
     * @var array
     */
    private $processingInstructions = [];

    /**
     * The DTD the should be output when a `XMLElement` is generated, defaults to null.
     * @var string
     */
    private $dtd = null;

    /**
     * The encoding of the `XMLElement`, defaults to 'utf-8'
     * @var string
     */
    private $encoding = 'utf-8';

    /**
     * The version of the XML that is used for generation, defaults to '1.0'
     * @var string
     */
    private $version = '1.0';

    /**
     * When set to true this will include the XML declaration will be
     * output when the `XMLElement` is generated. Defaults to `false`.
     * @var boolean
     */
    private $includeHeader = false;

    /**
     * Accessor for `$encoding`
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Accessor for `$version`
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Adds processing instructions to this `XMLElement`
     *
     * @param string $pi
     * @return XMLElement
     *  The current instance
     */
    public function addProcessingInstruction($pi)
    {
        $this->processingInstructions[] = $pi;
        return $this;
    }

    /**
     * Sets the DTD for this `XMLElement`
     *
     * @param string $dtd
     * @return XMLElement
     *  The current instance
     */
    public function setDTD($dtd)
    {
        $this->dtd = $dtd;
        return $this;
    }

    /**
     * Sets the encoding for this `XMLElement` for when
     * it's generated.
     *
     * @param string $value
     * @return XMLElement
     *  The current instance
     */
    public function setEncoding($value)
    {
        $this->encoding = $value;
        return $this;
    }

    /**
     * Sets the version for the XML declaration of this
     * `XMLElement`
     *
     * @param string $value
     * @return XMLElement
     *  The current instance
     */
    public function setVersion($value)
    {
        $this->version = $value;
        return $this;
    }

    /**
     * Sets whether this `XMLElement` needs to output an
     * XML declaration or not. This normally is only set to
     * true for the parent `XMLElement`, eg. 'html'.
     *
     * @param bool $value
     * @return XMLElement
     *  The current instance
     */
    public function setIncludeHeader($value)
    {
        $this->includeHeader = $value;
        return $this;
    }

    /**
     * Makes this `XMLElement` output an XML declaration.
     *
     * @since Symphony 3.0.0
     * @uses setIncludeHeader()
     * @return XMLElement
     *  The current instance
     */
    public function renderHeader()
    {
        return $this->setIncludeHeader(true);
    }

    /**
     * This function will turn the `XMLDocument` into a string
     * representing the document as it would appear in the markup.
     * The result is valid XML.
     *
     * @see XMLElement::generate()
     * @param boolean $indent
     *  Defaults to false
     * @param integer $tabDepth
     *  Defaults to 0, indicates the number of tabs (\t) that this
     *  element should be indented by in the output string
     * @return string
     *  The XML string
     */
    public function generate($indent = false, $tabDepth = 0)
    {
        $result = null;
        $newline = ($indent ? PHP_EOL : null);

        if ($this->includeHeader) {
            $result .= sprintf(
                '<?xml version="%s" encoding="%s" ?>%s',
                $this->version,
                $this->encoding,
                $newline
            );
        }

        if ($this->dtd) {
            $result .= $this->dtd . $newline;
        }

        if (!empty($this->processingInstructions)) {
            $result .= implode($newline, $this->processingInstructions);
        }

        return $result . parent::generate($indent, $tabDepth);
    }

    /**
     * Given a `DOMDocument`, this function will create a new `XMLDocument`
     * object, copy all attributes and children and return the result.
     *
     * @param DOMDocument $doc
     *  A DOMDocument to copy from
     * @return XMLDocument
     *  The new `XMLDocument` derived from `DOMDocument $doc`.
     */
    public static function fromDOMDocument(DOMDocument $doc)
    {
        $root = new XMLDocument($doc->documentElement->nodeName);
        static::copyDOMNode($root, $doc->documentElement);
        return $root;
    }
}
