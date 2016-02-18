<?php
/**
 * @package core
 */

/**
 * Cookies is a collection of currently set cookies, and user set cookies for
 * this request.
 *
 * @since Symphony 3.0.0
 */
class Cookies extends Container
{
    /**
     * @var string
     */
    const COOKIE_PIECE_REGEX = '@\s*[;]\s*@';

    /**
     * @var array
     */
    public static $reserved = array(
        'domain', 'path', 'secure', 'expires', 'max-age', 'httponly'
    );

    /**
     * Default cookie settings
     * @var array
     */
    protected $defaults = [
        'value' => '',
        'domain' => null,
        'path' => null,
        'expires' => null,
        'max-age' => null,
        'secure' => false,
        'httponly' => false
    ];

    /**
     * Any previously set cookies are stored in this array
     * @var array
     */
    protected $existing = array();

    /**
     * Constructor, allows overriding of default values
     * @param array $settings
     */
    public function __construct(array $settings = array())
    {
        $this->defaults = array_merge($this->defaults, $settings);
    }

    /**
     * Fetch the currently set cookies for reference, and capture any previously
     * set cookies from this request. This will unset those previously set cookies
     * and take over handling completely. Any cookies set from instantiation onwards
     * using `setcookie` will not be handled by this class.
     * @return void
     */
    public function fetch()
    {
        // Copy all the current cookies into this container for reference
        if (empty($this->existing)) {
            $header = (isset($_SERVER['HTTP_COOKIE']) ? rtrim($_SERVER['HTTP_COOKIE'], "\r\n") : null);

            if (!is_null($header)) {
                $pieces = preg_split(self::COOKIE_PIECE_REGEX, $header);

                $parsed = $this->processPieces($pieces);

                foreach ($parsed as $key => $value) {
                    $this->existing[$key] = $value;
                    $this->keys[$key] = true;
                }
            }
        }

        // Catch all cookies that have already been set
        if (empty($this->store)) {
            $headers = headers_list();

            foreach ($headers as $header) {
                if (stripos($header, 'Set-Cookie:') !== false) {
                    $header = str_replace('Set-Cookie: ', '', $header);
                    $pieces = preg_split(self::COOKIE_PIECE_REGEX, $header);
                    $parsed = $this->processPieces($pieces, $this->store);

                    // Um, guessing here a little bit
                    $keys = array_keys($parsed);
                    $key = '';

                    foreach ($keys as $k) {
                        if (!in_array(strtolower($k), self::$reserved)) {
                            $key = $k;
                        }
                    }

                    $parsed['value'] = $parsed[$key];
                    unset($parsed[$key]);

                    $this->store[$key] = $parsed;
                    $this->keys[$key] = true;
                }
            }

            header_remove('Set-Cookie');
        }
    }

    /**
     * Set a cookie into this container, for saving before output
     * @param string $key
     *  The cookie name
     * @param mixed $value
     *  Array of cookie settings, or a value for a cookie
     */
    public function offsetSet($key, $value)
    {
        if (is_array($value)) {
            $settings = array_replace($this->defaults, $value);
        } else {
            $settings = array_replace($this->defaults, array('value' => $value));
        }

        parent::offsetSet($key, $settings);
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
        unset($this->keys[$key], $this->existing[$key]);
    }

    /**
     * Get a service or value from this container
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        $cookies = array_merge($this->existing, $this->store);

        return (isset($cookies[$key]) ? $cookies[$key] : null);
    }

    /**
     * Save any new cookies, or cookies to be removed, as HTTP headers
     * @return boolean
     */
    public function save()
    {
        foreach ($this->store as $key => $value) {
            $cookie = $this->stringify($key, $value);
            header('Set-Cookie: ' . $cookie, false);
        }
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

    /**
     * Process the pieces of a parsed cookie header
     * @param  array  $pieces
     *  Array of parsed pieces
     * @return void
     */
    protected function processPieces(array $pieces)
    {
        $parsed = array();

        foreach ($pieces as $piece) {
            $cookie = explode('=', $piece, 2);

            if (count($cookie) === 2) {
                $key = urldecode($cookie[0]);
                $value = urldecode($cookie[1]);
                $parsed[trim($key)] = trim($value);
            } elseif (count($cookie) === 1) {
                $key = urldecode($cookie[0]);
                $parsed[trim($key)] = true;
            }
        }

        return $parsed;
    }
}
