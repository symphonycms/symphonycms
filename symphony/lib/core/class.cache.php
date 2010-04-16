<?php

	require_once(TOOLKIT . '/class.mutex.php');

	Interface iCacheDriver{
		public function read($key);
		public function write($key, $data, $ttl=NULL);
		public function delete($key);
		public function purge();
	}

	// 	TODO: Test, ensure read returns false on failure
	Final Class CacheDriverMemcache implements iCacheDriver{

		static $_connection;

		public function __construct(){
			self::$_connection = new Memcache;
			self::$_connection->connect('localhost', 11211);
		}

		public function __destruct(){
			self::$_connection->close();
			unset(self::$_connection);
		}

		public function read($key){
			return self::$_connection->get($key);
		}

		public function write($key, $data, $ttl=NULL){
			return self::$_connection->set($key, $data, MEMCACHE_COMPRESSED, $ttl);
		}

		public function delete($key){
			self::$_connection->delete($key);
		}

		public function purge(){
			return self::$_connection->flush();
		}

	}

	## Dummy driver. This is used as a simple way to totally disable the cache
	Final Class CacheDriverNone{
		public function __call($name, $args){
			if($name == 'read') return false;
			return true;
		}
	}

	Final Class CacheDriverAPC implements iCacheDriver{

		public function read($key){
			$success = NULL;
			$result = apc_fetch($key, $success);

			return ($success === true) ? $result : false;
		}

		public function write($key, $data, $ttl=NULL){
			return apc_store($key, $data, $ttl);
		}

		public function delete($key){
			return apc_delete($key);
		}

		public function purge(){
			return apc_clear_cache('user');
		}

	}

	Final Class CacheDriverFile implements iCacheDriver{

		public function read($key){
			$cache_file = CACHE . '/' . md5($key) . '.cache';
			return (file_exists($cache_file) ? self::__decompress(file_get_contents($cache_file)) : false);
		}

		public function write($key, $data, $ttl=NULL){
			if(!Mutex::acquire(md5($key), 2, TMP)) return;
			file_put_contents(CACHE . '/' . md5($key) . '.cache', self::__compress($data));
			Mutex::release(md5($key), TMP);
		}

		public function delete($key){
			if(file_exists(CACHE . '/' . md5($key) . '.cache')) unlink(CACHE . '/' . md5($key) . '.cache');
		}

		public function purge(){
			foreach(new DirectoryIterator(CACHE) as $file){
				if($file->isDot() || $file->isDir() || !preg_match('/^[a-f0-9]{32}\.cache$/i', $file->getFilename())) continue;
				unlink(CACHE . '/' . $file->getFilename());
			}
		}

		private static function __decompress($data){

			if(!preg_match('/^(0|1)::([a-f0-9]{32})::(-?\d+)\r\n\r\n(.*)/i', $data, $matches)) return false;

			list($original, $serialize_bit, $checksum, $ttl, $data) = $matches;

			$data = @gzuncompress(@base64_decode($matches[4]));

			if(md5($data) != $checksum) return false;
			elseif((integer)$ttl > 0 && $ttl <= time()) return false;

			if((integer)$serialize_bit == 1) $data = unserialize($data);

			return $data;
		}

		private static function __compress($data, $ttl=NULL){
			$serialize_bit = '0';
			if(is_array($data) || is_object($data)){
				$data = serialize($data);
				$serialize_bit = '1';
			}

			$checksum = md5($data);

			if($ttl > 0) $ttl += time();
			else $ttl = -1;

			$data = "{$serialize_bit}::{$checksum}::{$ttl}\r\n\r\n" . @base64_encode(@gzcompress($data));

			return $data;
		}
	}

	//Final Class CacheDriverDB implements iCacheDriver{
	//
	//}

	Class CacheException extends Exception{
	}

	Class Cache{

	    static private $instance;
		static private $driver;
		static private $enabled;

		static public function disable(){
			self::$enabled = false;
		}

		static public function enable(){
			self::$enabled = true;
		}

		static public function isEnabled(){
			return (bool)self::$enabled;
		}

		private function __construct(){
			self::enable();
		}

		static public function getDriver(){
			return self::$driver;
		}

		static public function setDriver($driver){
			$old = self::$driver;
			self::$driver = $driver;
			return $old;
		}

	    static public function instance($driver=NULL){

			if(is_null($driver) && self::getDriver() == NULL) throw new CacheException('No cache driver was specified');
			elseif(!is_null($driver) && $driver != self::getDriver()){
				self::setDriver($driver);
			}

	        if(!(self::$instance instanceof Cache)){

				$class = 'CacheDriver' . self::$driver;
				if(!class_exists($class)){
					throw new CacheException(__('The cache driver "%s" could not be found.', array(self::$driver)));
				}
				self::$instance = new $class;
			}

	        return self::$instance;
	    }

		/*public static function read($key){
			if(!self::$enabled) return false;

			return apc_fetch($key);
		}

		public static function write($key, $data, $ttl=NULL){
			if(!self::$enabled) return;

			return apc_store($key, $data, $ttl);
		}

		public static function delete($key){
			if(!self::$enabled) return;

			return apc_delete($key);
		}

		public static function purge(){
			if(!self::$enabled) return;

			return apc_clear_cache('user');
		}
		*/

	/*	private static function __decompress($data){
			if(!$data = @gzuncompress(@base64_decode($data))) return false;
			return $data;
		}

		private static function __compress($data){
			if(!$data = @base64_encode(@gzcompress($data))) return false;
			return $data;
		}*/


		/*private function __optimise(){
			Symphony::Database()->query('OPTIMIZE TABLE `tbl_cache`');
		}

		public function decompressData($data){
			if(!$data = @gzuncompress(@base64_decode($data))) return false;
			return $data;
		}

		public function compressData($data){
			if(!$data = @base64_encode(@gzcompress($data))) return false;
			return $data;
		}

		public function forceExpiry($identifier, $isID=false){
			if(empty($identifier)) return;
			Symphony::Database()->query("DELETE FROM `tbl_cache` WHERE `".($isID ? 'id' : 'hash')."` ".(is_array($identifier) ? "IN ('".@implode("','", $identifier)."')" : "= '$identifier' LIMIT 1"));
			$this->clean();
		}

		public function clean(){
			Symphony::Database()->query("DELETE FROM `tbl_cache` WHERE UNIX_TIMESTAMP() > `expiry`");
			$this->__optimise();
		}

		public function check($hash){

			if($records = Symphony::Database()->query("SELECT SQL_NO_CACHE * FROM `tbl_cache` WHERE `hash` = '$hash' AND (`expiry` IS NULL OR UNIX_TIMESTAMP() <= `expiry`) LIMIT 1")){

				$c = $records->current();

				if(!$c->data = $this->decompressData($c->data)){
					$this->forceExpiry($hash);
					return false;
				}

				return $c;

			}

			$this->clean();
			return false;
		}

		public function write($hash, $data, $ttl=NULL){

			if(!Mutex::acquire($hash, 2, TMP)) return;

			$creation = time();
			$expiry = NULL;

			$ttl = intval($ttl);
			if($ttl > 0) $expiry = $creation + $ttl;

			if(!$data = $this->compressData($data)) return false;

			$this->forceExpiry($hash);
			$insert_id = Symphony::Database()->insert(array('hash' => $hash, 'creation' => $creation, 'expiry' => $expiry, 'data' => $data), 'tbl_cache');

			Mutex::release($hash, TMP);

			return $insert_id;

		}*/

	}

/*

	require_once(TOOLKIT . '/class.mutex.php');

	##Interface for cacheable objects
	Class Cacheable{

		public $Database;

		function __construct(MySQL $Database){
			$this->Database = $Database;
		}

		private function __optimise(){
			$this->Database->query('OPTIMIZE TABLE `tbl_cache`');
		}

		public function decompressData($data){
			if(!$data = @gzuncompress(@base64_decode($data))) return false;
			return $data;
		}

		public function compressData($data){
			if(!$data = @base64_encode(@gzcompress($data))) return false;
			return $data;
		}

		public function forceExpiry($hash){
			$this->Database->query("DELETE FROM `tbl_cache` WHERE `hash` = '$hash' LIMIT 1");
		}

		public function clean(){
			$this->Database->query("DELETE FROM `tbl_cache` WHERE UNIX_TIMESTAMP() > `expiry`");
			$this->__optimise();
		}

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

		public function write($hash, $data, $ttl=NULL){

			if(!Mutex::acquire($hash, 2, TMP)) return;

			$creation = time();
			$expiry = NULL;

			$ttl = intval($ttl);
			if($ttl > 0) $expiry = $creation + ($ttl * 60);

			if(!$data = $this->compressData($data)) return false;

			$this->forceExpiry($hash);
			$this->Database->insert(array('hash' => $hash, 'creation' => $creation, 'expiry' => $expiry, 'data' => $data), 'tbl_cache');

			Mutex::release($hash, TMP);

		}

	}
*/