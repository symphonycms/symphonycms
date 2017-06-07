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
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog('MD5::hash()', 'PBKDF2::hash()', array(
                'message-format' => __('The use of `%s` is strongly discouraged due to severe security flaws.'),
            ));
        }
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
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog('MD5::file()', 'PBKDF2::hash()', array(
                'message-format' => __('The use of `%s` is strongly discouraged due to severe security flaws.'),
            ));
        }
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
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog('MD5::compare()', 'PBKDF2::compare()', array(
                'message-format' => __('The use of `%s` is strongly discouraged due to severe security flaws.'),
            ));
        }
        return ($hash == self::hash($input));
    }
}
