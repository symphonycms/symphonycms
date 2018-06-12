<?php

/**
 * @package core
 */
/**
 * The Cookie class is a wrapper to save Symphony "cookies". Typically this
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
     * Constructor for the Cookie class. Creates an empty slot in $_SESSION if required.
     *
     * @param string $index
     *  The prefix to used to namespace all Symphony cookies in $_SESSION
     * @throws Exception
     */
    public function __construct($index)
    {
        if (!$index) {
            throw new Exception('Cookie index cannot be null');
        }
        $this->_index = $index;
        if (!isset($_SESSION[$this->_index])) {
            $_SESSION[$this->_index] = [];
        }
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
        } else {
            // Since session is not empty, we still need to rotate the session id.
            $this->regenerate();
        }
    }

    /**
     * Regenerates the session id.
     * This is used to prevent session fixation.
     *
     * @return void
     */
    public function regenerate()
    {
        session_regenerate_id(false);
    }
}
