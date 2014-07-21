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
     * Session array
     * @var array
     */
    protected $session;

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
    public function __construct($handler = null, array $settings = array(), $key = 'symphony_session')
    {
        if (!is_null($handler)) {
            session_set_save_handler($handler, true);
            $this->handler = $handler;
        }

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

        $this->key = $key;
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

            session_set_cookie_params(
                $this->settings['session_cookie_lifetime'],
                $this->settings['session_cookie_path'],
                $this->settings['session_cookie_domain'],
                $this->settings['session_cookie_secure'],
                $this->settings['session_cookie_httponly']
            );

            session_name($this->settings['session_name']);

            register_shutdown_function('session_write_close');

            // Start session
            if (session_start() === false) {
                throw new \RuntimeException('Cannot start session. Unknown error while invoking `session_start()`.');
            }
        }

        if (!isset($this->session)) {
            $this->session =& $_SESSION;
        }

        // Pull the session into a localised container
        if (isset($this->session[$this->key])) {
            foreach ($this->session[$this->key] as $key => $value) {
                $this->offsetSet($key, $value);
            }
        }
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
     * Save this localised data back to the session
     */
    public function save()
    {
        $this->session[$this->key] = $this->all();
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
        return (session_status() === PHP_SESSION_ACTIVE ? true : false);
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
        if (!isset($_SESSION[$this->key]) || !is_array($_SESSION[$this->key]) || empty($_SESSION[$this->key])) {
            return;
        }

        unset($_SESSION[$this->key]);

        // Calling session_destroy triggers the Session::destroy function which removes the entire session
        // from the database. To prevent logout issues between functionality that relies on $_SESSION, such
        // as Symphony authentication or the Members extension, only delete the $_SESSION if it empty!
        if (empty($_SESSION)) {
            session_destroy();
        }
    }
}
