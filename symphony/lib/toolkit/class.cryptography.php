<?php
	/**
	 * @package toolkit
	 */
	/**
	 * Cryptography is a utility class that offers a number of cryptography-
	 * related functions such as message digestation.
	 */
	require_once(TOOLKIT . '/class.md5.php');
	require_once(TOOLKIT . '/class.sha1.php');
	require_once(TOOLKIT . '/class.ssha1.php');

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
		 * Compares a given hash with a cleantext password. Extracts the salt
		 * from the hash if required.
		 *
		 * @param string $input
		 * the string to be compared
		 * @param string $algorithm
		 * a valid PHP function handle
		 * @return string
		 * the hashed string
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
		 * @return string
		 * a hexadecimal string
		 */
		public static function generateSalt($length) {
			mt_srand(microtime(true)*100000 + memory_get_usage(true));
			return substr(sha1(uniqid(mt_rand(), true)), 0, $length);
		}
	}
