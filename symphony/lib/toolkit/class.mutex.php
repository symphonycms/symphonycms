<?php
/**
 * @package toolkit
 */
/**
 * The Mutex class is a crude locking class that generates files
 * with a specific time to live. It has basic functions to create a
 * lock, release a lock or refresh a lock.
 */
class Mutex
{
    /**
     * An associative array of files that have been locked by the Mutex
     * class, with the key the filename, and the values an associative array
     * with `time` and `ttl` values.
     * @var array
     */
    private static $lockFiles;

    /**
     * Creates a lock file if one does not already exist with a certain
     * time to live (TTL) at a specific path. If a lock already exists,
     * false will be returned otherwise boolean depending if a lock
     * file was created successfully or not.
     *
     * @param string $id
     *  The name of the lock file, which gets obfuscated using
     *  generateLockFileName.
     * @param integer $ttl
     *  The length, in seconds, that the lock should exist for. Defaults
     *  to 5.
     * @param string $path
     *  The path the lock should be written, defaults to the current
     *  working directory
     * @return boolean
     */
    public static function acquire($id, $ttl = 5, $path = '.')
    {
        $lockFile = self::__generateLockFileName($id, $path);

        // If this thread already has acquired the lock, return true.
        if (isset(self::$lockFiles[$lockFile])) {
            $age = time() - self::$lockFiles[$lockFile]['time'];
            return ($age < $ttl ? false : true);
        }

        // Disable log temporarily because we actually depend on fopen()
        // failing with E_WARNING here and we do not want Symphony to throw
        // errors or spam logfiles.
        try {
            GenericErrorHandler::$logDisabled = true;
            $lock = fopen($lockFile, 'xb');
            GenericErrorHandler::$logDisabled = false;

            self::$lockFiles[$lockFile] = array('time' => time(), 'ttl' => $ttl);
            fclose($lock);

            return true;
        } catch (Exception $ex) {
            // If, for some reason, lock file was not unlinked before,
            // remove it if it is old enough.
            if (file_exists($lockFile)) {
                $age = time() - filemtime($lockFile);

                if ($age > $ttl) {
                    unlink($lockFile);
                }
            }

            // Return false anyway - just in case two or more threads
            // do the same check and unlink at the same time.
            return false;
        }
    }

    /**
     * Removes a lock file. This is the only way a lock file can be removed
     *
     * @param string $id
     *  The original name of the lock file (note that this will be different from
     *  the name of the file saved on the file system)
     * @param string $path
     *  The path to the lock, defaults to the current working directory
     * @return boolean
     */
    public static function release($id, $path = '.')
    {
        $lockFile = self::__generateLockFileName($id, $path);

        if (!empty(self::$lockFiles[$lockFile])) {
            unset(self::$lockFiles[$lockFile]);
            if (file_exists($lockFile)) {
                return unlink($lockFile);
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Updates a lock file to 'keep alive' for another 'x' seconds.
     *
     * @param string $id
     *  The name of the lock file, which gets obfuscated using
     *  `__generateLockFileName()`.
     * @param integer $ttl
     *  The length, in seconds, that the lock should be extended by.
     *  Defaults to 5.
     * @param string $path
     *  The path to the lock, defaults to the current working directory
     * @return boolean
     */
    public static function refresh($id, $ttl = 5, $path = '.')
    {
        return touch(self::__generateLockFileName($id, $path), time() + $ttl, time());
    }

    /**
     * Checks if a lock exists, purely on the presence on the lock file.
     * This function takes the unobfuscated lock name
     * Others should not depend on value returned by this function,
     * because by the time it returns, the lock file can be created or deleted
     * by another thread.
     *
     * @since Symphony 2.2
     * @param string $id
     *  The name of the lock file, which gets obfuscated using
     *  generateLockFileName.
     * @param string $path
     *  The path the lock should be written, defaults to the current
     *  working directory
     * @return boolean
     */
    public static function lockExists($id, $path)
    {
        $lockFile = self::__generateLockFileName($id, $path);

        return file_exists($lockFile);
    }

    /**
     * Generates a lock filename using an MD5 hash of the `$id` and
     * `$path`. Lock files are given a .lock extension
     *
     * @param string $id
     *  The name of the lock file to be obfuscated
     * @param string $path
     *  The path the lock should be written
     * @return string
     */
    private static function __generateLockFileName($id, $path = null)
    {
        // This function is called from all others, so it is a good point to initialize Mutex handling.
        if (!is_array(self::$lockFiles)) {
            self::$lockFiles = array();
            register_shutdown_function(array(__CLASS__, '__shutdownCleanup'));
        }

        if (is_null($path)) {
            $path = sys_get_temp_dir();
        }

        // Use realpath, because shutdown function may operate in different working directory.
        // So we need to be sure that path is absolute.
        return rtrim(realpath($path), '/') . '/' . md5($id) . '.lock';
    }

    /**
     * Releases all locks on expired files.
     *
     * @since Symphony 2.2.2
     */
    public static function __shutdownCleanup()
    {
        $now = time();

        if (is_array(self::$lockFiles)) {
            foreach (self::$lockFiles as $lockFile => $meta) {
                if (($now - $meta['time'] > $meta['ttl']) && file_exists($lockFile)) {
                    unlink($lockFile);
                }
            }
        }
    }
}
