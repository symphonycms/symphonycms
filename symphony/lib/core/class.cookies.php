<?php
/**
 * @package core
 */

require_once CORE . '/class.container.php';

/**
 * Cookies
 */
class Cookies extends Container
{
    /**
     * Default cookie settings
     * @var array
     */
    protected $defaults = [
        'value' => '',
        'domain' => null,
        'path' => null,
        'expires' => null,
        'secure' => false,
        'httponly' => false
    ];

    /**
     * Whether the cookies in this store can be saved over HTTP.
     * Prevents existing cookie's expiries from being re-set
     * @var boolean
     */
    protected $locked = array();

    /**
     * Constructor, allows overriding of default values
     * @param array $settings
     */
    public function __construct(array $settings = array())
    {
        $this->defaults = array_merge($this->defaults, $settings);
    }

    /**
     * Fetch the current cookies from the $_COOKIE global
     * Prevents this Cooikes instance from being saved over HTTP
     */
    public function fetch()
    {
        if (empty($this->locked)) {
            $header = (isset($_SERVER['HTTP_COOKIE']) ? rtrim($_SERVER['HTTP_COOKIE'], "\r\n") : '');
            $pieces = preg_split('@\s*[;,]\s*@', $header);

            foreach ($pieces as $cookie) {
                $cookie = explode('=', $cookie, 2);

                if (count($cookie) === 2) {
                    $key = urldecode($cookie[0]);
                    $value = urldecode($cookie[1]);

                    if (!isset($this->locked[$key])) {
                        $this->locked[$key] = $value;
                        $this->keys[$key] = true;
                    }
                }
            }
        }
    }

    /**
     * Set a cookie
     * @param string $key   The cookie name
     * @param mixed $value  Array of cookie settings, or a value for a cookie
     */
    public function offsetSet($key, $value)
    {
        if (is_array($value)) {
            $settings = array_replace($this->defaults, $value);
        } else {
            $settings = array_replace($this->defaults, array('value' => $value));
        }

        $this->store[$key] = $settings;
        $this->keys[$key] = true;
    }

    /**
     * Remove a cookie. Requires that a cookie is set to expire in the past
     * @param  string $key      The cookie name
     * @param  array $settings  Array of cookie settings
     */
    public function offsetUnset($key, array $settings = array())
    {
        $settings = array_merge($this->defaults, $settings, array(
            'value' => '',
            'expires' => (time() - 86400)
        ));

        $this->offsetSet($key, $settings);
        unset($this->keys[$key], $this->locked[$key]);
    }

    /**
     * Get a service or value from this container
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        $cookies = array_merge($this->locked, $this->store);

        return (isset($cookies[$key]) ? $cookies[$key] : null);
    }

    /**
     * Check a key exists in this container
     * @param  string $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->keys);
    }

    /**
     * Save any new cookies, or cookies to be removed, as HTTP headers
     * @return boolean
     */
    public function save()
    {
        $cookies = array();

        foreach ($this->store as $key => $value) {
            $cookies[] = $this->stringify($key, $value);
        }

        header('Set-Cookie: ' . implode("\n", $cookies));
    }

    /**
     * Compose a Set-Cookie header string
     * @param  string $name
     * @param  array  $value
     * @return string
     */
    protected function stringify($name, $value)
    {
        $values = array();

        if (is_array($value)) {
            if (isset($value['domain']) && $value['domain']) {
                $values[] = '; domain=' . $value['domain'];
            }

            if (isset($value['path']) && $value['path']) {
                $values[] = '; path=' . $value['path'];
            }

            if (isset($value['expires'])) {
                if (is_string($value['expires'])) {
                    $timestamp = strtotime($value['expires']);
                } else {
                    $timestamp = (int) $value['expires'];
                }

                if ($timestamp !== 0) {
                    $values[] = '; expires=' . gmdate('D, d-M-Y H:i:s e', $timestamp);
                }
            }

            if (isset($value['secure']) && $value['secure']) {
                $values[] = '; secure';
            }

            if (isset($value['httponly']) && $value['httponly']) {
                $values[] = '; HttpOnly';
            }

            $value = (string)$value['value'];
        }

        $cookie = sprintf(
            '%s=%s',
            urlencode($name),
            urlencode((string) $value) . implode('', $values)
        );

        return $cookie;
    }
}
