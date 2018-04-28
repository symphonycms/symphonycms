<?php
/**
 * @package toolkit
 */
/**
 * `XMLElement` is a class used to simulate PHP's `DOMElement`
 * class. Each object is a representation of a HTML element
 * and can store it's children in an array. When an `XMLElement`
 * is generated, it is output as an XML string.
 */

class XMLElement implements IteratorAggregate
{
    /**
     * This is an array of HTML elements that are self closing.
     * @var array
     */
    protected static $no_end_tags = [
        'area',
        'base',
        'br',
        'col',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
    ];

    /**
     * The name of the HTML Element, eg. 'p'
     * @var string
     */
    private $name;

    /**
     * The value of this `XMLElement` as an array or a string
     * @var string|array
     */
    private $value = [];

    /**
     * Any additional attributes can be included in an associative array
     * with the key being the name and the value being the value of the
     * attribute.
     * @var array
     */
    private $attributes = [];

    /**
     * Children of this `XMLElement`, which will also be `XMLElement`'s
     * @var array
     */
    private $children = [];

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
     * The type of element, defaults to 'xml'. Used when determining the style
     * of end tag for this element when generated
     * @var string
     */
    private $elementStyle = 'xml';

    /**
     * When set to true this will include the XML declaration will be
     * output when the `XMLElement` is generated. Defaults to `false`.
     * @var boolean
     */
    private $includeHeader = false;

    /**
     * Specifies whether this HTML element has an closing element, or if
     * it self closing. Defaults to `true`.
     *  eg. `<p></p>` or `<input />`
     * @var boolean
     */
    private $selfClosing = true;

    /**
     * Specifies whether attributes need to have a value or if they can
     * be shorthand. Defaults to `true`. An example of this would be:
     *  `<option selected>Value</option>`
     * @var boolean
     */
    private $allowEmptyAttributes = true;

    /**
     * The constructor for the `XMLElement`
     *
     * @param string $name
     *  The name of the `XMLElement`, 'p'.
     * @param string|XMLElement $value (optional)
     *  The value of this `XMLElement`, it can be a string
     *  or another `XMLElement` object.
     * @param array $attributes (optional)
     *  Any additional attributes can be included in an associative array with
     *  the key being the name and the value being the value of the attribute.
     *  Attributes set from this array will override existing attributes
     *  set by previous params.
     * @param boolean $createHandle
     *  Whether this function should convert the `$name` to a handle. Defaults to
     *  `false`.
     */
    public function __construct($name, $value = null, array $attributes = [], $createHandle = false)
    {
        $this->setName($name, $createHandle);
        $this->setValue($value);

        if (is_array($attributes) && !empty($attributes)) {
            $this->setAttributeArray($attributes);
        }
    }

    /**
     * Accessor for `$name`
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Accessor for `$value`, converted to a string
     *
     * @return string
     */
    public function getValue()
    {
        $value = '';

        if (is_array($this->value)) {
            foreach ($this->value as $v) {
                if ($v instanceof XMLElement) {
                    $value .= $v->generate();
                } else {
                    $value .= $v;
                }
            }
        } elseif (!is_null($this->value)) {
            $value = $this->value;
        }

        return $value;
    }

    /**
     * Retrieves the value of an attribute by name
     *
     * @param string $name
     * @return string
     */
    public function getAttribute($name)
    {
        if (!isset($this->attributes[$name])) {
            return null;
        }

        return $this->attributes[$name];
    }

    /**
     * Accessor for `$this->attributes`
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieves a child-element by position
     *
     * @since Symphony 2.3
     * @param integer $position
     * @return XMLElement
     */
    public function getChild($position)
    {
        if (!isset($this->children[$this->getRealIndex($position)])) {
            return null;
        }

        return $this->children[$this->getRealIndex($position)];
    }

    /**
     * Accessor for `$this->children`
     *
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Accessor for `$this->children`, returning only `XMLElement` children,
     * not text nodes.
     *
     * @return XMLElementChildrenFilter
     */
    public function getIterator()
    {
        return new XMLElementChildrenFilter(new ArrayIterator($this->children));
    }

    /**
     * Retrieves child-element by name and position. If no child is found,
     * `NULL` will be returned.
     *
     * @since Symphony 2.3
     * @param string $name
     * @param integer $position
     * @return XMLElement
     */
    public function getChildByName($name, $position)
    {
        $result = array_values($this->getChildrenByName($name));

        if (!isset($result[$position])) {
            return null;
        }

        return $result[$position];
    }

    /**
     * Accessor to return an associative array of all `$this->children`
     * whose's name matches the given `$name`. If no children are found,
     * an empty array will be returned.
     *
     * @since Symphony 2.2.2
     * @param string $name
     * @return array
     *  An associative array where the key is the `$index` of the child
     *  in `$this->children`
     */
    public function getChildrenByName($name)
    {
        $result = [];

        foreach ($this as $i => $child) {
            if ($child->getName() != $name) {
                continue;
            }

            $result[$i] = $child;
        }

        return $result;
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
     * Sets the style of the `XMLElement`. Used when the
     * `XMLElement` is being generated to determine whether
     * needs to be closed, is self closing or is standalone.
     *
     * @param string $style
     *  If not 'xml', will trigger the
     *  XMLElement to be closed by itself or left standalone.
     * @return XMLElement
     *  The current instance
     */
    public function setElementStyle($style)
    {
        $this->elementStyle = $style;
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
     * Sets whether this `XMLElement` is self closing or not.
     *
     * @param bool $value
     * @return XMLElement
     *  The current instance
     */
    public function setSelfClosingTag($value)
    {
        $this->selfClosing = $value;
        return $this;
    }

    /**
     * Makes this `XMLElement` to self close.
     *
     * @since Symphony 3.0.0
     * @uses setSelfClosingTag()
     * @return XMLElement
     *  The current instance
     */
    public function renderSelfClosingTag()
    {
        return $this->setSelfClosingTag(true);
    }

    /**
     * Specifies whether attributes need to have a value
     * or if they can be shorthand on this `XMLElement`.
     *
     * @param bool $value
     * @return XMLElement
     *  The current instance
     */
    public function setAllowEmptyAttributes($value)
    {
        $this->allowEmptyAttributes = $value;
        return $this;
    }

    /**
     * Makes this `XMLElement` render empty attributes.
     *
     * @since Symphony 3.0.0
     * @uses setAllowEmptyAttributes()
     * @return XMLElement
     *  The current instance
     */
    public function renderEmptyAttributes()
    {
        return $this->setAllowEmptyAttributes(true);
    }

    /**
     * Sets the name of this `XMLElement`, ie. 'p' => <p />
     *
     * @since Symphony 2.3.2
     * @param string $name
     *  The name of the `XMLElement`, 'p'.
     * @param boolean $createHandle
     *  Whether this function should convert the `$name` to a handle.
     *  Defaults to `false`.
     * @return XMLElement
     *  The current instance
     */
    public function setName($name, $createHandle = false)
    {
        $this->name = ($createHandle) ? Lang::createHandle($name) : $name;
        return $this;
    }

    /**
     * Sets and appends the value of the `XMLElement`.
     *
     * @param string|XMLElement|array $value
     * @return XMLElement
     *  The current instance
     */
    public function setValue($value)
    {
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        if (!is_null($value)) {
            $this->value = $value;
            $this->appendChild($value);
        }

        return $this;
    }

    /**
     * This function will remove all text attributes from the `XMLElement` node
     * and replace them with the given value.
     *
     * @since Symphony 2.4
     * @param string|XMLElement|array $value
     * @return XMLElement
     *  The current instance
     */
    public function replaceValue($value)
    {
        foreach ($this->children as $i => $child) {
            if ($child instanceof XMLElement) {
                continue;
            }

            unset($this->children[$i]);
        }

        $this->setValue($value);

        return $this;
    }

    /**
     * Sets an attribute
     *
     * @param string $name
     *  The name of the attribute
     * @param string $value
     *  The value of the attribute
     * @return XMLElement
     *  The current instance
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * A convenience method to quickly add multiple attributes to
     * an `XMLElement`
     *
     * @param array $attributes
     *  Associative array with the key being the name and
     *  the value being the value of the attribute.
     * @return XMLElement
     *  The current instance
     */
    public function setAttributeArray(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }

        return $this;
    }

    /**
     * A convenience method that encapsulate validation of a child node.
     * This should prevent generate errors by catching them earlier.
     *
     * @since Symphony 2.5.0
     * @param XMLElement $child
     *  The child to validate
     * @return XMLElement
     *  The current instance
     * @throws Exception
     *  If the child is not valid
     */
    private function validateChild($child)
    {
        if ($this === $child) {
            throw new Exception(__('Can not add the element itself as one of its child'));
        }
        return $this;
    }

    /**
     * This function expects an array of `XMLElement` that will completely
     * replace the contents of `$this->children`. Take care when using
     * this function.
     *
     * @since Symphony 2.2.2
     * @uses validateChild()
     * @param array $children
     *  An array of XMLElement's to act as the children for the current
     *  XMLElement instance
     * @return XMLElement
     *  The current instance
     */
    public function setChildren(array $children)
    {
        foreach ($children as $child) {
            $this->validateChild($child);
        }
        $this->children = $children;
        return $this;
    }

    /**
     * Adds an `XMLElement` to the children array
     *
     * @uses validateChild()
     * @param XMLElement|string $child
     * @return XMLElement
     *  The current instance
     */
    public function appendChild($child)
    {
        $this->validateChild($child);
        $this->children[] = $child;
        return $this;
    }

    /**
     * A convenience method to add children to an `XMLElement`
     * quickly.
     *
     * @uses appendChild()
     * @param array $children
     * @return XMLElement
     *  The current instance
     */
    public function appendChildArray(array $children)
    {
        foreach ($children as $child) {
            $this->appendChild($child);
        }
        return $this;
    }

    /**
     * Adds an `XMLElement` to the start of the children
     * array, this will mean it is output before any other
     * children when the `XMLElement` is generated
     *
     * @uses validateChild()
     * @param XMLElement $child
     * @return XMLElement
     *  The current instance
     */
    public function prependChild(XMLElement $child)
    {
        $this->validateChild($child);
        array_unshift($this->children, $child);
        return $this;
    }

    /**
     * A convenience method to quickly add a CSS class to this `XMLElement`'s
     * existing class attribute. If the attribute does not exist, it will
     * be created.
     * It also make sure that classes are separated by a single space.
     *
     * @since Symphony 2.2.2
     * @uses setAttribute()
     * @param string $class
     *  The CSS class name to add to this `XMLElement`
     * @return XMLElement
     *  The current instance
     */
    public function addClass($class)
    {
        $current = preg_split('%\s+%', $this->getAttribute('class'), 0, PREG_SPLIT_NO_EMPTY);
        $added = preg_split('%\s+%', $class, 0, PREG_SPLIT_NO_EMPTY);
        $current = array_merge($current, $added);
        $classes = implode(' ', $current);
        return $this->setAttribute('class', $classes);
    }

    /**
     * A convenience method to quickly remove a CSS class from an
     * `XMLElement`'s existing class attribute. If the attribute does not
     * exist, this method will do nothing.
     * It also make sure that classes are separated by a single space.
     *
     * @since Symphony 2.2.2
     * @uses setAttribute()
     * @param string $class
     *  The CSS class name to remove from this `XMLElement`
     * @return XMLElement
     *  The current instance
     */
    public function removeClass($class)
    {
        $classes = preg_split('%\s+%', $this->getAttribute('class'), 0, PREG_SPLIT_NO_EMPTY);
        $removed = preg_split('%\s+%', $class, 0, PREG_SPLIT_NO_EMPTY);
        $classes = array_diff($classes, $removed);
        $classes = implode(' ', $classes);
        return $this->setAttribute('class', $classes);
    }

    /**
     * Returns the number of children this `XMLElement` has.
     * @return integer
     */
    public function getNumberOfChildren()
    {
        return count($this->children);
    }

    /**
     * Given the position of the child in the `$this->children`,
     * this function will unset the child at that position. This function
     * is not reversible. This function does not alter the key's of
     * `$this->children` after removing a child
     *
     * @since Symphony 2.2.2
     * @param integer $index
     *  The index of the child to be removed. If the index given is negative
     *  it will be calculated from the end of `$this->children`.
     * @return XMLElement
     *  The current instance
     * @throws Exception
     *  If the $index is not an integer or the index is not valid.
     */
    public function removeChildAt($index)
    {
        General::ensureType([
            'index' => ['var' => $index, 'type' => 'int'],
        ]);

        $index = $this->getRealIndex($index);

        if (!isset($this->children[$index])) {
            throw new Exception("Index out of range. No child at index `$index`.");
        }

        unset($this->children[$index]);

        return $this;
    }

    /**
     * Given a desired index, and an `XMLElement`, this function will insert
     * the child at that index in `$this->children` shuffling all children
     * greater than `$index` down one. If the `$index` given is greater then
     * the number of children for this `XMLElement`, the `$child` will be
     * appended to the current `$this->children` array.
     *
     * @since Symphony 2.2.2
     * @param integer $index
     *  The index where the `$child` should be inserted. If this is negative
     *  the index will be calculated from the end of `$this->children`.
     * @param XMLElement $child
     *  The XMLElement to insert at the desired `$index`
     * @return XMLElement
     *  The current instance
     * @throws Exception
     *  If the $index is not an integer.
     */
    public function insertChildAt($index, XMLElement $child = null)
    {
        General::ensureType([
            'index' => ['var' => $index, 'type' => 'int'],
        ]);
        
        $this->validateChild($child);

        if ($index >= $this->getNumberOfChildren()) {
            return $this->appendChild($child);
        }

        $start = array_slice($this->children, 0, $index);
        $end = array_slice($this->children, $index);

        $merge = array_merge($start, array(
            $index => $child
        ), $end);

        return $this->setChildren($merge);
    }

    /**
     * Given the position of the child to replace, and an `XMLElement`
     * of the replacement child, this function will replace one child
     * with another
     *
     * @since Symphony 2.2.2
     * @param integer $index
     *  The index of the child to be replaced. If the index given is negative
     *  it will be calculated from the end of `$this->children`.
     * @param XMLElement $child
     *  An XMLElement of the new child
     * @return XMLElement
     *  The current instance
     * @throws Exception
     *  If the $index is not an integer or the index is not valid.
     */
    public function replaceChildAt($index, XMLElement $child = null)
    {
        General::ensureType([
            'index' => ['var' => $index, 'type' => 'int'],
        ]);

        $this->validateChild($child);

        $index = $this->getRealIndex($index);

        if (!isset($this->children[$index])) {
            throw new Exception("Index out of range. No child at index `$index`.");
        }

        $this->children[$index] = $child;

        return $this;
    }

    /**
     * Given an `$index`, return the real index in `$this->children`
     * depending on if the value is negative or not. Negative values
     * will work from the end of an array.
     *
     * @since Symphony 2.2.2
     * @param integer $index
     *  Positive indexes are returned as is, negative indexes are deducted
     *  from the end of `$this->children`
     * @return integer
     */
    private function getRealIndex($index)
    {
        if ($index >= 0) {
            return $index;
        }

        return $this->getNumberOfChildren() + $index;
    }

    /**
     * This function strips characters that are not allowed in XML
     *
     * @since Symphony 2.3
     * @link http://www.w3.org/TR/xml/#charsets
     * @link http://www.phpedit.net/snippet/Remove-Invalid-XML-Characters
     * @param string $value
     * @return string
     */
    public static function stripInvalidXMLCharacters($value)
    {
        if (Lang::isUnicodeCompiled()) {
            return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $value);
        } else {
            $ret = '';

            if (empty($value)) {
                return $ret;
            }

            $length = strlen($value);

            for ($i=0; $i < $length; $i++) {
                $current = ord($value{$i});
                if (($current == 0x9) ||
                    ($current == 0xA) ||
                    ($current == 0xD) ||
                    (($current >= 0x20) && ($current <= 0xD7FF)) ||
                    (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                    (($current >= 0x10000) && ($current <= 0x10FFFF))) {
                    $ret .= chr($current);
                }
            }

            return $ret;
        }
    }

    /**
     * This function will turn the `XMLElement` into a string
     * representing the element as it would appear in the markup.
     * The result is valid XML.
     *
     * @param boolean $indent
     *  Defaults to false
     * @param integer $tab_depth
     *  Defaults to 0, indicates the number of tabs (\t) that this
     *  element should be indented by in the output string
     * @param boolean $has_parent
     *  Defaults to false, set to true when the children are being
     *  generated. Only the parent will output an XML declaration
     *  if `$this->includeHeader` is set to true.
     * @return string
     */
    public function generate($indent = false, $tab_depth = 0, $has_parent = false)
    {
        $result = null;
        $newline = ($indent ? PHP_EOL : null);

        if (!$has_parent) {
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

            if (is_array($this->processingInstructions) && !empty($this->processingInstructions)) {
                $result .= implode(PHP_EOL, $this->processingInstructions);
            }
        }

        $result .= ($indent ? str_repeat("\t", $tab_depth) : null) . '<' . $this->getName();

        $attributes = $this->getAttributes();

        if (!empty($attributes)) {
            foreach ($attributes as $attribute => $value) {
                $length = strlen($value);
                if ($length !== 0 || $length === 0 && $this->allowEmptyAttributes) {
                    $result .= sprintf(' %s="%s"', $attribute, $value);
                }
            }
        }

        $value = $this->getValue();
        $added_newline = false;

        if ($this->getNumberOfChildren() > 0 || strlen($value) !== 0 || !$this->selfClosing) {
            $result .= '>';

            foreach ($this->children as $i => $child) {
                if (!($child instanceof XMLElement)) {
                    $result .= $child;
                } else {
                    if ($added_newline === false) {
                        $added_newline = true;
                        $result .= $newline;
                    }

                    $child->setElementStyle($this->elementStyle);
                    $result .= $child->generate($indent, $tab_depth + 1, true);
                }
            }

            $result .= sprintf(
                "%s</%s>%s",
                ($indent && $added_newline ? str_repeat("\t", $tab_depth) : null),
                $this->getName(),
                $newline
            );

            // Empty elements:
        } else {
            if ($this->elementStyle === 'xml') {
                $result .= ' />';
            } elseif (in_array($this->name, XMLElement::$no_end_tags) || (substr($this->getName(), 0, 3) === '!--')) {
                $result .= '>';
            } else {
                $result .= sprintf("></%s>", $this->getName());
            }

            $result .= $newline;
        }

        return $result;
    }

    /**
     * Given a string of XML, this function will convert it to an `XMLElement`
     * object and return the result.
     *
     * @since Symphony 2.4
     * @param string $root_element
     * @param string $xml
     *  A string of XML
     * @return XMLElement
     */
    public static function convertFromXMLString($root_element, $xml)
    {
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->loadXML($xml);

        return self::convertFromDOMDocument($root_element, $doc);
    }

    /**
     * Given a `DOMDocument`, this function will convert it to an `XMLElement`
     * object and return the result.
     *
     * @since Symphony 2.4
     * @param string $root_element
     * @param DOMDocument $doc
     * @return XMLElement
     */
    public static function convertFromDOMDocument($root_element, DOMDocument $doc)
    {
        $xpath = new DOMXPath($doc);
        $root = new XMLElement($root_element);

        foreach ($xpath->query('.') as $node) {
            self::convertNode($root, $node);
        }

        return $root;
    }

    /**
     * This helper function is used by `XMLElement::convertFromDOMDocument`
     * to recursively convert `DOMNode` into an `XMLElement` structure
     *
     * @since Symphony 2.4
     * @param XMLElement $root
     * @param DOMNode $node
     * @return XMLElement
     */
    private static function convert(XMLElement $root, DOMNode $node)
    {
        $el = new XMLElement($node->tagName);

        self::convertNode($el, $node);

        $root->appendChild($el);
    }

    /**
     * Given a DOMNode, this function will help replicate it as an
     * XMLElement object
     *
     * @since Symphony 2.5.2
     * @param XMLElement $element
     * @param DOMNode $node
     */
    private static function convertNode(XMLElement $element, DOMNode $node)
    {
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $name => $attrEl) {
                $element->setAttribute($name, General::sanitize($attrEl->value));
            }
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $childNode) {
                if ($childNode instanceof DOMCdataSection) {
                    $element->setValue(General::wrapInCDATA($childNode->data));
                } elseif ($childNode instanceof DOMText) {
                    if ($childNode->isWhitespaceInElementContent() === false) {
                        $element->setValue(General::sanitize($childNode->data));
                    }
                } elseif ($childNode instanceof DOMElement) {
                    self::convert($element, $childNode);
                }
            }
        }
    }
}
