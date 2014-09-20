<?php

/**
 * @package core
 */

 /**
  * The CacheDatabase interface allows extensions to store data in Symphony's
  * database table, `tbl_cache`. At the moment, it is mostly unused by the core,
  * with the exception of the deprecated Dynamic XML datasource.
  *
  * This cache will be initialised by default if no other caches are specified
  * in the install.
  * 
  * @see ExtensionManager#getCacheProvider()
  */
require_once FACE . '/interface.cache.php';
require_once TOOLKIT . '/class.mutex.php';

class CacheDatabase implements iCache
{
    /**
     * An instance of the MySQL class to communicate with `tbl_cache`
     * which is where the cached data is stored.
     *
     * @var MySQL
     */
    private $Database;

    /**
     * The constructor for the Cacheable takes an instance of the
     * MySQL class and assigns it to `$this->Database`
     *
     * @param MySQL $Database
     *  An instance of the MySQL class to store the cached
     *  data in.
     */
    public function __construct(MySQL $Database)
    {
        $this->Database = $Database;
    }

    /**
     * Returns the human readable name of this cache type. This is
     * displayed in the system preferences cache options.
     *
     * @return string
     */
    public static function getName()
    {
        return 'Database';
    }

    /**
     * This function returns all the settings of the current Cache
     * instance.
     *
     * @return array
     *  An associative array of settings for this cache where the
     *  key is `getClass` and the value is an associative array of settings,
     *  key being the setting name, value being, the value
     */
    public function settings()
    {

    }

    /**
     * Given the hash of a some data, check to see whether it exists in
     * `tbl_cache`. If no cached object is found, this function will return
     * false, otherwise the cached object will be returned as an array.
     *
     * @param string $hash
     *  The hash of the Cached object, as defined by the user
     * @return array|boolean
     *  An associative array of the cached object including the creation time,
     *  expiry time, the hash and the data. If the object is not found, false will
     *  be returned.
     */
    public function read($hash)
    {
        if ($c = $this->Database->fetchRow(0, "SELECT SQL_NO_CACHE * FROM `tbl_cache` WHERE `hash` = '$hash' AND (`expiry` IS NULL OR UNIX_TIMESTAMP() <= `expiry`) LIMIT 1")) {
            if (!$c['data'] = Cacheable::decompressData($c['data'])) {
                $this->delete($hash);

                return false;
            }

            return $c;
        }

        $this->delete();

        return false;
    }

    /**
     * This function will compress data for storage in `tbl_cache`.
     * It is left to the user to define a unique hash for this data so that it can be
     * retrieved in the future. Optionally, a `$ttl` parameter can
     * be passed for this data. If this is omitted, it data is considered to be valid
     * forever. This function utilizes the Mutex class to act as a crude locking
     * mechanism.
     *
     * @see toolkit.Mutex
     * @param string $hash
     *  The hash of the Cached object, as defined by the user
     * @param string $data
     *  The data to be cached, this will be compressed prior to saving.
     * @param integer $ttl
     *  A integer representing how long the data should be valid for in minutes.
     *  By default this is null, meaning the data is valid forever
     * @throws DatabaseException
     * @return boolean
     *  If an error occurs, this function will return false otherwise true
     */
    public function write($hash, $data, $ttl = null)
    {
        if (!Mutex::acquire($hash, 2, TMP)) {
            return false;
        }

        $creation = time();
        $expiry = null;

        $ttl = intval($ttl);

        if ($ttl > 0) {
            $expiry = $creation + ($ttl * 60);
        }

        if (!$data = Cacheable::compressData($data)) {
            return false;
        }

        $this->delete($hash);
        $this->Database->insert(array('hash' => $hash, 'creation' => $creation, 'expiry' => $expiry, 'data' => $data), 'tbl_cache');

        Mutex::release($hash, TMP);

        return true;
    }

    /**
     * Given the hash of a cacheable object, remove it from `tbl_cache`
     * regardless of if it has expired or not. If no $hash is given,
     * this removes all cache objects from `tbl_cache` that have expired.
     * After removing, the function uses the `__optimise` function
     *
     * @see core.Cacheable#optimise()
     * @param string $hash
     *  The hash of the Cached object, as defined by the user
     * @throws DatabaseException
     */
    public function delete($hash = null)
    {
        if (!is_null($hash)) {
            $this->Database->delete("`tbl_cache`", sprintf("`hash` = '%s'", $hash));
        } else {
            $this->Database->delete("`tbl_cache`", "UNIX_TIMESTAMP() > `expiry`");
        }

        $this->__optimise();
    }

    /**
     * Runs a MySQL OPTIMIZE query on `tbl_cache`
     */
    private function __optimise()
    {
        $this->Database->query('OPTIMIZE TABLE `tbl_cache`');
    }
}
