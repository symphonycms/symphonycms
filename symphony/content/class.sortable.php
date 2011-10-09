<?php

	/**
	 * @package content
	 */
	/**
	 * This class handles object is sortable via `$_REQUEST` parameters
	 * @todo Finish this class, document this class and it's methods
	 *
	 * @since Symphony 2.3
	 */

	class Sortable {

		public function __construct(&$data, &$sort, &$order) {
			if (isset($_REQUEST['sort']) && is_numeric($_REQUEST['sort'])) {
				$sort = intval($_REQUEST['sort']);
				$order = ($_REQUEST['order'] == 'desc' ? 'desc' : 'asc');
			}
			else {
				$sort = 0;
				$order = 'desc';
			}
		}

	}
