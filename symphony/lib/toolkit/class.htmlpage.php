<?php
	/**
	 * @package toolkit
	 */
	/**
	 * HTMLPage extends the Page class to provide an object representation
	 * of a Symphony backend page.
	 */

	require_once(TOOLKIT . '/class.page.php');

	Class HTMLPage extends Page{
		/**
		 * An XMLElement object for the `<html>` element. This is the parent
		 * DOM element for all other elements on the output page.
		 * @var XMLElement
		 */
		public $Html = null;

		/**
		 * An XMLElement object for the `<head>`
		 * @var XMLElement
		 */
		public $Head = null;

		/**
		 * An XMLElement object for the `<body>`
		 * @var XMLElement
		 */
		public $Body = null;

		/**
		 * An XMLElement object for the `<form>`. Most Symphony backend pages
		 * are contained within a main form
		 * @var XMLElement
		 */
		public $Form = null;

		/**
		 * This holds all the elements that will eventually be in the `$Head`.
		 * This allows extensions to add elements at certain indexes so
		 * resource dependancies can be met, and duplicates can be removed.
		 * Defaults to an empty array.
		 * @var array
		 */
		protected $_head = array();

		/**
		 * Constructor for the HTMLPage. Intialises the class variables with
		 * empty instances of XMLElement
		 */
		public function __construct(){
			parent::__construct();

			$this->Html = new XMLElement('html');
			$this->Html->setIncludeHeader(false);

			$this->Head = new XMLElement('head');

			$this->Body = new XMLElement('body');
		}

		/**
		 * Setter function for the `<title>` of a backend page. Uses the
		 * `addElementToHead()` function to place into the `$this->_head` array.
		 *
		 * @see addElementToHead()
		 * @param string $title
		 * @return int
		 *  Returns the position that the title has been set in the $_head
		 */
		public function setTitle($title){
			return $this->addElementToHead(
				new XMLElement('title', $title)
			);
		}

		/**
		 * The generate function calls the `__build()` function before appending
		 * all the current page's headers and then finally calling the `$Html's`
		 * generate function which generates a HTML DOM from all the
		 * XMLElement children.
		 *
		 * @return string
		 */
		public function generate(){
			$this->__build();
			parent::generate();
			return $this->Html->generate(true);
		}

		/**
		 * Called when page is generated, this function appends the `$Head`,
		 * `$Form` and `$Body` elements to the `$Html`.
		 *
		 * @see __generateHead()
		 */
		protected function __build(){
			$this->__generateHead();
			$this->Html->appendChild($this->Head);
			$this->Html->appendChild($this->Body);
		}

		/**
		 * Sorts the `$this->_head` elements by key, then appends them to the
		 * `$Head` XMLElement in order.
		 */
		protected function __generateHead(){
			ksort($this->_head);

			foreach($this->_head as $position => $obj){
				if(is_object($obj)) $this->Head->appendChild($obj);
			}
		}

		/**
		 * Adds an XMLElement to the `$this->_head` array at a desired position.
		 * If no position is given, the object will be added to the end
		 * of the `$this->_head` array. If that position is already taken, it will
		 * add the object at the next available position.
		 *
		 * @see toolkit.General#array_find_available_index()
		 * @param XMLElement $object
		 * @param integer $position
		 *  Defaults to null which will put the `$object` at the end of the
		 *  `$this->_head`.
		 * @return integer
		 *  Returns the position that the `$object` has been set in the `$this->_head`
		 */
		public function addElementToHead(XMLElement $object, $position = null){
			if(($position && isset($this->_head[$position]))) {
				$position = General::array_find_available_index($this->_head, $position);
			}
			else if(is_null($position)) {
				if(count($this->_head) > 0)
					$position = max(array_keys($this->_head))+1;
				else
					$position = 0;
			}

			$this->_head[$position] = $object;

			return $position;
		}

		/**
		 * Given an elementName, this function will remove the corresponding
		 * XMLElement from the `$this->_head`
		 *
		 * @param string $elementName
		 */
		public function removeFromHead($elementName){
			foreach($this->_head as $position => $element){
				if($element->getName() != $elementName) continue;

				unset($this->_head[$index]);
			}
		}

		/**
		 * Determines if two elements are duplicates based on an attribute
		 * and value
		 *
		 * @param string $value
		 *  The value of the attribute
		 * @param string $attribute
		 *  The attribute to check
		 * @return boolean
		 */
		public function checkElementsInHead($path, $attribute){
			foreach($this->_head as $element) {
				if(basename($element->getAttribute($attribute)) == basename($path)) return true;
			}
			return false;
		}

		/**
		 * Convenience function to add a `<script>` element to the `$this->_head`. By default
		 * the function will allow duplicates to be added to the `$this->_head`. A duplicate
		 * is determined by if the `$path` is unique.
		 *
		 * @param string $path
		 *  The path to the script file
		 * @param integer $position
		 *  The desired position that the resulting XMLElement will be placed
		 *  in the `$this->_head`. Defaults to null which will append to the end.
		 * @param boolean $duplicate
		 *  When set to false the function will only add the script if it doesn't
		 *  already exist. Defaults to true which allows duplicates.
		 * @return integer
		 *  Returns the position that the script has been set in the `$this->_head`
		 */
		public function addScriptToHead($path, $position = null, $duplicate = true){
			if($duplicate === true || ($duplicate === false && $this->checkElementsInHead($path, 'src') === false)){
				$script = new XMLElement('script');
				$script->setSelfClosingTag(false);
				$script->setAttributeArray(array('type' => 'text/javascript', 'src' => $path));

				return $this->addElementToHead($script, $position);
			}
		}

		/**
		 * Convenience function to add a stylesheet to the `$this->_head` in a `<link>` element.
		 * By default the function will allow duplicates to be added to the `$this->_head`.
		 * A duplicate is determined by if the `$path` is unique.
		 *
		 * @param string $path
		 *  The path to the stylesheet file
		 * @param string $type
		 *  The media attribute for this stylesheet, defaults to 'screen'
		 * @param integer $position
		 *  The desired position that the resulting XMLElement will be placed
		 *  in the `$this->_head`. Defaults to null which will append to the end.
		 * @param boolean $duplicate
		 *  When set to false the function will only add the script if it doesn't
		 *  already exist. Defaults to true which allows duplicates.
		 * @return integer
		 *  Returns the position that the stylesheet has been set in the `$this->_head`
		 */
		public function addStylesheetToHead($path, $type = 'screen', $position = null, $duplicate = true){
			if($duplicate === true || ($duplicate === false && $this->checkElementsInHead($path, 'href') === false)){
				$link = new XMLElement('link');
				$link->setAttributeArray(array('rel' => 'stylesheet', 'type' => 'text/css', 'media' => $type, 'href' => $path));

				return $this->addElementToHead($link, $position);
			}
		}

		/**
		 * This function builds a HTTP query string from `$_GET` parameters with
		 * the option to remove parameters with an `$exclude` array
		 *
		 * @param array $exclude
		 *  A simple array with the keys that should be omitted in the resulting
		 *  query string.
		 * @return string
		 */
		public function __buildQueryString(Array $exclude=array()){
			$exclude[] = 'page';

			// Generate the full query string and then parse it back to an array
			$pre_exclusion = http_build_query($_GET, null, '&');
			parse_str($pre_exclusion, $query);

			// Remove the excluded keys from query string and then build
			// the query string again
			$post_exclusion = array_diff_key($query, array_fill_keys($exclude, true));

			return urldecode(http_build_query($post_exclusion, null, '&'));
		}

	}
