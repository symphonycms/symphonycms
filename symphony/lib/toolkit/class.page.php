<?php
/**
 * @package toolkit
 */
/**
 * Page is an abstract class that holds an object representation
 * of a page's headers.
 */
abstract class Page
{
    /**
     * Refers to the HTTP status code, 200 OK
     *
     * @since Symphony 2.3.2
     * @var integer
     */
    const HTTP_STATUS_OK = 200;

    /**
     * Refers to the HTTP status code, 301 Moved Permanently
     *
     * @since Symphony 2.3.2
     * @var integer
     */
    const HTTP_STATUS_MOVED_PERMANENT = 301;

    /**
     * Refers to the HTTP status code, 302 Found
     * This is used as a temporary redirect
     *
     * @since Symphony 2.3.2
     * @var integer
     */
    const HTTP_STATUS_FOUND = 302;

    /**
     * Refers to the HTTP status code, 304 Not Modified
     *
     * @since Symphony 2.3.2
     * @var integer
     */
    const HTTP_NOT_MODIFIED = 304;

    /**
     * Refers to the HTTP status code, 400 Bad Request
     *
     * @since Symphony 2.3.2
     * @var integer
     */
    const HTTP_STATUS_BAD_REQUEST = 400;

    /**
     * Refers to the HTTP status code, 401 Unauthorized
     *
     * @since Symphony 2.3.2
     * @var integer
     */
    const HTTP_STATUS_UNAUTHORIZED = 401;

    /**
     * Refers to the HTTP status code, 403 Forbidden
     *
     * @since Symphony 2.3.2
     * @var integer
     */
    const HTTP_STATUS_FORBIDDEN = 403;

    /**
     * Refers to the HTTP status code, 404 Not Found
     *
     * @since Symphony 2.3.2
     * @var integer
     */
    const HTTP_STATUS_NOT_FOUND = 404;

    /**
     * Refers to the HTTP status code, 500 Internal Server Error
     *
     * @since Symphony 2.3.2
     * @var integer
     */
    const HTTP_STATUS_ERROR = 500;

    /**
     * Keyed array of all the string
     *
     * @since Symphony 2.3.2
     * @var array
     */
    public static $HTTP_STATUSES = array(
        // 200
        self::HTTP_STATUS_OK => 'OK',
        // 300
        self::HTTP_STATUS_MOVED_PERMANENT => 'Moved Permanently',
        self::HTTP_STATUS_FOUND => 'Found',
        self::HTTP_NOT_MODIFIED => 'Not Modified',
        // 400
        self::HTTP_STATUS_BAD_REQUEST => 'Bad Request',
        self::HTTP_STATUS_UNAUTHORIZED => 'Unauthorized',
        self::HTTP_STATUS_FORBIDDEN => 'Forbidden',
        self::HTTP_STATUS_NOT_FOUND => 'Not Found',
        // 500
        self::HTTP_STATUS_ERROR => 'Internal Server Error',
    );

    /**
     * The HTTP status code of the page using the `HTTP_STATUSES` constants
     *
     * @deprecated Since Symphony 2.3.2, this has been deprecated. It will be
     * removed in Symphony 3.0
     * @see $this->setHttpStatus and self::$HTTP_STATUSES
     *
     * @var integer
     */
    protected $_status = null;

    /**
     * This stores the headers that will be sent when this page is
     * generated as an associative array of header=>value.
     *
     * @var array
     */
    protected $_headers = array();

    /**
     * Initialises the Page object by setting the headers to empty
     */
    public function __construct()
    {
        $this->_headers = array();
    }

    /**
     *
     * This method returns the string HTTP Status value.
     * If `$status_code` is null, it returns all the values
     * currently registered.
     *
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     *
     * @since Symphony 2.3.2
     *
     * @param integer $status_code (optional)
     *  The HTTP Status code to get the value for.
     * @return array|string
     *  Returns string if the $status_code is not null. Array otherwise
     */
    final public static function getHttpStatusValue($status_code = null)
    {
        if (!$status_code) {
            return self::$HTTP_STATUSES;
        }

        return self::$HTTP_STATUSES[$status_code];
    }

    /**
     * This method format the provided `$status_code` to used
     * php's `header()` function.
     *
     * @since Symphony 2.3.2
     *
     * @param integer $status_code
     *  The HTTP Status code to get the value for
     * @return string
     *  The formatted HTTP Status string
     */
    final public static function getHeaderStatusString($status_code)
    {
        return sprintf("Status: %d %s", $status_code, self::getHttpStatusValue($status_code));
    }

    /**
     * Sets the `$sting_value` for the specified `$status_code`.
     * If `$sting_value` is null, the `$status_code` is removed from
     * the array.
     *
     * This allow developers to register customs HTTP_STATUS into the
     * static `Page::$HTTP_STATUSES` array and use `$page->setHttpStatus()`.
     *
     * @since Symphony 2.3.2
     *
     * @param integer $status_code
     *  The HTTP Status numeric code.
     * @param string $string_value
     *  The HTTP Status string value.
     */
    final public static function setHttpStatusValue($status_code, $string_value)
    {
        if (!$string_value) {
            unset(self::$HTTP_STATUSES[$status_code]);
        } elseif (is_int($status_code) && $status_code >= 100 && $status_code < 600) {
            self::$HTTP_STATUSES[$status_code] = $string_value;
        } else {
            // Throw error ?
        }
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
    public function addHeaderToPage($name, $value = null, $response_code = null)
    {
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
    public function removeHeaderFromPage($name)
    {
        unset($this->_headers[strtolower($name)]);
    }

    /**
     * Shorthand for `addHeaderToPage` in order to set the
     * HTTP Status header.
     *
     * @since Symphony 2.3.2
     *
     * @param integer $status_code
     *   The HTTP Status numeric value.
     */
    public function setHttpStatus($status_code)
    {
        $this->addHeaderToPage('Status', null, $status_code);
        // Assure we clear the legacy value
        $this->_status = null;
    }

    /**
     * Gets the current HTTP Status.
     * If none is set, it assumes HTTP_STATUS_OK
     *
     * @since Symphony 2.3.2
     *
     * @return integer
     */
    public function getHttpStatusCode()
    {
        // Legacy check
        if ($this->_status != null) {
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
    public function headers()
    {
        return $this->_headers;
    }

    /**
     * This function calls `__renderHeaders()`.
     *
     * @see __renderHeaders()
     */
    public function generate($page = null)
    {
        $this->__renderHeaders();
    }

    /**
     * This method calls php's `header()` function
     * in order to set the HTTP status code properly on all platforms.
     *
     * @see https://github.com/symphonycms/symphony-2/issues/1558#issuecomment-10663716
     *
     * @param integer $status_code
     */
    final public static function renderStatusCode($status_code)
    {
        header(self::getHeaderStatusString($status_code), true, $status_code);
    }

    /**
     * Iterates over the `$_headers` for this page
     * and outputs them using PHP's header() function.
     */
    protected function __renderHeaders()
    {
        if (!is_array($this->_headers) || empty($this->_headers)) {
            return;
        }

        // Legacy check
        if ($this->_status != null) {
            $this->setHttpStatus($this->_status);
        }

        foreach ($this->_headers as $key => $value) {
            // If this is the http status
            if ($key == 'status' && isset($value['response_code'])) {
                $res_code = intval($value['response_code']);
                self::renderStatusCode($res_code);
            } else {
                header($value['header']);
            }
        }
    }

    /**
     * This function will check to ensure that this post request is not larger than
     * what the server is set to handle. If it is, a notice is shown.
     *
     * @link https://github.com/symphonycms/symphony-2/issues/1187
     * @since Symphony 2.5.2
     */
    public function isRequestValid()
    {
        $max_size = ini_get('post_max_size');
        if (getenv('REQUEST_METHOD') === 'POST' && (int)getenv('CONTENT_LENGTH') > General::convertHumanFileSizeToBytes($max_size)) {
            return false;
        }

        return true;
    }
}
