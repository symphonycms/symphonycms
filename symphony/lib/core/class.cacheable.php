<?php

	/**
	 * @package core
	 */

	 /**
	  * The Cacheable class is used to store data in the dedicated Symphony
	  * cache table. It is used by Symphony for Session management and by
	  * the Dynamic XML datasource, but it can be used by extensions to store
	  * anything. The cache table is `tbl_cache`
	  */
	require_once(TOOLKIT . '/class.mutex.php');

	Class Cacheable{

		/**
		 * An instance of the MySQL class to store the cached
		 * data in.
		 *
		 * @var MySQL
		 */
		public $Database;

		/**
		 * The constructor for the Cacheable takes an instance of the
		 * MySQL class and assigns it to `$this->Database`
		 *
		 * @param MySQL $Database
		 *  An instance of the MySQL class to store the cached
		 *  data in.
		 */
		public function __construct(MySQL $Database){
			$this->Database = $Database;
		}

		/**
		 * This function will compress data for storage in `tbl_cache`.
		 * It is left to the user to define a unique hash for this data so that it can be
		 * retrieved in the future. Optionally, a `$ttl` parameter can
		 * be passed for this data. If this is omitted, it data is considered to be valid
		 * forever. This function utilizes the Mutex class to act as a crude locking
		 * mechanism.
		 *
		 * @see toolkit.Mutex
		 * @param string $hash
		 *  The hash of the Cached object, as defined by the user
		 * @param string $data
		 *  The data to be cached, this will be compressed prior to saving.
		 * @param integer $ttl
		 *  A integer representing how long the data should be valid for in seconds.
		 *  By default this is null, meaning the data is valid forever
		 * @return boolean
		 *  If an error occurs, this function will return false otherwise true
		 */
		public function write($hash, $data, $ttl = null){

			if(!Mutex::acquire($hash, 2, TMP)) return false;

			$creation = time();
			$expiry = null;

			$ttl = intval($ttl);
			if($ttl > 0) $expiry = $creation + ($ttl * 60);

			if(!$data = $this->compressData($data)) return false;

			$this->forceExpiry($hash);
			$this->Database->insert(array('hash' => $hash, 'creation' => $creation, 'expiry' => $expiry, 'data' => $data), 'tbl_cache');

			Mutex::release($hash, TMP);

			return true;
		}

		/**
		 * Given some data, this function will compress it using gzcompress
		 * and then base64_encodes the output. If this fails, false is returned
		 * otherwise the compressed data
		 *
		 * @param string $data
		 *  The data to compress
		 * @return string|boolean
		 *  The compressed data, or false if an error occurred
		 */
		public function compressData($data){
			if(!$data = base64_encode(gzcompress($data))) return false;
			return $data;
		}

		/**
		 * Given compressed data, this function will decompress it and return
		 * the output.
		 *
		 * @param string $data
		 *  The data to decompress
		 * @return string|boolean
		 *  The decompressed data, or false if an error occurred
		 */
		public function decompressData($data){
			if(!$data = gzuncompress(base64_decode($data))) return false;
			return $data;
		}

		/**
		 * Given the hash of a some data, check to see whether it exists in
		 * `tbl_cache`. If no cached object is found, this
		 * function will return false, otherwise the cached object will be
		 * returned as an array.
		 *
		 * @param string $hash
		 *  The hash of the Cached object, as defined by the user
		 * @return array|boolean
		 *  An associative array of the cached object including the creation time,
		 *  expiry time, the hash and the data. If the object is not found, false will
		 *  be returned.
		 */
		public function check($hash){

			if($c = $this->Database->fetchRow(0, "SELECT SQL_NO_CACHE * FROM `tbl_cache` WHERE `hash` = '$hash' AND (`expiry` IS NULL OR UNIX_TIMESTAMP() <= `expiry`) LIMIT 1")){

				if(!$c['data'] = $this->decompressData($c['data'])){
					$this->forceExpiry($hash);
					return false;
				}

				return $c;

			}

			$this->clean();
			return false;
		}

		/**
		 * Given the hash of a cacheable object, remove it from `tbl_cache`
		 * regardless of if it has expired or not.
		 *
		 * @param string $hash
		 *  The hash of the Cached object, as defined by the user
		 */
		public function forceExpiry($hash){
			$this->Database->query("DELETE FROM `tbl_cache` WHERE `hash` = '$hash' LIMIT 1");
		}

		/**
		 * Removes all cache objects from `tbl_cache` that have expired.
		 * After removing, the function uses the optimise function
		 *
		 * @see core.Cacheable#optimise()
		 */
		public function clean(){
			$this->Database->query("DELETE FROM `tbl_cache` WHERE UNIX_TIMESTAMP() > `expiry`");
			$this->__optimise();
		}

		/**
		 * Runs a MySQL OPTIMIZE query on `tbl_cache`
		 */
		private function __optimise(){
			$this->Database->query('OPTIMIZE TABLE `tbl_cache`');
		}

	}
