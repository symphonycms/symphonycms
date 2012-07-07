<?php
	/**
	 * @package toolkit
	 */
	/**
	 * MD5 is a cryptography class for hashing and comparing messages
	 * using the MD5-Algorithm
	 * This code exists only for backwards-compatibility-purposes
	 * and should not be used when writing new features.
	 */
	Class MD5 extends Cryptography{
		
		/**
		 * Uses `MD5` to create a hash based on some input
		 *
		 * @param string $input
		 * the string to be hashed
		 * @param string $algorithm
		 * a valid PHP function handle
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
		 * the string to be compared
		 * @param string $algorithm
		 * a valid PHP function handle
		 * @return string
		 * the hashed string
		 */
		public static function compare($input, $hash){
			return ($hash == self::hash($input));
		}
	}
