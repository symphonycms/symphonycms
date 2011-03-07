<?php

	/**
	 * @package toolkit
	 */
	/**
	 * The XMLElement class is a wrapper for PHPs DOMDocument
	 * and DOMElement classes. Each instance of the XMLElement uses
	 * an internal DOMElement class to store and build XML.
	 *
	 * Originally this class build and stored the XML on its own,
	 * but was not very memory efficient.
	 */
	class XMLElement {
		/**
		 * Used to set the document to output as XML valid.
		 * @var string
		 */
		const STYLE_XML = 'xml';

		/**
		 * Used to set the document to output as HTML valid.
		 * @var string
		 */
		const STYLE_HTML = 'html';

		/**
		 * A instance of DOMDocument as returned by DOMImplementation.
		 * @var DOMDocument
		 */
		static protected $document;

		/**
		 * An instance of the ReflectionClass on the DOMElement class.
		 * @var Reflection
		 */
		static protected $reflection;

		/**
		 * Prepare the XMLElement class by creating a DOMDocument.
		 * that can handle HTML entities.
		 */
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

		/**
		 * The DTD that should be output when a XMLElement is generated, defaults to null.
		 * @var string
		 */
		protected $documentType;

		/**
		 * An instance of DOMElement for this XMLElement.
		 * @var DOMElement
		 */
		protected $element;

		/**
		 * When set to true this will include the XML declaration will be
		 * output when the XML Element is generated. Defaults to false.
		 * @var boolean
		 */
		protected $includeHeader;

		/**
		 * Whether the XMLElement should be returned as a string of XML or HTML.
		 * @var string
		 */
		protected $outputStyle;

		/**
		 * The constructor for the XMLElement class takes params to either create
		 * a new XMLElement, or to set `$this->element` as a instance of DOMElement
		 *
		 * @param string|DOMElement $name
		 *	The name of the XMLElement, 'p', or a DOMElement object which makes the
		 *	other parameters optional.
		 * @param string|XMLElement $value (optional)
		 *	The value of this XMLElement, it can be a string or another XMLElement object.
		 * @param array $attributes (optional)
		 * 	Any additional attributes can be included in an associative array with
		 *	the key being the name and the value being the value of the attribute.
		 *	Attributes set from this array will override existing attributes
		 *	set by previous params.
		 * @return XMLElement
		 */
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

		/**
		 * Magic method exposes DOMElement functions to XMLElement
		 * allowing developers to interact with XMLElement as they
		 * would with DOMElement.
		 *
		 * @param string $name
		 *	The function name of DOMElement.
		 * @param array $args
		 *	The arguments to pass to the desired function.
		 * @return mixed
		 *	The result of the called method.
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
		 * Magic method for cloning of the XMLElement object,
		 * makes sure the inner DOMElement is also cloned.
		 */
		public function __clone() {
			$this->element = clone $this->element;
		}

		/**
		 * Magic method to return variables set via `__set`.
		 *
		 * @param string $name
		 * @return mixed
		 */
		public function __get($name) {
			return $this->element->{$name};
		}

		/**
		 * Magic method to set variables on `$this->element`. Keep in mind
		 * that `$this->element` is an instance of the DOMElement class.
		 *
		 * @param string $name
		 * @param string $value
		 */
		public function __set($name, $value) {
			$this->element->{$name} = $value;
		}

		/**
		 * A convenience method to add children to an XMLElement quickly.
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
		 * @param boolean $format
		 *	Defaults to false. Will fully indent XML, but only
		 *	wraps HTML onto new lines.
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
		 * Returns the number of children this XMLElement has.
		 * @return int
		 */
		public function getNumberOfChildren(){
			return count($this->childNodes);
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
		 *	Associative array with the key being the name and
		 *	the value being the value of the attribute.
		 */
		public function setAttributeArray(array $attributes) {
			foreach ($attributes as $name => $value) {
				$this->setAttribute($name, $value);
			}
		}

		/**
		 * Sets the DTD for this XMLElement.
		 *
		 * @param string $dtd
		 */
		public function setDTD($value) {
			$this->documentType = $value;
		}

		/**
		 * Change the output style of the XMLElement from am
		 * XML string to a HTML string.
		 *
		 * @param string $style (optional)
		 *	Either `XMLElement::STYLE_XML` or `STYLE_HTML`.
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
		 *	Defaults to false.
		 */
		public function setIncludeHeader($value = false){
			$this->includeHeader = $value;
		}

		/**
		 * @deprecated. Due to moving to DOMDocument internally, there is no
		 * need to have to explicitly set open/close values.
		 *
		 * Originally this function was used to prevent special HTML elements
		 * like the textarea element from using the self closing `<a />` tag
		 * format. Outputting as HTML now solves this automatically.
		 */
		public function setSelfClosingTag($value = true) {

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
	}

	XMLElement::initializeDocument();

?>