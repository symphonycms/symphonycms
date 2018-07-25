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
    const ITERATIONS = 100000;

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
     *  the string to be hashed
     * @param array $options
     *  the options array
     * @param string $options.salt
     *  an optional salt
     * @param integer $options.iterations
     *  an optional number of iterations to be used
     * @param string $options.keylength
     *  an optional length the key will be cropped to fit
     * @return string
     *  the hashed string
     */
    public static function hash($input, array $options = [])
    {
        if (empty($options['salt'])) {
            $salt = self::generateSalt(self::SALT_LENGTH);
        } else {
            $salt = $options['salt'];
        }

        if (empty($options['iterations'])) {
            $iterations = self::ITERATIONS;
        } else {
            $iterations = $options['iterations'];
        }

        if (empty($options['keylength'])) {
            $keylength = self::KEY_LENGTH;
        } else {
            $keylength = $options['keylength'];
        }

        if (empty($options['algorithm'])) {
            $algorithm = self::ALGORITHM;
        } else {
            $algorithm = $options['algorithm'];
        }

        $hashlength = strlen(hash($algorithm, null, true));
        $blocks = ceil($keylength / $hashlength);
        $key = '';

        for ($block = 1; $block <= $blocks; $block++) {
            $ib = $b = hash_hmac($algorithm, $salt . pack('N', $block), $input, true);

            for ($i = 1; $i < $iterations; $i++) {
                $ib ^= ($b = hash_hmac($algorithm, $b, $input, true));
            }

            $key .= $ib;
        }

        return self::PREFIX . "|$algorithm|$iterations|$salt|" . base64_encode(substr($key, 0, $keylength));
    }

    /**
     * Compares a given hash with a clean text password. Also extracts the salt
     * from the hash.
     *
     * @uses hash_equals()
     * @param string $input
     *  the clear text password
     * @param string $hash
     *  the hash the password should be checked against
     * @param bool $isHash
     *  if the $input is already a hash
     * @return boolean
     *  the result of the comparison
     */
    public static function compare($input, $hash, $isHash = false)
    {
        if ($isHash) {
            return hash_equals($hash, $input);
        }
        $salt = self::extractSalt($hash);
        $iterations = self::extractIterations($hash);
        $keylength = strlen(base64_decode(self::extractHash($hash)));
        $algorithm = self::extractAlgorithm($hash);
        $options = [
            'salt' => $salt,
            'iterations' => $iterations,
            'keylength' => $keylength,
            'algorithm' => $algorithm,
        ];
        if (!$algorithm) {
            $hash = self::PREFIX . "|sha256|$iterations|$salt|" . self::extractHash($hash);
        }
        return hash_equals(self::hash($input, $options), $hash);
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
        $data = explode('|', $input, 5);

        return empty($data[4]) ? $data[3] : $data[4];
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
        $data = explode('|', $input, 5);

        return empty($data[4]) ? $data[2] : $data[3];
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
        $data = explode('|', $input, 5);

        return (int) (empty($data[4]) ? $data[1] : $data[2]);
    }

    /**
     * Extracts the algorithm from a hash/salt-combination
     *
     * @param string $input
     *  the hashed string
     * @return string
     *  the algorithm
     */
    public static function extractAlgorithm($input)
    {
        $data = explode('|', $input, 5);

        return empty($data[4]) ? null : $data[1];
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
        $algorithm = self::extractAlgorithm($hash);

        return $length !== self::SALT_LENGTH ||
            $iterations !== self::ITERATIONS ||
            $keylength !== self::KEY_LENGTH ||
            $algorithm !== self::ALGORITHM;
    }
}
