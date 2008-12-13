<?php

/**
 * Based on: http://pl.php.net/manual/en/function.session-set-save-handler.php#81761 by klose at openriverbed dot de
 *   which was based on: http://pl.php.net/manual/en/function.session-set-save-handler.php#79706 by maria at junkies dot jp
 */

	require_once(CORE . '/class.cacheable.php');
	
	Class Session{
		
		private static $_initialized;
		private static $_registered;
		private static $_db;
		private static $_cache;

		public static function start($lifetime = 0, $path = '/', $domain = NULL) {
			if (!self::$_initialized) {
				global $Frontend;
				global $Admin;
				if (isset($Frontend->Database)) {
					self::$_db = &$Frontend->Database;
				}
				else if (isset($Admin->Database)) {
					self::$_db = &$Admin->Database;
				}
				else {
					return false;
				}

				self::$_cache = new Cacheable(self::$_db);
				$installed = self::$_cache->check('_session_config');
				if (!$installed) {
					if (!self::createTable()) return false;
					self::$_cache->write('_session_config', true);
				}

				ini_set('session.save_handler', 'user');
				session_set_save_handler(
					array('Session', 'open'),
					array('Session', 'close'),
					array('Session', 'read'),
					array('Session', 'write'),
					array('Session', 'destroy'),
					array('Session', 'gc')
				);

				session_set_cookie_params($lifetime, $path, ($domain ? $domain : self::getDomain()), false, true);

				self::$_initialized = true;

				if (session_id() == '') session_start();
			}

			return session_id();
		}

		public static function createTable() {
			if (!self::$_db) return false;

			return self::$_db->query(
'CREATE TABLE IF NOT EXISTS `tbl_sessions` (
  `session` varchar(255) character set utf8 collate utf8_bin NOT NULL,
  `session_expires` int(10) unsigned NOT NULL default \'0\',
  `session_data` text collate utf8_unicode_ci,
  PRIMARY KEY  (`session`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;'
			);
		}

		public function getDomain() {
			
			if(isset($_SERVER['HTTP_HOST'])){

				$dom = $_SERVER['HTTP_HOST'];

				if (preg_match('/(localhost|127\.0\.0\.1)/', $dom)) return NULL; // prevent problems on local setups

				if(strtolower(substr($dom, 0, 4)) == 'www.') $dom = substr($dom, 4);

				$uses_port = strpos($dom, ':');
				if($uses_port) $dom = substr($dom, 0, $uses_port);

				$dom = '.' . $dom;

				return $dom; 
			} 

			return false;
		    
		}
		
		public static function open() {
			if (!self::$_db) return false;

			if (!self::$_registered) {
				register_shutdown_function('session_write_close');
				self::$_registered = true;
			}

			return self::$_registered;
		}
		
		public static function close() {
			return true;
		}
		
		public static function read($id) {
			if (!self::$_db) return '';

			$fields = array('session' => $id);
			self::$_db->cleanFields($fields);

			return self::$_db->fetchVar('session_data', 0, 'SELECT `session_data` FROM `tbl_sessions` WHERE `session` = '.$fields['session']);
		}

		public static function write($id, $data) {
			if (!self::$_db) return false;

			$fields = array('session' => $id, 'session_expires' => time(), 'session_data' => $data);
			return self::$_db->insert($fields, 'tbl_sessions', true);
		}

		public static function destroy($id) {
			if (!self::$_db) return false;

			$fields = array('session' => $id);
			self::$_db->cleanFields($fields);

			// Database->delete() does not return value :(
			return self::$_db->query('DELETE FROM `tbl_sessions` WHERE `session` = '.$fields['session']);
		}

		public static function gc($max) {
			if (!self::$_db) return false;

			$fields = array('session_expires' => time() - $max);
			self::$_db->cleanFields($fields);

			// Database->delete() does not return value :(
			return self::$_db->query('DELETE FROM `tbl_sessions` WHERE `session_expires` = '.$fields['session_expires']);
		}
	}

?>