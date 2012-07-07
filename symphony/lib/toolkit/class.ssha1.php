<?php
	/**
	 * @package toolkit
	 */
	/**
	 * SHA1 is a cryptography class for hashing and comparing messages
	 * using the SHA1-Algorithm with salting.
	 * This is the most advanced hashing algorithm Symphony provides.
	 */
	Class SSHA1 extends Cryptography{

		/**
		 * Salt length
		 */
		const SALT_LENGTH = 10;

		/**
		 * Uses `SHA1` and random salt generation to create a hash based on some input
		 *
		 * @param string $input
		 * the string to be hashed
		 * @param string $algorithm
		 * a valid PHP function handle
		 * @return string
		 * the hashed string
		 */
		public static function hash($input, $salt=NULL){
			if($salt === NULL)
				$salt = self::generateSalt(self::SALT_LENGTH);

			return $salt . sha1($salt . $input);
		}

		/**
		 * Compares a given hash with a cleantext password. Also extracts the salt
		 * from the hash.
		 *
		 * @param string $input
		 * the string to be compared
		 * @param string $algorithm
		 * a valid PHP function handle
		 * @return string
		 * the hashed string
		 */
		public static function compare($input, $hash){
			$salt = self::extractSalt($hash, self::SALT_LENGTH);
			$hash = self::extractHash($hash, self::SALT_LENGTH);

			return ($salt . $hash == self::hash($input, $salt));
		}
	}
