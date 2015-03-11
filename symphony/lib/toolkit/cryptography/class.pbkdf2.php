<?php
/**
 * @package cryptography
 */
/**
 * PBKDF2 is a cryptography class for hashing and comparing messages
 * using the PBKDF2-Algorithm with salting.
 * This is the most advanced hashing algorithm Symphony provides.
 *
 * @since Symphony 2.3.1
 * @see toolkit.Cryptography
 */
class PBKDF2 extends Cryptography
{
    /**
     * Salt length
     */
    const SALT_LENGTH = 20;

    /**
     * Key length
     */
    const KEY_LENGTH = 40;

    /**
     * Key length
     */
    const ITERATIONS = 10000;

    /**
     * Algorithm to be used
     */
    const ALGORITHM = 'sha256';

    /**
     * Prefix to identify the algorithm used
     */
    const PREFIX = 'PBKDF2v1';

    /**
     * Uses `PBKDF2` and random salt generation to create a hash based on some input.
     * Original implementation was under public domain, taken from
     * http://www.itnewb.com/tutorial/Encrypting-Passwords-with-PHP-for-Storage-Using-the-RSA-PBKDF2-Standard
     *
     * @param string $input
     * the string to be hashed
     * @param string $salt
     * an optional salt
     * @param integer $iterations
     * an optional number of iterations to be used
     * @param string $keylength
     * an optional length the key will be cropped to fit
     * @return string
     * the hashed string
     */
    public static function hash($input, $salt = null, $iterations = null, $keylength = null)
    {
        if ($salt === null) {
            $salt = self::generateSalt(self::SALT_LENGTH);
        }

        if ($iterations === null) {
            $iterations = self::ITERATIONS;
        }

        if ($keylength === null) {
            $keylength = self::KEY_LENGTH;
        }

        $hashlength = strlen(hash(self::ALGORITHM, null, true));
        $blocks = ceil(self::KEY_LENGTH / $hashlength);
        $key = '';

        for ($block = 1; $block <= $blocks; $block++) {
            $ib = $b = hash_hmac(self::ALGORITHM, $salt . pack('N', $block), $input, true);

            for ($i = 1; $i < $iterations; $i++) {
                $ib ^= ($b = hash_hmac(self::ALGORITHM, $b, $input, true));
            }

            $key .= $ib;
        }

        return self::PREFIX . "|" . $iterations . "|" . $salt . "|" . base64_encode(substr($key, 0, $keylength));
    }

    /**
     * Compares a given hash with a cleantext password. Also extracts the salt
     * from the hash.
     *
     * @param string $input
     *  the cleartext password
     * @param string $hash
     *  the hash the password should be checked against
     * @param boolean $isHash
     * @return boolean
     *  the result of the comparison
     */
    public static function compare($input, $hash, $isHash = false)
    {
        $salt = self::extractSalt($hash);
        $iterations = self::extractIterations($hash);
        $keylength = strlen(base64_decode(self::extractHash($hash)));

        return $hash === self::hash($input, $salt, $iterations, $keylength);
    }

    /**
     * Extracts the hash from a hash/salt-combination
     *
     * @param string $input
     * the hashed string
     * @return string
     * the hash
     */
    public static function extractHash($input)
    {
        $data = explode("|", $input, 4);

        return $data[3];
    }

    /**
     * Extracts the salt from a hash/salt-combination
     *
     * @param string $input
     * the hashed string
     * @return string
     * the salt
     */
    public static function extractSalt($input)
    {
        $data = explode("|", $input, 4);

        return $data[2];
    }

    /**
     * Extracts the saltlength from a hash/salt-combination
     *
     * @param string $input
     * the hashed string
     * @return integer
     * the saltlength
     */
    public static function extractSaltlength($input)
    {
        return strlen(self::extractSalt($input));
    }

    /**
     * Extracts the number of iterations from a hash/salt-combination
     *
     * @param string $input
     * the hashed string
     * @return integer
     * the number of iterations
     */
    public static function extractIterations($input)
    {
        $data = explode("|", $input, 4);

        return (int) $data[1];
    }

    /**
     * Checks if provided hash has been computed by most recent algorithm
     * returns true if otherwise
     *
     * @param string $hash
     * the hash to be checked
     * @return boolean
     * whether the hash should be re-computed
     */
    public static function requiresMigration($hash)
    {
        $length = self::extractSaltlength($hash);
        $iterations = self::extractIterations($hash);
        $keylength = strlen(base64_decode(self::extractHash($hash)));

        if ($length !== self::SALT_LENGTH || $iterations !== self::ITERATIONS || $keylength !== self::KEY_LENGTH) {
            return true;
        } else {
            return false;
        }
    }
}
