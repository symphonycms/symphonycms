<?php
	/**
	 * @package toolkit
	 */
	/**
	 * Page is an abstract class that holds an object representation
	 * of a page's headers.
	 */
	Abstract Class Page{
		/**
		 * The end-of-line constant.
		 * @var string
		 * @deprecated This will be removed in the next version of Symphony
		 */
		const CRLF = PHP_EOL;

		/**
		 * This stores the headers that will be sent when this page is
		 * generated as an associative array of header=>value.
		 * @var array 
		 */
		protected $_headers = array();

		/**
		 * Initialises the Page object by setting the headers to empty
		 */
		public function __construct(){
			$this->_headers = array();
		}

		/**
		 * Adds a header to the $_headers array using the $name
		 * as the key.
		 *
		 * @param string $name
		 *  The header name, eg. Content-Type.
		 * @param string $value (optional)
		 *  The value for the header, eg. text/xml. Defaults to null.
		 */
		public function addHeaderToPage($name, $value = null){
			$this->_headers[strtolower($name)] = $name . (is_null($value) ? null : ":{$value}");
		}

		/**
		 * Accessor function for `$_headers`
		 *
		 * @return array
		 */
		public function headers(){
			return $this->_headers;
		}

		/**
		 * This function calls `__renderHeaders()`.
		 *
		 * @see __renderHeaders()
		 */
		public function generate(){
			$this->__renderHeaders();
		}

		/**
		 * Iterates over the `$_headers` for this page
		 * and outputs them using PHP's header() function.
		 *
		 * @return result of header();
		 */
		protected function __renderHeaders(){
			if(!is_array($this->_headers) || empty($this->_headers)) return;

			foreach($this->_headers as $value){
				header($value);
			}
		}
	}
