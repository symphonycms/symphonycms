<?php
	/**
	 * @package toolkit
	 */
	/**
	 * The Mutex class is a crude locking class that generates files
	 * with a specific time to live. It has basic functions to create a
	 * lock, release a lock or refresh a lock.
	 */
	Class Mutex{

		/**
		 * Creates a lock file if one does not already exist with a certain
		 * time to live (TTL) at a specific path. If a lock already exists,
		 * false will be returned otherwise boolean depending if a lock
		 * file was created successfully or not.
		 *
		 * @param string $id
		 *  The name of the lock file, which gets obfuscated using
		 *  generateLockFileName.
		 * @param integer $ttl
		 *  The length, in seconds, that the lock should exist for. Defaults
		 *  to 5.
		 * @param string $path
		 *  The path the lock should be written, defaults to the current
		 *  working directory
		 * @return boolean
		 */
		public static function acquire($id, $ttl=5, $path='.'){
			$lockFile = self::__generateLockFileName($id, $path);

			if(self::__lockExists($lockFile)){
				$age = time() - filemtime($lockFile);

				if($age < $ttl) return false;

			}

			return self::__createLock($lockFile);
		}

		/**
		 * Removes a lock file. This is the only way a lock file can be removed
		 *
		 * @param string $id
		 *  The original name of the lock file (note that this will be different from
		 *  the name of the file saved on the file system)
		 * @param string $path
		 *  The path to the lock, defaults to the current working directory
		 * @return boolean
		 */
		public static function release($id, $path='.'){
			$lockFile = self::__GenerateLockFileName($id, $path);

			if(self::__lockExists($lockFile)) return unlink($lockFile);

			return true;
		}

		/**
		 * Updates a lock file to 'keep alive' for another 'x' seconds.
		 *
		 * @param string $id
		 *  The name of the lock file, which gets obfuscated using
		 *  `__generateLockFileName()`.
		 * @param integer $ttl
		 *  The length, in seconds, that the lock should be extended by.
		 *  Defaults to 5.
		 * @param string $path
		 *  The path to the lock, defaults to the current working directory
		 * @return boolean
		 */
		public static function refresh($id, $ttl=5, $path='.'){
			return touch(self::__generateLockFileName($id, $path));
		}

		/**
		 * Private function that writes the lock to the file system with the
		 * contents Mutex lock file - DO NOT DELETE
		 *
		 * @param string $lockFile
		 *  The obfuscated lock name
		 * @return boolean
		 */
		 private static function __createLock($lockFile){
			file_put_contents($lockFile,  'Mutex lock file - DO NOT DELETE', LOCK_EX);

			return touch($lockFile);
		}

		/**
		 * Checks if a lock exists, purely on the presence on the lock file.
		 * This function takes the unobfuscated lock name
		 *
		 * @since Symphony 2.2
		 * @param string $id
		 *  The name of the lock file, which gets obfuscated using
		 *  generateLockFileName.
		 * @param string $path
		 *  The path the lock should be written, defaults to the current
		 *  working directory
		 * @return boolean
		 */
		public static function lockExists($id, $path){
			$lockFile = self::__generateLockFileName($id, $path);

			return file_exists($lockFile);
		}

		/**
		 * Checks if a lock exists, purely on the presence on the lock file
		 *
		 * @param string $lockFile
		 *  The obfuscated lock name
		 * @return boolean
		 */
		private static function __lockExists($lockFile){
			return file_exists($lockFile);
		}

		/**
		 * Generates a lock filename using an MD5 hash of the `$id` and
		 * `$path`. Lock files are given a .lock extension
		 *
		 * @param string $id
		 *  The name of the lock file to be obfuscated
		 * @param string $path
		 *  The path the lock should be written
		 * @return string
		 */
		private static function __generateLockFileName($id, $path){
			return rtrim($path, '/') . '/' . md5($id) . '.lock';
		}

	}
