<?php

/**
 * @package core
 */
if (!defined('__IN_SYMPHONY__')) {
    die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
}

/**
 * The Cookie class is a wrapper to save Symphony cookies. Typically this
 * is used to maintain if an Author is logged into Symphony, or by extensions
 * to determine similar things. The Cookie class is tightly integrated with
 * PHP's `$_SESSION` global and it's related functions.
 */

class Cookie
{
    /**
     * Used to prevent Symphony cookies from completely polluting the
     * `$_SESSION` array. This will act as a key and all
     * cookies will live under that key. By default, the index is read from
     * the Symphony configuration, and unless changed, is `sym-`
     *
     * @var string
     */
    private $_index;

    /**
     * This variable determines if the Cookie was set by the Symphony Session
     * class, or if it was set directly. By default, this is false as the Symphony cookie
     * created directly in the Symphony constructor, otherwise it will be an instance
     * of the Session class
     *
     * @see core.Symphony#__construct()
     * @var Session|boolean
     */
    private $_session = false;

    /**
     * How long this cookie is valid for. By default, this is 0 if used by an extension,
     * but it is usually set for 2 weeks in the Symphony context.
     *
     * @var integer
     */
    private $_timeout = 0;

    /**
     * The path that this cookie is valid for, by default Symphony makes this the whole
     * domain using /
     *
     * @var string
     */
    private $_path;

    /**
     * The domain that this cookie is valid for. This is null by default which implies
     * the entire domain and all subdomains created will have access to this cookie.
     *
     * @var string
     */
    private $_domain;

    /**
     * Determines whether this cookie can be read by Javascript or not, by default
     * this is set to true, meaning cookies written by Symphony cannot be read by Javascript
     *
     * @var boolean
     */
    private $_httpOnly = true;

    /**
     * Determines whether this cookie will be sent over a secure connection or not. If
     * true, this cookie will only be sent on a secure connection. Defaults to false
     * but will automatically be set if `__SECURE__` is true
     *
     * @since Symphony 2.3.3
     * @see boot
     * @var boolean
     */
    private $_secure = false;

    /**
     * Constructor for the Cookie class intialises all class variables with the
     * given parameters. Most of the parameters map to PHP's setcookie
     * function. It creates a new Session object via the `$this->__init()`
     *
     * @see __init()
     * @link http://php.net/manual/en/function.setcookie.php
     * @param string $index
     *  The prefix to used to namespace all Symphony cookies
     * @param integer $timeout
     *  The Time to Live for a cookie, by default this is zero, meaning the
     *  cookie never expires
     * @param string $path
     *  The path the cookie is valid for on the domain
     * @param string $domain
     *  The domain this cookie is valid for
     * @param boolean $httpOnly
     *  Whether this cookie can be read by Javascript. By default the cookie
     *  cannot be read by Javascript
     * @throws Exception
     */
    public function __construct($index, $timeout = 0, $path = '/', $domain = null, $httpOnly = true)
    {
        $this->_index = $index;
        $this->_timeout = $timeout;
        $this->_path = $path;
        $this->_domain = $domain;
        $this->_httpOnly = $httpOnly;

        if (defined(__SECURE__)) {
            $this->_secure = __SECURE__;
        }

        $this->_session = $this->__init();
    }

    /**
     * Initialises a new Session instance using this cookie's params
     *
     * @throws Exception
     * @return Session
     */
    private function __init()
    {
        $this->_session = Session::start($this->_timeout, $this->_path, $this->_domain, $this->_httpOnly, $this->_secure);

        if (!$this->_session) {
            return false;
        }

        if (!isset($_SESSION[$this->_index])) {
            $_SESSION[$this->_index] = array();
        }

        // Class FrontendPage uses $_COOKIE directly (inside it's __buildPage() function), so try to emulate it.
        $_COOKIE[$this->_index] = &$_SESSION[$this->_index];

        return $this->_session;
    }

    /**
     * A basic setter, which will set a value to a given property in the
     * `$_SESSION` array, stored in the key of `$this->_index`
     *
     * @param string $name
     *  The name of the property
     * @param string $value
     *  The value of the property
     */
    public function set($name, $value)
    {
        $_SESSION[$this->_index][$name] = $value;
    }

    /**
     * Accessor function for properties in the `$_SESSION` array
     *
     * @param string $name
     *  The name of the property to retrieve (optional)
     * @return string|null
     *  The value of the property, or null if it does not exist. If
     *  no `$name` is provided, return the entire Cookie.
     */
    public function get($name = null)
    {
        if (is_null($name) && isset($_SESSION[$this->_index])) {
            return $_SESSION[$this->_index];
        }

        if (isset($_SESSION[$this->_index]) && is_array($_SESSION[$this->_index]) && array_key_exists($name, $_SESSION[$this->_index])) {
            return $_SESSION[$this->_index][$name];
        }

        return null;
    }

    /**
     * Expires the current `$_SESSION` by unsetting the Symphony
     * namespace (`$this->_index`). If the `$_SESSION`
     * is empty, the function will destroy the entire `$_SESSION`
     *
     * @link http://au2.php.net/manual/en/function.session-destroy.php
     */
    public function expire()
    {
        if (!isset($_SESSION[$this->_index]) || !is_array($_SESSION[$this->_index]) || empty($_SESSION[$this->_index])) {
            return;
        }

        unset($_SESSION[$this->_index]);

        // Calling session_destroy triggers the Session::destroy function which removes the entire session
        // from the database. To prevent logout issues between functionality that relies on $_SESSION, such
        // as Symphony authentication or the Members extension, only delete the $_SESSION if it empty!
        if (empty($_SESSION)) {
            session_destroy();
        }
    }
}
