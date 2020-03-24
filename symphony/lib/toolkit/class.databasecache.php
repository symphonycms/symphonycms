<?php

/**
 * @package toolkit
 */

/**
 * The DatabaseCache class acts offers a simple API for dealing with a key to array map.
 *
 * @since Symphony 3.0.0
 */
class DatabaseCache
{
    /**
     * The internal storage for the map
     * @var array
     */
    private $storage = [];

    /**
     * Adds a new value into the array for the specified $key.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function append($key, $value)
    {
        General::ensureType([
            'key' => ['var' => $key, 'type' => 'string'],
        ]);
        $this->storage[$key][] = $value;
    }

    /**
     * Adds all values into the array for the specified $key.
     *
     * @param string $key
     * @param array $values
     * @return void
     */
    public function appendAll($key, array $values)
    {
        General::ensureType([
            'key' => ['var' => $key, 'type' => 'string'],
        ]);
        $this->storage[$key] = array_merge($this->storage[$key], $values);
    }

    /**
     * Gets the array for the specified key. If the key does not exists, it
     * returns null.
     *
     * @param string $key
     * @return array|null
     */
    public function get($key)
    {
        General::ensureType([
            'key' => ['var' => $key, 'type' => 'string'],
        ]);
        if (!$this->has($key)) {
            return null;
        }
        return $this->storage[$key];
    }

    /**
     * Checks if the specified key has an entry in the map
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        General::ensureType([
            'key' => ['var' => $key, 'type' => 'string'],
        ]);
        return isset($this->storage[$key]);
    }
}
