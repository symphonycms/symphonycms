<?php

	/**
	 * @package interface
	 */

	/**
	 * The `ImportableField` interface defines the functions required to implement
	 * the field import API.
	 *
	 * @since Symphony 2.3.1
	 * @link https://github.com/symphonycms/symphony-2/issues/1394
	 */
	interface ImportableField {
		/**
		 * Give the field some data and ask it to return a value.
		 *
		 * @param mixed $data
		 * @param integer $entry_id
		 * @return array|null
		 */
		public function prepareImportValue($data, $entry_id = null);
	}