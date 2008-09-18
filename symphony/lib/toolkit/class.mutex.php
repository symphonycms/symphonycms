<?php

	Class Mutex{

		public static function acquire($id, $ttl=5, $path='.'){
			$lockFile = self::__generateLockFileName($id, $path);

			if(self::__lockExists($lockFile)){
				$age = time() - filectime($lockFile); 

				if($age < $ttl) return false;
				
			}
			
			return self::__createLock($lockFile);

		}

		public static function release($id, $path='.'){
			$lockFile = self::__GenerateLockFileName($id, $path);

			if(self::__lockExists($lockFile)) return unlink($lockFile);

			return true;
		}

		public static function refresh($id, $ttl=5, $path='.'){
			return touch(self::__generateLockFileName($id, $path));
		}

		private static function __createLock($lockFile){

			if(!$fp = fopen($lockFile, 'w')) return false;

			fwrite($fp, 'Mutex lock file - DO NOT DELETE');
			fclose($fp);

			touch($lockFile);
			
			return true;
		}	

		private static function __lockExists($lockFile){
			return file_exists($lockFile);
		}

		private static function __generateLockFileName($id, $path){
			return rtrim($path, '/') . '/' . md5($id) . '.lock';
		}

	}
	
?>