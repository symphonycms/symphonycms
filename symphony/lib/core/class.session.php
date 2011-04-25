<?php

	/**
	 * @package core
	 */

	 /**
	  * The Session class is a handler for all Session related logic in PHP. The functions
	  * map directly to all handler functions as defined by session_set_save_handler in
	  * PHP. In Symphony, this function is used in conjunction with the `Cookie` class.
	  * Based on: http://php.net/manual/en/function.session-set-save-handler.php#81761
	  * by klose at openriverbed dot de which was based on
	  * http://php.net/manual/en/function.session-set-save-handler.php#79706 by
	  * maria at junkies dot jp
	  *
	  * @link http://php.net/manual/en/function.session-set-save-handler.php
	  */
	require_once(CORE . '/class.cacheable.php');

	Class Session{

		/**
		 * If a Session has been created, this will be true, otherwise false
		 *
		 * @var boolean
		 */
		private static $_initialized = false;

		/**
		 * False until a shutdown function is registered, true after that
		 *
		 * @var boolean
		 */
		private static $_registered = false;

		/**
		 * An instance of the Cacheable class
		 *
		 * @var Cacheable
		 */
		private static $_cache = null;

		/**
		 * Starts a Session object, only if one doesn't already exist. This function maps
		 * the Session Handler functions to this classes methods by reading the default
		 * information from the PHP ini file.
		 *
		 * @link http://php.net/manual/en/function.session-set-save-handler.php
		 * @link http://php.net/manual/en/function.session-set-cookie-params.php
		 * @param integer $lifetime
		 *  How long a Session is valid for, by default this is 0, which means it
		 *  never expires
		 * @param string $path
		 *  The path the cookie is valid for on the domain
		 * @param string $domain
		 *  The domain this cookie is valid for
		 * @param boolean $httpOnly
		 *  Whether this cookie can be read by Javascript. By default the cookie
		 *  can be read using Javascript and PHP
		 * @return string|boolean
		 *  Returns the Session ID on success, or false on error.
		 */
		public static function start($lifetime = 0, $path = '/', $domain = NULL, $httpOnly = false) {

			if (!self::$_initialized) {

				if(!is_object(Symphony::Database()) || !Symphony::Database()->isConnected()) return false;

				if (session_id() == '') {
					ini_set('session.save_handler', 'user');
					ini_set('session.gc_maxlifetime', $lifetime);
					ini_set('session.gc_probability', '1');
					ini_set('session.gc_divisor', '10');
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

		/**
		 * Returns the current domain for the Session to be saved to, if the installation
		 * is on localhost, this returns null and just allows PHP to take care of setting
		 * the valid domain for the Session, otherwise it will return the non-www version
		 * of the domain host.
		 *
		 * @return string|null
		 *  Null if on localhost, or HTTP_HOST is not set, a string of the domain name sans
		 *  www otherwise
		 */
		public static function getDomain() {
			if(isset($_SERVER['HTTP_HOST'])){

				if(preg_match('/(localhost|127\.0\.0\.1)/', $_SERVER['HTTP_HOST']) || $_SERVER['SERVER_ADDR'] == '127.0.0.1'){
					return null; // prevent problems on local setups
				}

				return preg_replace('/(^www.|:\d+$)/i', NULL, $_SERVER['HTTP_HOST']);

			}

			return null;
		}

		/**
		 * Called when a Session is created, registers the close function
		 *
		 * @return boolean
		 *  Always returns true
		 */
		public static function open() {
			if (!self::$_registered) {
				register_shutdown_function('session_write_close');
				self::$_registered = true;
			}

			return self::$_registered;
		}

		/**
		 * Allows the Session to close without any further logic. Acts as a
		 * destructor function for the Session.
		 *
		 * @return boolean
		 *  Always returns true
		 */
		public static function close() {
			return true;
		}

		/**
		 * Given an ID, and some data, save it into `tbl_sessions`. This uses
		 * the ID as a unique key, and will override any existing data. If the
		 * `$data` is deemed to be empty, no row will be saved in the database
		 * unless there is an existing row.
		 *
		 * @param string $id
		 *  The ID of the Session, usually a hash
		 * @param string $data
		 *  The Session information, usually a serialized object of
		 * `$_SESSION[Cookie->_index]`
		 * @return boolean
		 *  True if the Session information was saved successfully, false otherwise
		 */
		public static function write($id, $data) {
			// Only prevent this record from saving if there isn't already a record
			// in the database. This prevents empty Sessions from being created, but
			// allows them to be nulled.
			if(is_null(Session::read($id))) {
				if(preg_match('/^([^}]+\|a:0:{})+$/i', $data)) return true;
			}

			$fields = array(
				'session' => $id,
				'session_expires' => time(),
				'session_data' => $data
			);
			return Symphony::Database()->insert($fields, 'tbl_sessions', true);
		}

		/**
		 * Given a session's ID, return it's row from `tbl_sessions`
		 *
		 * @param string $id
		 *  The identifier for the Session to fetch
		 * @return string
		 *  The serialised session data
		 */
		public static function read($id) {
			return Symphony::Database()->fetchVar(
				'session_data', 0,
				sprintf(
					"SELECT `session_data` FROM `tbl_sessions` WHERE `session` = '%s' LIMIT 1",
					Symphony::Database()->cleanValue($id)
				)
			);
		}

		/**
		 * Given a session's ID, remove it's row from `tbl_sessions`
		 *
		 * @param string $id
		 *  The identifier for the Session to destroy
		 * @return boolean
		 *  True if the Session was deleted successfully, false otherwise
		 */
		public static function destroy($id) {
			return Symphony::Database()->query(
				sprintf(
					"DELETE FROM `tbl_sessions` WHERE `session` = '%s'",
					Symphony::Database()->cleanValue($id)
				)
			);
		}

		/**
		 * The garbage collector, which removes all empty Sessions, or any
		 * Sessions that have expired. This has a 10% chance of firing based
		 * off the `gc_probability`/`gc_divisor`.
		 *
		 * @param integer $max
		 *  The max session lifetime.
		 * @return boolean
		 *  True on Session deletion, false if an error occurs
		 */
		public static function gc($max) {
			return Symphony::Database()->query(
				sprintf(
					"DELETE FROM `tbl_sessions` WHERE `session_expires` <= '%s'",
					Symphony::Database()->cleanValue(time() - $max)
				)
			);
		}
	}
