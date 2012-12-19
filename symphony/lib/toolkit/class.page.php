<?php
	/**
	 * @package toolkit
	 */
	/**
	 * Page is an abstract class that holds an object representation
	 * of a page's headers.
	 */
	Abstract Class Page {
		
		// see: http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html for status codes
		
		/**
		 * Refers to the HTTP status code, 200 OK
		 * @var integer
		 */
		const HTTP_STATUS_OK = 200;
		
		/**
		 * Refers to the HTTP status code, 301 Moved Permanently
		 * @var integer
		 */
		const HTTP_STATUS_MOVED_PERMANENT = 301;
		
		/**
		 * Refers to the HTTP status code, 302 Found
		 * This is used as a temporary redirect
		 * @var integer
		 */
		const HTTP_STATUS_FOUND = 302;

		/**
		 * Refers to the HTTP status code, 400 Bad Request
		 * @var integer
		 */
		const HTTP_STATUS_BAD_REQUEST = 400;
		
		/**
		 * Refers to the HTTP status code, 401 Unauthorized
		 * @var integer
		 */
		const HTTP_STATUS_UNAUTHORIZED = 401;
		
		/**
		 * Refers to the HTTP status code, 403 Forbidden
		 * @var integer
		 */
		const HTTP_STATUS_FORBIDDEN = 403;
		
		/**
		 * Refers to the HTTP status code, 404 Not Found
		 * @var integer
		 */
		const HTTP_STATUS_NOT_FOUND = 404;
		

		/**
		 * Refers to the HTTP status code, 500 Internal Server Error
		 * @var integer
		 */
		const HTTP_STATUS_ERROR = 500;

		
		/**
		 * Keyed array of all the string
		 * @var Array
		 */
		protected static $HTTP_STATUSES = array (
			// 200
			HTTP_STATUS_OK => 'OK',
			// 300
			HTTP_STATUS_MOVED_PERMANENT => 'Moved Permanently',
			HTTP_STATUS_FOUND => 'Found',
			// 400
			HTTP_STATUS_BAD_REQUEST => 'Bad Request',
			HTTP_STATUS_UNAUTHORIZED => 'Unauthorized',
			HTTP_STATUS_FORBIDDEN => 'Forbidden',
			HTTP_STATUS_NOT_FOUND => 'Not Found',
			// 500
			HTTP_STATUS_ERROR => 'Internal Server Error',
		);
		
		public static final function getHttpStatusString($status_code) {
			$string_status = self::$HTTP_STATUSES[$status_code];
			if (!empty($string_status)) {
				return vsprintf('HTTP/1.1 %d %s', array($status_code, $string_status));
			}
			return NULL;
		}
		

		/**
		 * The HTTP status code of the page using the `HTTP_STATUSES` constants
		 * @var integer
		 */
		protected $_status;
		

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
		 * @param integer $response_code (optional)
		 *  The HTTP response code that should be set by PHP with this header, eg. 200
		 */
		public function addHeaderToPage($name, $value = null, $response_code = null) {
			$this->_headers[strtolower($name)] = array(
				'header' => $name . (is_null($value) ? null : ":{$value}"),
				'response_code' => $response_code
			);
		}

		/**
		 * Removes a header from the $_headers array using the $name
		 * as the key.
		 *
		 * @param string $name
		 *  The header name, eg. Expires.
		 */
		public function removeHeaderFromPage($name) {
			unset($this->_headers[strtolower($name)]);
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
		 */
		protected function __renderHeaders(){
			if(!is_array($this->_headers) || empty($this->_headers)) return;

			foreach($this->_headers as $value){
				if(isset($value['response_code'])) {
					//header($value['header'], true, $value['response_code']);
					header(self::getHttpStatusString($value['header']), true, $value['response_code']);
				}
				else {
					header($value['header']);
				}
			}
		}
	}
