<?php
	/**
	 * @package toolkit
	 */
	/**
	 * PBKDF2 is a cryptography class for hashing and comparing messages
	 * using the PBKDF2-Algorithm with salting.
	 * This is the most advanced hashing algorithm Symphony provides.
	 */
	Class PBKDF2 extends Cryptography{

		/**
		 * Salt length
		 */
		const SALT_LENGTH = 10;

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
		 * Uses `PBKDF2` and random salt generation to create a hash based on some input.
		 * Original implementation was under public domain, taken from
		 * http://www.itnewb.com/tutorial/Encrypting-Passwords-with-PHP-for-Storage-Using-the-RSA-PBKDF2-Standard
		 *
		 * @param string $input
		 * the string to be hashed
		 * @param string $salt
		 * an optional salt
		 * @return string
		 * the hashed string
		 */
		public static function hash($input, $salt=NULL){
			if($salt === NULL)
				$salt = self::generateSalt(self::SALT_LENGTH);

			$hashlength = strlen(hash(self::ALGORITHM, null, true));
			$blocks = ceil(self::KEY_LENGTH / $hashlength);
			$key = '';

			for ($block = 1; $block <= $blocks; $block++) {
				$ib = $b = hash_hmac(self::ALGORITHM, $salt . pack('N', $block), $input, true);
				for ($i = 1; $i < self::ITERATIONS; $i++)
					$ib ^= ($b = hash_hmac(self::ALGORITHM, $b, $input, true));
				$key .= $ib;
			}

			return $salt . substr(base64_encode($key), 0, self::KEY_LENGTH);
		}

		/**
		 * Compares a given hash with a cleantext password. Also extracts the salt
		 * from the hash.
		 *
		 * @param string $input
		 * the cleartext password
		 * @param string $hash
		 * the hash the password should be checked against
		 * @return bool
		 * the result of the comparison
		 */
		public static function compare($input, $hash){
			$salt = self::extractSalt($hash, self::SALT_LENGTH);
			$hash = self::extractHash($hash, self::SALT_LENGTH);

			return ($salt . $hash == self::hash($input, $salt));
		}
	}
