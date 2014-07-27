<?php
/**
 * @package core
 */

require_once CORE . '/class.container.php';

/**
 * Session
 */
class Session extends Container
{
    /**
     * Session handler
     * @var DatabaseSessionHandler
     */
    protected $handler;

    /**
     * Array of settings
     * @var array
     */
    protected $settings;

    /**
     * Session key
     * @var string
     */
    protected $key;

    /**
     * Constructor
     * @param DatabaseSessionHandler $handler
     * @param array $settings
     * @param string $key
     */
    public function __construct($handler = null, array $settings = array(), $key = 'symphony')
    {
        $this->handler = $handler;

        $this->settings = array_merge([
            'session_name' => $key,
            'session_gc_probability' => '1',
            'session_gc_divisor' => '10',
            'session_gc_maxlifetime' => '1440',
            'session_cookie_lifetime' => '1440',
            'session_cookie_path' => '/',
            'session_cookie_domain' => '',
            'session_cookie_secure' => false,
            'session_cookie_httponly' => false
        ], $settings);

        if (empty($this->settings['session_cookie_domain'])) {
            $this->settings['session_cookie_domain'] = $this->getDomain();
        }

        $this->key = $this->settings['session_name'];
    }

    /**
     * Start the session
     */
    public function start()
    {
        if (!$this->isStarted()) {
            // Disable PHP cache headers
            session_cache_limiter('');

            if (session_id() == '') {
                ini_set('session.gc_probability', $this->settings['session_gc_probability']);
                ini_set('session.gc_maxlifetime', $this->settings['session_gc_maxlifetime']);
                ini_set('session.gc_divisor', $this->settings['session_gc_divisor']);
                ini_set('session.use_cookies', '1');
                ini_set('session.hash_bits_per_character', 5);
            }

            if (!is_null($this->handler)) {
                // In PHP 5.4 we can move this to
                // session_set_save_handler($handler, true);
                session_set_save_handler(
                    array($this->handler, 'open'),
                    array($this->handler, 'close'),
                    array($this->handler, 'read'),
                    array($this->handler, 'write'),
                    array($this->handler, 'destroy'),
                    array($this->handler, 'gc')
                );
            }

            session_name($this->settings['session_name']);

            session_set_cookie_params(
                $this->settings['session_cookie_lifetime'],
                $this->settings['session_cookie_path'],
                $this->settings['session_cookie_domain'],
                $this->settings['session_cookie_secure'],
                $this->settings['session_cookie_httponly']
            );

            if (session_id() == '') {
                if (headers_sent()) {
                    throw new Exception('Headers already sent. Cannot start session.');
                }

                register_shutdown_function('session_write_close');
                session_start();
            }
        }

        $this->store =& $_SESSION;

        return session_id();
    }

    public function getDomain() {
        if (isset($_SERVER['HTTP_HOST'])) {
            if (preg_match('/(localhost|127\.0\.0\.1)/', $_SERVER['HTTP_HOST']) || $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
                return null; // prevent problems on local setups
            }

            return preg_replace('/(^www\.|:\d+$)/i', null, $_SERVER['HTTP_HOST']);

        }

        return null;
    }

    /**
     * Get the current session save key
     * @return string
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Is the session started
     * @return boolean
     */
    protected function isStarted()
    {
       if (php_sapi_name() !== 'cli') {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                return session_status() === PHP_SESSION_ACTIVE ? true : false;
            } else {
                return session_id() === '' ? false : true;
            }
        }
        return false;
    }

    /**
     * Expires the current session by unsetting the Symphony
     * namespace (`$this->key`). If `$this->store`
     * is empty, the function will destroy the entire `$this->store`
     *
     * @link http://au2.php.net/manual/en/function.session-destroy.php
     */
    public function expire()
    {
        if (isset($this->store[$this->key]) && !empty($this->store[$this->key])) {
            unset($this->store[$this->key]);
        }

        // Calling session_destroy triggers the Session::destroy function which removes the entire session
        // from the database. To prevent logout issues between functionality that relies on $this->store, such
        // as Symphony authentication or the Members extension, only delete the $this->store if it empty!
        if (empty($this->store)) {
            session_destroy();
        }
    }

    /**
     * Set a service or value in this container
     * @param  string $key
     * @param  mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->store[$this->key][$key] = $value;
        $this->keys[$key] = true;
    }

    /**
     * Get a service or value from this container
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->store[$this->key][$key];
    }

    /**
     * Unset a value from the container
     * @param  string $key
     */
    public function offsetUnset($key)
    {
        unset($this->store[$this->key][$key], $this->keys[$key]);
    }
}
