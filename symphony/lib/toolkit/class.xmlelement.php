<?php

	/**
	 * @package toolkit
	 */
	/**
	 * XMLElement is a class used to simulate PHP's DOMDocument
	 * class. Each object is a representation of a HTML element
	 * and can store it's children in an array. When an XMLElement
	 * is generated, it is output as an XML string.
	 */
	class XMLElement {
		/**
		 * A instance of DOMDocument as returned by DOMImplementation
		 * @var DOMDocument
		 */
		static protected $document;

		/**
		 * An instance of the ReflectionClass on the DOMElement class
		 * @var Reflection
		 */
		static protected $reflection;

		/**
		 * An instance of DOMElement for this XMLElement
		 * @var DOMElement
		 */
		protected $element = null;

		/**
		 * The DTD the should be output when a XMLElement is generated, defaults to null.
		 * @var string
		 */
		protected $documentType = null;

		/**
		 * When set to true this will include the XML declaration will be
		 * output when the XML Element is generated. Defaults to false.
		 * @var boolean
		 */
		protected $includeHeader = false;

		/**
		 * Whether the XMLElement should be returned as a string of HTML
		 * or as a DOMElement. Defaults to false.
		 * @var boolean
		 */
		protected $outputAsHTML = false;

		/**
		 * The constructor for the XMLElement class takes params to either create
		 * a new XMLElement, or to set `$this->element` as a instance of DOMElement
		 *
		 * @param string|DOMElement $name
		 *  The name of the XMLElement, 'p', or a DOMElement object which makes the
		 *  other parameters optional.
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
		public function __construct($name, $value = null, array $attributes = null) {
			if (!isset(self::$document)) {
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

				// Force entities to be loaded:
				$document->appendChild(
					$document->createElement('data')
				);
				$document->validate();

				self::$document = $document;
				self::$reflection = new ReflectionClass('DOMElement');
			}

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
		}

		/**
		 * Magic method exposes DOMElement functions to XMLElement
		 * allowing developers to interact with XMLElement as they
		 * would with DOMElement
		 *
		 * @param string $name
		 *  The function name of DOMElement
		 * @param array $args
		 *  The arguments to pass to the desired function
		 * @return mixed
		 *  The result of the called method.
		 */
		public function __call($name, $args) {
			$method = self::$reflection->getMethod($name);

			foreach ($args as $index => $value) {
				if (!$value instanceof self) continue;

				$args[$index] = $value->element;
			}

			return $method->invokeArgs($this->element, $args);
		}

		/**
		 * Magic method for cloning of the XMLElement object
		 */
		public function __clone() {
			$this->element = clone $this->element;
		}

		/**
		 * Magic method to set variables on `$this->element`. Keep in mind
		 * that `$this->element` is an instance of the DOMElement class
		 *
		 * @param string $name
		 * @param string $value
		 */
		public function __set($name, $value) {
			$this->element->{$name} = $value;
		}

		/**
		 * Magic method to return variables set via `__set`
		 *
		 * @param string $name
		 * @return mixed
		 */
		public function __get($name) {
			return $this->element->{$name};
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
		 * Sets the value of the XMLElement. Checks to see
		 * whether the value should be prepended or appended
		 * to the children.
		 *
		 * @param string|XMLElement $value
		 */
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
		 * This function will turn the XMLElement into a string
		 * representing the element as it would appear in the markup.
		 * It is valid XML.
		 *
		 * @param boolean $indent
		 *  Defaults to false. Not fully implemented.
		 * @return string
		 */
		public function generate($indent = false) {
			self::$document->formatOutput = $indent;
			$output = $this->element->ownerDocument->saveXML($this->element);
			self::$document->formatOutput = false;

			/**
			* @todo find a better way of handling this error:
			* "Couldn't fetch DOMElement. Node no longer exists"
			*/

			if ($this->documentType) {
				$output = $this->documentType
					. ($indent ? PHP_EOL : null)
					. $output;
			}

			if ($this->includeHeader) {
				$output = '<?xml version="1.0" encoding="utf-8" ?>'
					. ($indent ? PHP_EOL : null)
					. $output;
			}

			return $output;
		}

		/**
		 * @deprecated. This function is no longer required.
		 */
		public function setElementStyle($style = 'xml') {
			$this->outputAsHTML = ($style == 'html');
		}

		/**
		 * @deprecated. Due to moving to DOMDocument internally, there is no
		 * need to have to explicitly set open/close values.
		 */
		public function setSelfClosingTag($value = true) {
			return true;
		}

	}

?>