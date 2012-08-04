<?php

	/**
	 * @package interface
	 */

	/**
	 * The `ExportableField` interface defines the functions required to implement
	 * the field export API proposal.
	 *
	 * @link https://github.com/symphonycms/symphony-2/issues/1394
	 */
	interface ExportableField {
		/**
		 * Select an array of values from a field.
		 */
		const LIST_OF = 0x1100000;

		/**
		 * Select a `boolean` from a field.
		 */
		const BOOLEAN = 0x2010000;

		/**
		 * Select an `object` from a field.
		 */
		const OBJECT = 0x2020000;

		/**
		 * Select an entry ID from a field.
		 */
		const ENTRY = 0x4010000;

		/**
		 * Select an author ID from a field.
		 */
		const AUTHOR = 0x4040000;

		/**
		 * Select handles from a field.
		 */
		const HANDLE = 0x8010000;

		/**
		 * Select raw values from a field.
		 */
		const VALUE = 0x8020000;

		/**
		 * Select formatted values from a field.
		 */
		const FORMATTED = 0x8040000;

		/**
		 * Select unformatted values from a field.
		 */
		const UNFORMATTED = 0x8080000;

		/**
		 * Return a list of supported export modes for use with `prepareExportValue`.
		 *
		 * @return array
		 */
		public function getExportModes();

		/**
		 * Give the field some data and ask it to return a value using one of many
		 * possible modes.
		 *
		 * @param mixed $data
		 * @param integer $mode
		 * @param integer $entry_id
		 * @return mixed
		 */
		public function prepareExportValue($data, $mode, $entry_id = null);
	}