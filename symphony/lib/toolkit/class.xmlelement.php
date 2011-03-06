<?php
	
	/**
	 * @package toolkit
	 */
	/**
	 * XMLElement is a class used to simulate PHP's DOMElement
	 * class. Each object is a representation of a HTML element
	 * and can store it's children in an array. When an XMLElement
	 * is generated, it is output as an XML string.
	 */
	class XMLElement {
		static protected $document;
		static protected $reflection;
		
		static public function initializeDocument() {
			$imp = new DOMImplementation;
			$dtd = $imp->createDocumentType(
				'data', null, 'symphony/assets/entities.dtd'
			);
			$document = $imp->createDocument(null, null, $dtd);
			$document->recover = true;
			$document->resolveExternals = true;
			$document->strictErrorChecking = false;
			$document->formatOutput = false;
			$document->substituteEntities = true;
			
			// Set encoding and XML version:
			$document->encoding = 'UTF-8';
			$document->xmlVersion = '1.0';
			
			// Force entities to be loaded:
			$document->appendChild(
				$document->createElement('data')
			);
			$document->validate();
			
			self::$document = $document;
			self::$reflection = new ReflectionClass('DOMElement');
		}
		
		const STYLE_XML = 'xml';
		const STYLE_HTML = 'html';
		
		protected $element;
		protected $documentType;
		protected $includeHeader;
		protected $outputStyle;
		
		public function __construct($name, $value = null, array $attributes = null) {
			if ($name instanceof DOMElement) {
				$this->element = $name;
			}
			
			else if (is_string($name)) {
				$this->element = self::$document->createElement($name);
				$this->setValue($value);
				
				if (is_array($attributes)) {
					$this->setAttributeArray($attributes);
				}
			}
			
			else {
				throw new Exception('Expecting string or DOMElement.');
			}
			
			$this->includeHeader = false;
			$this->outputStyle = self::STYLE_XML;
		}
		
		public function __call($name, $args) {
			$method = self::$reflection->getMethod($name);
			
			foreach ($args as $index => $value) {
				if (!$value instanceof self) continue;
				
				$args[$index] = $value->element;
			}
			
			return $method->invokeArgs($this->element, $args);
		}
		
		public function __clone() {
			$this->element = clone $this->element;
		}
		
		public function __get($name) {
			return $this->element->{$name};
		}
		
		public function __set($name, $value) {
			$this->element->{$name} = $value;
		}
		
		/**
		 * A convenience method to add children to an XMLElement
		 * quickly.
		 *
		 * @param array $children
		 */
		public function appendChildArray(array $children) {
			foreach ($children as $child) {
				$this->appendChild($child);
			}
		}
		
		/**
		 * Return all child elements
		 *
		 * @return array
		 */
		public function getChildren() {
			$children = array();
			
			foreach ($this->childNodes as $node) {
				if (!$node instanceof DOMElement) continue;
				
				$children[] = new self($node);
			}
			
			return $children;
		}
		
		/**
		 * Return the inner element
		 *
		 * @return DOMElement
		 */
		public function getElement() {
			return $this->element;
		}
		
		/**
		 * Return the element name
		 *
		 * @return string
		 */
		public function getName() {
			return $this->nodeName;
		}
		
		/**
		 * Return the element value
		 *
		 * @return string
		 */
		public function getValue() {
			if (!$this->hasChildNodes()) return null;
			
			$value = null;
			
			foreach ($this->childNodes as $node) {
				$value .= self::$document->saveXML($node);
			}
			
			return $value;
		}
		
		/**
		 * This function will turn the XMLElement into a string
		 * representing the element as it would appear in the markup.
		 * It is valid XML.
		 *
		 * @param boolean $format
		 *  Defaults to false. Will fully indent XML, but only
		 *  wraps HTML onto new lines.
		 * @return string
		 */
		public function generate($format = false) {
			if ($this->outputStyle == self::STYLE_XML) {
				self::$document->formatOutput = $format;
				$output = self::$document->saveXML($this->element);
				self::$document->formatOutput = false;
			}
			
			else if ($this->outputStyle = self::STYLE_HTML) {
				$document = new DOMDocument(
					self::$document->xmlVersion,
					self::$document->encoding
				);
				
				$element = $document->importNode($this->element, true);
				$document->appendChild($element);
				
				$document->formatOutput = $format;
				$output = $document->saveHTML();
				$document->formatOutput = false;
			}
			
			else {
				throw new Exception('Unknown output style.');
			}
			
			if ($this->documentType) {
				$output = $this->documentType
					. PHP_EOL . $output;
			}
			
			if ($this->includeHeader) {
				$output = sprintf(
					'<?xml version="%s" encoding="%s" ?>%s',
					self::$document->xmlVersion,
					self::$document->encoding,
					PHP_EOL . $output
				);
			}
			
			return $output;
		}
		
		/**
		 * Adds an XMLElement to the start of the children
		 * array, this will mean it is output before any other
		 * children when the XMLElement is generated
		 *
		 * @param XMLElement $child
		 */
		public function prependChild($child) {
			if (is_null($this->firstChild)) {
				$this->appendChild($child);
			}
			
			else {
				$this->insertBefore($child, $this->firstChild);
			}
		}
		
		/**
		 * Before passing onto the DOM Element we must decode
		 * all HTML entities.
		 *
		 * @param string $name
		 * @param string $value
		 */
		public function setAttribute($name, $value) {
			$this->element->setAttribute($name, html_entity_decode($value));
		}
		
		/**
		 * A convenience method to quickly add multiple attributes to
		 * an XMLElement
		 *
		 * @param array $attributes
		 *  Associative array with the key being the name and
		 *  the value being the value of the attribute.
		 */
		public function setAttributeArray(array $attributes) {
			foreach ($attributes as $name => $value) {
				$this->setAttribute($name, $value);
			}
		}
		
		/**
		 * Sets the DTD for this XMLElement
		 *
		 * @param string $dtd
		 */
		public function setDTD($value) {
			$this->documentType = $value;
		}
		
		/**
		 * Deprecated.
		 */
		public function setElementStyle($style = 'xml') {
			$this->outputStyle = $style;
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
			$this->includeHeader = $value;
		}
		
		/**
		 * Deprecated.
		 */
		public function setSelfClosingTag($value = true) {
			
		}
		
		public function setValue($value) {
			if (is_null($value) || $value == '') return;
			
			// Remove current children:
			$this->nodeValue = '';
			
			// Repair broken entities:
			$value = preg_replace('%&(?!(#x?)?[0-9a-z]+;)%i', '&amp;', $value);
			
			$fragment = self::$document->createDocumentFragment();
			$fragment->appendXML($value);
			
			if ($fragment->hasChildNodes()) {
				$this->appendChild($fragment);
			}
		}
	}
	
	XMLElement::initializeDocument();
	
?>