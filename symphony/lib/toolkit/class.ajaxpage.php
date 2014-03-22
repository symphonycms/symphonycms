<?php
	/**
	 * @package toolkit
	 */
	/**
	 * AjaxPage extends the Page class to provide an object representation
	 * of a Symphony backend AJAX page.
	 *
	 * @deprecated @since Symphony 2.4
	 * @see XMLPage
	 * @see JSONPage
	 */

	require_once(TOOLKIT . '/class.xmlpage.php');

	Abstract Class AjaxPage extends XMLPage {

		/**
		 * All classes that extend the `AJAXPage` class must define a view method
		 * which contains the logic for the content of this page. The resulting HTML
		 * is append to `$this->_Result` where it is generated on build
		 *
		 * @see build()
		 */
		abstract public function view();

	}
