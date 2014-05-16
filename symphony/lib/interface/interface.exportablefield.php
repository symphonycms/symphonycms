<?php

/**
 * @package interface
 */

/**
 * The `ExportableField` interface defines the functions required to implement
 * the field export API proposal.
 *
 * @since Symphony 2.3.1
 * @link https://github.com/symphonycms/symphony-2/issues/1394
 */
interface ExportableField
{
	/**
	 * Select an array of values from a field.
	 */
	const LIST_OF = 1100;

	/**
	 * Select a `boolean` from a field.
	 */
	const BOOLEAN = 1101;

	/**
	 * Select an `object` from a field.
	 */
	const OBJECT = 1102;

	/**
	 * Select an entry ID from a field.
	 */
	const ENTRY = 1103;

	/**
	 * Select an author ID from a field.
	 */
	const AUTHOR = 1104;

	/**
	 * Select handles from a field.
	 */
	const HANDLE = 1105;

	/**
	 * Select raw values from a field, oriented towards the human readable.
	 */
	const VALUE = 1106;

	/**
	 * Select raw values from a field, as expected by `prepareImportValue`.
	 */
	const POSTDATA = 1107;

	/**
	 * Select formatted values from a field.
	 */
	const FORMATTED = 1108;

	/**
	 * Select unformatted values from a field.
	 */
	const UNFORMATTED = 1109;

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
