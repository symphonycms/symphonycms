<?php
/**
 * @package core
 */

/**
 * Container
 */
class Container implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * Storage of key => value pairs
     * @var array
     */
    protected $store = array();

    /**
     * Index of stored keys
     * @var array
     */
    protected $keys = array();

    /**
     * Constructor, add a set of key => value pairs on instantiation
     * @param array $contents
     */
    public function __construct(array $contents = array())
    {
        foreach ($contents as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Call a container value as a method
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->offsetGet($name);
    }

    /**
     * Set a service or value in this container
     * @param  string $key
     * @param  mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->store[$key] = $value;
        $this->keys[$key] = true;
    }

    /**
     * Set a service or value in this container
     * @param  string $key
     * @param  mixed $value
     */
    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Set a service or value in this container
     * @param  string $key
     * @param  mixed $value
     */
    public function set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Get a service or value from this container
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        if (!isset($this->keys[$key])) {
            return null;
        }

        if (!is_object($this->store[$key]) || !method_exists($this->store[$key], '__invoke')) {
            return $this->store[$key];
        }

        return $this->store[$key] = $this->store[$key]($this);
    }

    /**
     * Get a service or value from this container
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * Get a service or value from this container
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->offsetGet($key);
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
     * Check a key exists in this container
     * @param  string $key
     * @return boolean
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Check a key exists in this container
     * @param  string $key
     * @return boolean
     */
    public function exists($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset a value from the container
     * @param  string $key
     */
    public function offsetUnset($key)
    {
        unset($this->store[$key], $this->keys[$key]);
    }

    /**
     * Unset a value from the container
     * @param  string $key
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Unset a value from the container
     * @param  string $key
     */
    public function remove($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Return an instance of self with a subset of this container
     * @param  array  $keys
     * @return SymphonyCMS\Container
     */
    public function subSet(array $keys)
    {
        $extraction = array();

        foreach ($keys as $key) {
            if (isset($this->keys[$key])) {
                $extraction[$key] = $this->store[$key];
            }
        }

        return new static($extraction);
    }

    /**
     * Get all the registered keys
     * @return array
     */
    public function keys()
    {
        return array_keys($this->keys);
    }

    /**
     * Get all from the store
     * @return array
     */
    public function all()
    {
        return $this->store;
    }

    /**
     * Get an ArrayIterator for this container
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->store);
    }

    /**
     * Return a count of the container items using Countable
     *
     * @return integer
     */
    public function count()
    {
        return count($this->store);
    }
}
