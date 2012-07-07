<?php
	/**
	 * @package toolkit
	 */
	/**
	 * Cryptography is a utility class that offers a number of cryptography-
	 * related functions such as message digestation.
	 */
	Class Cryptography{

		/**
		 * Uses `SHA1` or `MD5` to create a hash based on some input
		 * This function is currently very basic, but would allow
		 * future expansion. Salting the hash comes to mind.
		 *
		 * @param string $input
		 * the string to be hashed
		 * @param string $algorithm
		 * a valid PHP function handle
		 * @return string
		 * the hashed string
		 */
		public static function hash($input, $algorithm='sha1'){
			return hash($algorithm, $input);
		}
	}
