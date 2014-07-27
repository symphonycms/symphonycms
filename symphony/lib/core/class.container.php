<?php
/**
 * @package core
 */

/**
 * The Symphony Container allows for simple dependency injection. It uses many
 * access methods to allow a developer freedom to choose.
 * @since  2.5.0
 */
class Container implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * Storage of key => value pairs
     * @var array
     */
    protected $store = array();

    /**
     * Index of stored keys that allows a performant lookup for the container.
     * @var array
     */
    protected $keys = array();

    /**
     * Constructor, add an array of key => value pairs on instantiation
     * @param array $contents
     */
    public function __construct(array $contents = array())
    {
        foreach ($contents as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Calling a container value as a method has two possible results; Like 'get'
     * if the key points to a callable object, it will return the result of that
     * callable, otherwise it will return the value of the key.
     * @param  string $name
     *  The key within the container
     * @param  array  $arguments
     *  Any arguments passed will be provided to a callable, after `$this`
     * @return mixed
     *  Whatever the value is in the container
     */
    public function __call($name, $arguments)
    {
        if (!isset($this->keys[$key])) {
            return null;
        }

        if (!is_object($this->store[$key]) || !method_exists($this->store[$key], '__invoke')) {
            return $this->store[$key];
        }

        return $this->store[$key] = $this->store[$key]($this, $arguments);
    }

    /**
     * Set a service or value in this container based on a provided key
     * @param  string $key
     *  String key to reference the value in the container
     * @param  mixed $value
     *  Mixed value to store in the container
     */
    public function offsetSet($key, $value)
    {
        $this->store[$key] = $value;
        $this->keys[$key] = true;
    }

    /**
     * Set a service or value in this container based on a provided key
     * @param  string $key
     *  String key to reference the value in the container
     * @param  mixed $value
     *  Mixed value to store in the container
     */
    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Set a service or value in this container based on a provided key
     * @param  string $key
     *  String key to reference the value in the container
     * @param  mixed $value
     *  Mixed value to store in the container
     */
    public function set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Get a service or value from this container. If the key points to a callable
     * object, it will return the result of that callable.
     * @param  string $key
     *  String reference to get from the container
     * @return mixed
     *  Mixed value from the container
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
     * Get a service or value from this container. If the key points to a callable
     * object, it will return the result of that callable.
     * @param  string $key
     *  String reference to get from the container
     * @return mixed
     *  Mixed value from the container
     */
    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * Get a service or value from this container. If the key points to a callable
     * object, it will return the result of that callable.
     * @param  string $key
     *  String reference to get from the container
     * @return mixed
     *  Mixed value from the container
     */
    public function get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * Check a key exists in this container
     * @param  string $key
     *  String key to check
     * @return boolean
     *  Whether the key is in the container
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->keys);
    }

    /**
     * Check a key exists in this container
     * @param  string $key
     *  String key to check
     * @return boolean
     *  Whether the key is in the container
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Check a key exists in this container
     * @param  string $key
     *  String key to check
     * @return boolean
     *  Whether the key is in the container
     */
    public function exists($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset a value from the container using a provided key
     * @param  string $key
     *  The key to identify the item to unset
     */
    public function offsetUnset($key)
    {
        unset($this->store[$key], $this->keys[$key]);
    }

    /**
     * Unset a value from the container using a provided key
     * @param  string $key
     *  The key to identify the item to unset
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Unset a value from the container using a provided key
     * @param  string $key
     *  The key to identify the item to unset
     */
    public function remove($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Return an instance of the container class with a subset of this container
     * @param  array  $keys
     *  Keys to create a new container with
     * @return Container
     *  A new instance of the container with only the requested keys and values
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
     * Get all the registered keys for contents of this container
     * @return array
     *  Array of string keys
     */
    public function keys()
    {
        return array_keys($this->keys);
    }

    /**
     * Get all of the contents of this container in an array
     * @return array
     *  The container as an array
     */
    public function all()
    {
        return $this->store;
    }

    /**
     * Get an ArrayIterator for this container
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->store);
    }

    /**
     * Return a count of the container items using Countable
     * @return integer
     */
    public function count()
    {
        return count($this->store);
    }
}
