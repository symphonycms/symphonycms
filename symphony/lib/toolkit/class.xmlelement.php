<?php
	/**
	 * @package toolkit
	 */
	/**
	 * XMLElement is a class used to simulate PHP's DOMElement
	 * class. Each object is a representation of a HTML element
	 * and can store it's children in an array. When an XMLElement
	 * is generated, it is output as an XML string.
	 *
	 * @deprecated Symphony 3 will use PHP's DOM classes.
	 */
	Class XMLElement {
		/**
		 * @var string The end-of-line constant.
		 * @deprecated This will no longer exist in Symphony 3
		 */
		const CRLF = PHP_EOL;

		/**
		 * @var array This is an array of all HTML elements that are
		 * self closing.
		 */
		protected static $no_end_tags = array(
			'area', 'base', 'basefont', 'br', 'col', 'frame', 'hr', 'img',
			'input', 'isindex', 'link', 'meta', 'param'
		);

		/**
		 * @var string The name of the HTML Element, eg. 'p'
		 */
		private	$_name;

		/**
		 * @var string|XMLElement The value of this XMLElement, it can be a string
		 *  or another XMLElement object.
		 */
		private	$_value;

		/**
		 * @var array $_attributes Any additional attributes can be included in
		 *  an associative array with the key being the name and the value being
		 *  the value of the attribute.
		 */
		private	$_attributes = array();

		/**
		 * @var array(XMLElement) Any children of this element as XMLElements
		 */
		private	$_children = array();

		/**
		 * @var array Any processing instructions that the XSLT should
		 *  know about when a XMLElement is generated
		 */
		private $_processingInstructions = array();

		/**
		 * @var string The DTD the should be output when a XMLElement is
		 *  generated, defaults to null.
		 */
		private $_dtd = null;

		/**
		 * @var string The encoding of the XMLElement, defaults to 'utf-8'
		 */
		private $_encoding = 'utf-8';

		/**
		 * @var string The version of the XML that is used for generation,
		 * 	defaults to '1.0'
		 */
		private $_version = '1.0';

		/**
		 * @var string The type of element, defaults to 'xml'. Used when
		 *  determining the style of end tag for this element when generated
		 */
		private $_elementStyle = 'xml';

		/**
		 * @var boolean When set to true this will include the XML declaration
		 *  will be output when the XML Element is generated. Defaults
		 *  to false.
		 */
		private $_includeHeader = false;

		/**
		 * @var boolean Specifies whether this HTML element has an closing
		 *  element, or if it self closing. Defaults to true.
		 *  eg. <p></p> or <input />
		 */
		private $_selfclosing = true;

		/**
		 * @var boolean Specifies whether attributes need to have a value
		 *  or if they can be shorthand. Defaults to true.
		 *  An example of this would be:
		 *  <option selected>Value</option>
		 */
		private $_allowEmptyAttributes = true;

		/**
		 * @var boolean Defaults to false, which puts the value before any
		 * 	children elements. Setting to true will append any
		 *  children first, then add the value to the current
		 *  XMLElement
		 */
		private $_placeValueAfterChildElements = false;

		/**
		 * The constructor for the XMLElement
		 *
		 * @param string $name
		 *  The name of the XMLElement, 'p'
		 * @param string|XMLElement $value (optional)
		 *  The value of this XMLElement, it can be a string
		 *  or another XMLElement object.
		 * @param array $attributes (optional)
		 *  Any additional attributes can be included in an associative array with
		 *  the key being the name and the value being the value of the attribute.
		 *  Attributes set from this array will override existing attributes
		 *  set by previous params.
		 * @return XMLElement
		 */
		public function __construct($name, $value = null, Array $attributes = array()){

			$this->_name = $name;
			$this->setValue($value);

			if(is_array($attributes) && !empty($attributes)) {
				$this->setAttributeArray($attributes);
			}
		}

		/**
		 * Accessor for $_name
		 *
		 * @return string
		 */
		public function getName(){
			return $this->_name;
		}

		/**
		 * Accessor for $_value
		 *
		 * @return string|XMLElement
		 */
		public function getValue(){
			return $this->_value;
		}

		/**
		 * Accessor for $_attributes
		 *
		 * @return array
		 */
		public function getAttributes(){
			return $this->_attributes;
		}

		/**
		 * Accessor for $_children
		 *
		 * @return array
		 */
		public function getChildren(){
			return $this->_children;
		}

		/**
		 * Adds processing instructions to this XMLElement
		 *
		 * @param string $pi
		 */
		public function addProcessingInstruction($pi){
			$this->_processingInstructions[] = $pi;
		}

		/**
		 * Sets the DTD for this XMLElement
		 *
		 * @param string $dtd
		 */
		public function setDTD($dtd){
			$this->_dtd = $dtd;
		}

		/**
		 * Sets the encoding for this XMLElement for when
		 * it's generated.
		 *
		 * @param string $value
		 */
		public function setEncoding($value){
			$this->_encoding = $value;
		}

		/**
		 * Sets the version for the XML declaration of this
		 * XMLElement
		 *
		 * @param string $value
		 */
		public function setVersion($value){
			$this->_version = $value;
		}

		/**
		 * Sets the style of the XMLElement. Used when the
		 * XMLElement is being generated to determine whether
		 * needs to be closed, is self closing or is standalone.
		 *
		 * @param string $style (optional)
		 *  Defaults to 'xml', any other value will trigger the
		 *  XMLElement to be closed by itself or left standalone
		 *  if it is in the XMLElement::no_end_tags.
		 */
		public function setElementStyle($style='xml'){
			$this->_elementStyle = $style;
		}

		/**
		 * Sets whether this XMLElement needs to output an
		 * XML declaration or not. This normally is only set to
		 * true for the parent XMLElement, eg. 'html'.
		 *
		 * @param string $value (optional)
		 *  Defaults to false
		 */
		public function setIncludeHeader($value = false){
			$this->_includeHeader = $value;
		}

		/**
		 * Sets whether this XMLElement is self closing or not.
		 *
		 * @param string $value (optional)
		 *  Defaults to true
		 */
		public function setSelfClosingTag($value = true){
			$this->_selfclosing = $value;
		}

		/**
		 * Specifies whether attributes need to have a value
		 * or if they can be shorthand on this XMLElement.
		 *
		 * @param string $value (optional)
		 *  Defaults to true
		 */
		public function setAllowEmptyAttributes($value = true){
			$this->_allowEmptyAttributes = $value;
		}

		/**
		 * Sets the value of the XMLElement. Checks to see
		 * whether the value should be prepended or appended
		 * to the children.
		 *
		 * @param string|XMLElement $value
		 * @param boolean $prepend (optional)
		 *  Defaults to true.
		 */
		public function setValue($value, $prepend=true){
			if(!$prepend) $this->_placeValueAfterChildElements = true;
			$this->_value = $value;
		}

		/**
		 * Sets an attribute
		 *
		 * @param string $name
		 *  The name of the attribute
		 * @param string $value
		 *  The value of the attribute
		 */
		public function setAttribute($name, $value){
			$this->_attributes[$name] = $value;
		}

		/**
		 * A convience method to quickly add multiple
		 * attributes to an XMLElement
		 *
		 * @param array $attributes
		 *  Associative array with the key being the name and
		 *  the value being the value of the attribute.
		 */
		public function setAttributeArray(Array $attributes = null){
			if(!is_array($attributes) || empty($attributes)) return;

			foreach($attributes as $name => $value)
				$this->setAttribute($name, $value);
		}

		/**
		 * Retrieves the value of an attribute by name
		 *
		 * @param string $name
		 * @return string
		 */
		public function getAttribute($name){
			if(!isset($this->_attributes[$name])) return null;
			return $this->_attributes[$name];
		}

		/**
		 * Adds an XMLElement to the children array
		 *
		 * @param XMLElement $child
		 */
		public function appendChild(XMLElement $child){
			$this->_children[] = $child;
		}

		/**
		 * A convience method to add children to an XMLElement
		 * quickly.
		 *
		 * @param Array(XMLElement) $children
		 */
		public function appendChildArray(Array $children = null){
			if(is_array($children) && !empty($children)) {
				foreach($children as $child)
					$this->appendChild($child);
			}
		}

		/**
		 * Adds an XMLElement to the start of the children
		 * array, this will mean it is output before any other
		 * children when the XMLElement is generated
		 *
		 * @param XMLElement $child
		 */
		public function prependChild(XMLElement $child){
			array_unshift($this->_children, $child);
		}

		/**
		 * Returns the number of children this XMLElement has.
		 * @return int
		 */
		public function getNumberOfChildren(){
			return count($this->_children);
		}

		/**
		 * This function will turn the XMLElement into a string
		 * representing the element as it would appear in the markup.
		 * It is valid XML.
		 *
		 * @param boolean $indent
		 *  Defaults to false
		 * @param int $tab_depth
		 *  Defaults to 0, indicates the number of tabs (\t) that this
		 *  element should be indented by in the output string
		 * @param boolean $hasParent
		 *  Defaults to false, set to true when the children are being
		 *  generated. Only the parent will output an XML declaration
		 *  if _includeHeader is set to true.
		 * @return string
		 */
		public function generate($indent = false, $tab_depth = 0, $hasParent = false){

			$result = null;
			$newline = ($indent ? self::CRLF : null);

			if(!$hasParent){
				if($this->_includeHeader){
					$result .= sprintf(
						'<?xml version="%s" encoding="%s" ?>%s',
						$this->_version, $this->_encoding, $newline
					);
				}

				if($this->_dtd) {
					$result .= $this->_dtd . $newline;
				}

				if(is_array($this->_processingInstructions) && !empty($this->_processingInstructions)){
					$result .= implode(self::CRLF, $this->_processingInstructions);
				}
			}

			$result .= ($indent ? str_repeat("\t", $tab_depth) : null) . '<' . $this->getName();

			$attributes = $this->getAttributes();
			if(!empty($attributes)){
				foreach($attributes as $attribute => $value ){
					if(strlen($value) != 0 || (strlen($value) == 0 && $this->_allowEmptyAttributes)){
						$result .= sprintf(' %s="%s"', $attribute, $value);
					}
				}
			}

			$numberOfchildren = $this->getNumberOfChildren();

			if($numberOfchildren > 0 || strlen($this->_value) != 0 || !$this->_selfclosing){

				$result .= '>';

				if(!is_null($this->getValue()) && !$this->_placeValueAfterChildElements) {
					$result .= $this->getValue();
				}

				if($numberOfchildren > 0 ){
					$result .= $newline;

					foreach($this->_children as $child ){
						if(!($child instanceof XMLElement)) {
							throw new Exception('Child is not of type XMLElement');
						}
						$child->setElementStyle($this->_elementStyle);
						$result .= $child->generate($indent, $tab_depth + 1, true);
					}

					if($indent) $result .= str_repeat("\t", $tab_depth);
				}

				if(!is_null($this->getValue()) && $this->_placeValueAfterChildElements){
					if($indent) $result .= str_repeat("\t", max(1, $tab_depth));
					$result .= $this->getValue() . $newline;
				}

				$result .= sprintf("</%s>%s", $this->getName(), $newline);

			}

			// Empty elements:
			else {
				if ($this->_elementStyle == 'xml') {
					$result .= ' />';
				}
				else if (in_array($this->_name, XMLElement::$no_end_tags) || (substr($this->getName(), 0, 3) == '!--')) {
					$result .= '>';
				}
				else {
					$result .= sprintf("></%s>", $this->getName());
				}

				$result .= $newline;
			}

			return $result;
		}
	}
