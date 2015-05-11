<?php
/**
 * @package core
 */

/**
 * Session Flash
 */
class SessionFlash
{
    /**
     * The global flash storage key
     * @var string
     */
    protected $key;

    /**
     * The Session object
     * @var Session
     */
    protected $session;

    /**
     * Stack of Flash data
     * @var array
     */
    protected $flash;

    /**
     * Constructor
     * @param Session $session
     * @param string  $key
     */
    public function __construct(Session $session, $key = 'symphony_flash')
    {
        $this->session = $session;
        $this->key = $key;
        $this->flash = [
            'prev' => $session[$key],
            'next' => array(),
            'now' => array()
        ];
    }

    /**
     * Set flash for the next request
     * @param  string   $key
     * @param  mixed   $value
     */
    public function next($key, $value)
    {
        $this->flash['next'][(string)$key] = $value;
    }

    /**
     * Set flash for the current request
     * @param  string $key
     * @param  mixed $value
     */
    public function now($key, $value)
    {
        $this->flash['now'][(string)$key] = $value;
    }

    /**
     * Persist flash from the current request to the next
     */
    public function keep()
    {
        foreach ($this->messages['prev'] as $key => $val) {
            $this->next($key, $val);
        }
    }

    /**
     * Save flash to the session
     */
    public function save()
    {
        $this->session[$this->key] = $this->flash['next'];
    }

    /**
     * Get flash for the current request
     * @return array
     */
    public function current()
    {
        return array_merge($this->flash['prev'], $this->flash['now']);
    }

    /**
     * Offset exists
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        $flash = $this->current();

        return isset($flash[$offset]);
    }

    /**
     * Offset get
     * @param  mixed $offset
     * @return mixed The value at specified offset, or null
     */
    public function offsetGet($offset)
    {
        $flash = $this->current();

        return isset($flash[$offset]) ? $flash[$offset] : null;
    }

    /**
     * Offset set
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->now($offset, $value);
    }

    /**
     * Offset unset
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->flash['prev'][$offset], $this->flash['now'][$offset]);
    }

    /**
     * Get iterator
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->current());
    }

    /**
     * Count all current flash
     * @return int
     */
    public function count()
    {
        return count($this->current());
    }
}
