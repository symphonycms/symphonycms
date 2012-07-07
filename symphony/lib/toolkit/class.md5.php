<?php
	/**
	 * @package toolkit
	 */
	/**
	 * MD5 is a cryptography class for hashing and comparing messages
	 * using the MD5-Algorithm
	 *
	 * @deprecated This code is regarded as insecure and exists only for backwards-compatibility-purposes.
	 * It should not be used when writing new password-related features.
	 */
	Class MD5 extends Cryptography{
		
		/**
		 * Uses `MD5` to create a hash based on some input
		 *
		 * @param string $input
		 * the string to be hashed
		 * @return string
		 * the hashed string
		 */
		public static function hash($input){
			return md5($input);
		}

		/**
		 * Compares a given hash with a cleantext password. 	
		 *
		 * @param string $input
		 * the cleartext password
		 * @param string $hash
		 * the hash the password should be checked against
		 * @return bool
		 * the result of the comparison
		 */
		public static function compare($input, $hash){
			return ($hash == self::hash($input));
		}
	}
