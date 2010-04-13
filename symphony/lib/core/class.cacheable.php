<?php
	
	require_once(TOOLKIT . '/class.mutex.php');
	
	##Interface for cacheable objects
	Class Cacheable{
		private function __optimise(){
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

		public function forceExpiry($hash){	
			// TODO: Replace with delete():
			Symphony::Database()->query("DELETE FROM `tbl_cache` WHERE `hash` = '$hash' LIMIT 1");				
		}

		public function clean(){	
			// TODO: Replace with delete():
			Symphony::Database()->query("DELETE FROM `tbl_cache` WHERE UNIX_TIMESTAMP() > `expiry`");
			$this->__optimise();
		}

		public function check($hash){
			$results = Symphony::Database()->query(
				"SELECT SQL_NO_CACHE * FROM `tbl_cache` AS c WHERE c.hash = '%s' AND (c.expiry IS NULL OR UNIX_TIMESTAMP() <= c.expiry) LIMIT 1",
				array($hash)
			);
			
			if ($results->valid()) {
				$cache = $results->current();
				
				if (!$cache->data = $this->decompressData($cache->data)) {
					$this->forceExpiry($hash);
					return false;
				}
				
				return $cache;
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
