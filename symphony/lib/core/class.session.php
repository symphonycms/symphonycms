<?php

	/**
	 * Based on: http://pl.php.net/manual/en/function.session-set-save-handler.php#81761 by klose at openriverbed dot de
	 *   which was based on: http://pl.php.net/manual/en/function.session-set-save-handler.php#79706 by maria at junkies dot jp
	 */

	require_once(CORE . '/class.cacheable.php');

	Class Session{

		private static $_initialized;
		private static $_registered;
		private static $_cache;

		public static function start($lifetime = 0, $path = '/', $domain = NULL, $httpOnly = false) {

			if (!self::$_initialized) {

				if(!is_object(Symphony::Database()) || !Symphony::Database()->isConnected()) return false;

				self::$_cache = new Cacheable(Symphony::Database());

				if (self::$_cache->check('_session_config') === false) {
					self::createTable();
					self::$_cache->write('_session_config', true);
				}

				if (session_id() == '') {
					ini_set('session.save_handler', 'user');
					ini_set('session.gc_maxlifetime', $lifetime);
					ini_set('session.gc_probability', '1');
					ini_set('session.gc_divisor', '3');
				}

				session_set_save_handler(
					array('Session', 'open'),
					array('Session', 'close'),
					array('Session', 'read'),
					array('Session', 'write'),
					array('Session', 'destroy'),
					array('Session', 'gc')
				);

				session_set_cookie_params($lifetime, $path, ($domain ? $domain : self::getDomain()), false, $httpOnly);

				if(session_id() == ""){
					if(headers_sent()){
						throw new Exception('Headers already sent. Cannot start session.');
					}
					session_start();
				}

				self::$_initialized = true;
			}

			return session_id();
		}

		public static function createTable() {
			Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_sessions` (
				  `session` varchar(255) NOT NULL,
				  `session_expires` int(10) unsigned NOT NULL default '0',
				  `session_data` text,
				  PRIMARY KEY  (`session`)
				);"
			);
		}

		public static function getDomain() {

			if(isset($_SERVER['HTTP_HOST'])){

				if(preg_match('/(localhost|127\.0\.0\.1)/', $_SERVER['HTTP_HOST']) || $_SERVER['SERVER_ADDR'] == '127.0.0.1'){
					return NULL; // prevent problems on local setups
				}

				return preg_replace('/^www./i', NULL, $_SERVER['HTTP_HOST']);

			}

			return NULL;

		}

		public static function open() {
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
			return Symphony::Database()->fetchVar(
				'session_data', 0,
				sprintf(
					"SELECT `session_data` FROM `tbl_sessions` WHERE `session` = '%s' LIMIT 1",
					Symphony::Database()->cleanValue($id)
				)
			);
		}

		public static function write($id, $data) {
			if(strlen(trim($data)) == 0) return;

			$fields = array(
				'session' => $id,
				'session_expires' => time(),
				'session_data' => $data
			);
			return Symphony::Database()->insert($fields, 'tbl_sessions', true);
		}

		public static function destroy($id) {
			return Symphony::Database()->query(
				sprintf(
					"DELETE FROM `tbl_sessions` WHERE `session` = '%s'",
					Symphony::Database()->cleanValue($id)
				)
			);
		}

		public static function gc($max) {
			return Symphony::Database()->query(
				sprintf(
					"DELETE FROM `tbl_sessions` WHERE `session_expires` <= '%s' OR `session_data` REGEXP '^([^}]+\\\|a:0:{})+$'",
					Symphony::Database()->cleanValue(time() - $max)
				)
			);
		}
	}

