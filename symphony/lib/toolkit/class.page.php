<?php
	/**
	 * @package toolkit
	 */
	/**
	 * Page is an abstract class that holds an object representation
	 * of a page's headers.
	 */
	Abstract Class Page {

		/**
		 * Refers to the HTTP status code, 200 OK
		 * @since 2.3.2
		 * @var integer
		 */
		const HTTP_STATUS_OK = 200;

		/**
		 * Refers to the HTTP status code, 301 Moved Permanently
		 * @since 2.3.2
		 * @var integer
		 */
		const HTTP_STATUS_MOVED_PERMANENT = 301;

		/**
		 * Refers to the HTTP status code, 302 Found
		 * This is used as a temporary redirect
		 * @since 2.3.2
		 * @var integer
		 */
		const HTTP_STATUS_FOUND = 302;

		/**
		 * Refers to the HTTP status code, 400 Bad Request
		 * @since 2.3.2
		 * @var integer
		 */
		const HTTP_STATUS_BAD_REQUEST = 400;

		/**
		 * Refers to the HTTP status code, 401 Unauthorized
		 * @since 2.3.2
		 * @var integer
		 */
		const HTTP_STATUS_UNAUTHORIZED = 401;

		/**
		 * Refers to the HTTP status code, 403 Forbidden
		 * @since 2.3.2
		 * @var integer
		 */
		const HTTP_STATUS_FORBIDDEN = 403;

		/**
		 * Refers to the HTTP status code, 404 Not Found
		 * @since 2.3.2
		 * @var integer
		 */
		const HTTP_STATUS_NOT_FOUND = 404;


		/**
		 * Refers to the HTTP status code, 500 Internal Server Error
		 * @since 2.3.2
		 * @var integer
		 */
		const HTTP_STATUS_ERROR = 500;


		/**
		 * Keyed array of all the string
		 * @since 2.3.2
		 * @var Array
		 */
		private static $HTTP_STATUSES = array (
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

		/**
		 *
		 * This method returns the string HTTP Status value.
		 * If `$status_code` is null, it returns all the values
		 * currently registered.
		 *
		 * @see: http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
		 *
		 * @since 2.3.2
		 *
		 * @param integer $status_code (optional)
		 *   The HTTP Status code to get the value for.
		 * @return mixed (Array | String)
		 */
		public static final function getHttpStatusValue($status_code=NULL) {
			if (!$status_code) {
				self::$HTTP_STATUSES;
			}
			return self::$HTTP_STATUSES[$status_code];
		}

		/**
		 *
		 * Sets the `$sting_value` for the specified `$status_code`.
		 * If `$sting_value` is null, the `$status_code` is removed from
		 * the array.
		 *
		 * This allow developers to register customs HTTP_STATUS into the
		 * static `Page::$HTTP_STATUSES` array and use `$page->setHttpStatus()`.
		 *
		 * @since 2.3.2
		 *
		 * @param integer $status_code
		 *  The HTTP Status numeric code.
		 * @param string $string_value
		 *  The HTTP Status string value.
		 */
		public static final function setHttpStatusValue($status_code, $string_value) {
			if (!$string_value) {
				unset(self::$HTTP_STATUSES[$status_code]);
			} else if (is_int($status_code) && $status_code >= 100 && $status_code < 600) {
				self::$HTTP_STATUSES[$status_code] = $string_value;
			} else {
				// Throw error ?
			}
		}

		/**
		 * The HTTP status code of the page using the `HTTP_STATUSES` constants
		 *
		 * @deprecated @since 2.3.2
		 * @see $this->setHttpStatus and self::$HTTP_STATUSES
		 *
		 * @var integer
		 */
		protected $_status = NULL;

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
		 *
		 * Shorthand for `addHeaderToPage` in order to set the
		 * HTTP Status header.
		 *
		 * @since 2.3.2
		 *
		 * @param integer $status_code
		 *   The HTTP Status numeric value.
		 */
		public function setHttpStatus($status_code) {
			$this->addHeaderToPage('Status', NULL, $status_code);
			// Assure we clear the legacy value
			$this->_status = NULL;
		}

		/**
		 *
		 * Gets the current HTTP Status.
		 * If none is set, it assumes HTTP_STATUS_OK
		 *
		 * @since 2.3.2
		 *
		 * @return integer
		 */
		public function getHttpStatusCode() {
			// Legacy check
			if ($this->_status != NULL) {
				$this->setHttpStatus($this->_status);
			}

			if (isset($this->_headers['status'])) {
				return $this->_headers['status']['response_code'];
			}
			return self::HTTP_STATUS_OK;
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

			// Legacy check
			if ($this->_status != NULL) {
				$this->setHttpStatus($this->_status);
			}

			foreach($this->_headers as $key => $value){
				// If this is the http status
				if($key == 'status' && isset($value['response_code'])) {
					$res_code = intval($value['response_code']);
					// See https://github.com/symphonycms/symphony-2/issues/1558#issuecomment-10663716
					// for explanation of the format
					header(
						vsprintf("Status: %d %s", $res_code, self::getHttpStatusValue($res_code)),
						true,
						$res_code
						);
				}
				else {
					header($value['header']);
				}
			}
		}
	}
