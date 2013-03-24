<?php
	/**
	 * @package toolkit
	 */
	/**
	 * Cryptography is a utility class that offers a number of general purpose cryptography-
	 * related functions for message digestation as well as (backwards-)compatibility
	 * checking. The message digestation algorithms are placed in the subclasses
	 * `MD5`, `SHA1` and `PBKDF2`.
	 *
	 * @since Symphony 2.3.1
	 * @see cryptography.MD5
	 * @see cryptography.SHA1
	 * @see cryptography.PBKDF2
	 */
	require_once(TOOLKIT . '/cryptography/class.md5.php');
	require_once(TOOLKIT . '/cryptography/class.sha1.php');
	require_once(TOOLKIT . '/cryptography/class.pbkdf2.php');

	Class Cryptography{

		/**
		 * Uses an instance of `PBKDF2` to create a hash. If you require other
		 * hashes, see the related functions of the `MD5` or `SHA1` classes
		 *
		 * @see cryptography.MD5#hash()
		 * @see cryptography.SHA1#hash()
		 * @see cryptography.PBKDF2#hash()
	 	 *
		 * @param string $input
		 * the string to be hashed
		 * @return string
		 * the hashed string
		 */
		public static function hash($input){
			return PBKDF2::hash($input);
		}

		/**
		 * Compares a given hash with a clean text password by figuring out the
		 * algorithm that has been used and then calling the appropriate sub-class
		 *
		 * @see cryptography.MD5#compare()
		 * @see cryptography.SHA1#compare()
		 * @see cryptography.PBKDF2#compare()
		 *
		 * @param string $input
		 * the cleartext password
		 * @param string $hash
		 * the hash the password should be checked against
		 * @return boolean
		 * the result of the comparison
		 */
		public static function compare($input, $hash, $isHash=false){
			$version = substr($hash, 0, 8);

			if($isHash == true) {
				return $input == $hash;
			}
			elseif($version == 'PBKDF2v1') { // salted PBKDF2
				return PBKDF2::compare($input, $hash);
			}
			elseif(strlen($hash) == 40){ // legacy, unsalted SHA1
				return SHA1::compare($input, $hash);
			}
			elseif(strlen($hash) == 32){ // legacy, unsalted MD5
				return MD5::compare($input, $hash);
			}
			else { // the hash provided doesn't make any sense
				return false;
			}
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
		public static function requiresMigration($hash){
			$version = substr($hash, 0, 8);

			if($version == 'PBKDF2v1') { // salted PBKDF2, let the responsible class decide
				return PBKDF2::requiresMigration($hash);
			}
			else { // everything else
				return true;
			}
		}

		/**
		 * Generates a salt to be used in message digestation.
		 *
		 * @param integer $length
		 * the length of the salt
		 * @return string
		 * a hexadecimal string
		 */
		public static function generateSalt($length) {
			mt_srand(microtime(true)*100000 + memory_get_usage(true));
			return substr(sha1(uniqid(mt_rand(), true)), 0, $length);
		}
	}
