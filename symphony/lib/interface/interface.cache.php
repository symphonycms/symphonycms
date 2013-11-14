<?php

	/**
	 * @package interface
	 */
	/**
	 * This interface is to be implemented by Extensions who wish to provide
	 * cacheable objects for Symphony to use.
	 *
	 * @since Symphony 2.3.5
	 */
	Interface iCache {

		/**
		 * Returns the human readable name of this cache type. This is
		 * displayed in the system preferences cache options.
		 *
		 * @return string
		 */
		public static function getName();

		/**
		 * This function returns all the settings of the current Cache
		 * instance.
		 *
		 * @return array
		 *  An associative array of settings for this cache where the
		 *  key is `getClass` and the value is an associative array of settings,
		 *  key being the setting name, value being, the value
		 */
		public function settings();

		public function read($hash);

		public function write($hash, $data, $ttl = null);

		public function delete($hash);

	}