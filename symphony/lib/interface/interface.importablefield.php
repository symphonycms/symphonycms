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
		 * Prepare a string for a field, oriented towards the human readable.
		 *
		 * @since Symphony 2.3.2
		 * @var integer
		 */
		const STRING_VALUE = 1100;

		/**
		 * Select raw values from a field, usually as an associative array
		 * that `processRawFieldData` would generate.
		 *
		 * @since Symphony 2.3.2
		 * @var integer
		 */
		const ARRAY_VALUE = 1101;

		/**
		 * Return a list of supported import modes for use with `prepareImportValue`.
		 *
		 * @return array
		 */
		public function getImportModes();

		/**
		 * Give the field some data and ask it to return a value.
		 *
		 * @since Symphony 2.3.2 the method signature changed to add `$mode`
		 *  as the second parameter. The default signature restores functionality
		 *  present prior to Symphony 2.3.1
		 *
		 * @param mixed $data
		 *  The data to sanitise/prepare so that it can be added to Symphony.
		 * @param integer $mode
		 *  The `$mode` dictates how this function should return it's value. Prior
		 *  to Symphony 2.3.1, the pseudo implementation of this function returned
		 *  strings, whereas in Symphony 2.3.1 the function returned arrays. `$mode`
		 *  toggles between these two return types (for now)
		 * @param integer $entry_id
		 *  If there is an existing entry that this value is being imported into,
		 *  the `$entry_id` can allow the field to do additional processing/checking
		 * @return array|string
		 *  Dependent on the `$mode`
		 */
		public function prepareImportValue($data, $mode, $entry_id = null);
	}