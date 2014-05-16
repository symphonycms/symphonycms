<?php
/**
 * @package cryptography
 */
/**
 * MD5 is a cryptography class for hashing and comparing messages
 * using the MD5-Algorithm
 *
 * @since Symphony 2.3.1
 * @see toolkit.Cryptography
 * @deprecated This code is regarded as insecure and exists only for backwards-compatibility-purposes.
 * It should not be used when writing new password-related features.
 */
class MD5 extends Cryptography
{
    /**
     * Uses `MD5` to create a hash based on some input
     *
     * @param string $input
     * the string to be hashed
     * @return string
     * the hashed string
     */
    public static function hash($input)
    {
        Symphony::Log()->pushToLog('The use of MD5::hash() is discouraged due to severe security flaws.', E_DEPRECATED, true);
        return md5($input);
    }

    /**
     * Uses `MD5` to create a hash from the contents of a file
     *
     * @param string $input
     * the file to be hashed
     * @return string
     * the hashed string
     */
    public static function file($input)
    {
        return md5_file($input);
    }

    /**
     * Compares a given hash with a cleantext password.
     *
     * @param string $input
     * the cleartext password
     * @param string $hash
     * the hash the password should be checked against
     * @param boolean $isHash
     * @return bool
     * the result of the comparison
     */
    public static function compare($input, $hash, $isHash = false)
    {
        return ($hash == self::hash($input));
    }
}
