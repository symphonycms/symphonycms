<?php
	/**
	 * @package toolkit
	 */
	/**
	 * Cryptography is a utility class that offers a number of general purpose cryptography-
	 * related functions for message digestation as well as (backwards-)compatibility
	 * checking. The message digestation algorithms are placed in the subclasses
	 * `MD5`, `SHA1` and `SSHA1`.

	 * @see toolkit.MD5
	 * @see toolkit.SHA1
	 * @see toolkit.SSHA1
	 */
	require_once(TOOLKIT . '/class.md5.php');
	require_once(TOOLKIT . '/class.sha1.php');
	require_once(TOOLKIT . '/class.ssha1.php');

	Class Cryptography{
		
		/**
		 * Uses an instance of `MD5`, `SHA1` or `SSHA1` to create a hash
		 *
		 * @see toolkit.MD5#hash()
		 * @see toolkit.SHA1#hash()
		 * @see toolkit.SSHA1#hash()
	 	 *
		 * @param string $input
		 * the string to be hashed
		 * @param string $algorithm
		 * a handle for the algorithm to be used
		 * @deprecated This parameter will be removed in a future release. The use of i.e. `SHA1::hash()` is recommended instead when not using the default algorithm.
		 * @return string
		 * the hashed string
		 */
		public static function hash($input, $algorithm='ssha1'){
			switch($algorithm) {
				case 'md5':
					return MD5::hash($input);
					break;
				case 'sha1':
					return SHA1::hash($input);
					break;
				case 'ssha1':
				default:
					return "SSHA1" . SSHA1::hash($input);
					break;
			}
		}

		/**
		 * Compares a given hash with a cleantext password by figuring out the
		 * algorithm that has been used and then calling the approriate sub-class
		 *
		 * @see toolkit.MD5#compare()
		 * @see toolkit.SHA1#compare()
		 * @see toolkit.SSHA1#compare()
		 *
		 * @param string $input
		 * the cleartext password
		 * @param string $hash
		 * the hash the password should be checked against
		 * @return bool
		 * the result of the comparison
		 */
		public static function compare($input, $hash, $isHash=false){
			$version = substr($hash, 0, 5);

			if($version == 'SSHA1') { // salted SHA1
				return SSHA1::compare($input, $hash);
			}
			elseif(strlen($hash) == 40){ // legacy, unsalted SHA1
				return SHA1::compare($input, $hash);
			}
			elseif(strlen($hash) == 32){ // legacy, unsalted MD5
				return MD5::compare($input, $hash);
			}
		}

		/**
		 * Checks if provided hash has been computed by most recent algorithm
		 * returns true if otherwise
		 *
		 * @param string $hash
		 * the hash to be checked
		 * @return bool
		 * whether the hash should be re-computed
		 */
		public static function requiresMigration($hash){
			$version = substr($hash, 0, 5);

			if($version == 'SSHA1') { // salted SHA1
				return false;
			}
			else {
				return true;
			}
		}

		/**
		 * Extracts the hash from a hash/salt-combination
		 *
		 * @param string $input
		 * the hashed string
		 * @param int $length
		 * the length of the salt
		 * @return string
		 * the hash
		 */
		public static function extractHash($input, $length){
			return substr($input, 5+$length);
		}

		/**
		 * Extracts the salt from a hash/salt-combination
		 *
		 * @param string $input
		 * the hashed string
		 * @param int $length
		 * the length of the salt
		 * @return string
		 * the salt
		 */
		public static function extractSalt($input, $length){
			return substr($input, 5, $length);
		}

		/**
		 * Generates a salt to be used in message digestation.
		 *
		 * @param int $length
		 * the length of the salt
		 * @return string
		 * a hexadecimal string
		 */
		public static function generateSalt($length) {
			mt_srand(microtime(true)*100000 + memory_get_usage(true));
			return substr(sha1(uniqid(mt_rand(), true)), 0, $length);
		}
	}
