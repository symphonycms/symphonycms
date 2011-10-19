<?php

	/**
	 * @package content
	 */
	/**
	 * This class handles sortable objects via `$_REQUEST` parameters.
	 *
	 * @since Symphony 2.3
	 */

	Class Sortable {

		/**
		 * The Constructor initializes the `$sort` and `$order` variables by looking at
		 * `$_REQUEST`. Then, based on the `driver` key of the page callback, it calls
		 * a private, context-based handler method that returns the sorted set of objects.
		 *
		 * @see core.Administration#getPageCallback()
		 * @param string $sort
		 *	This variable stores the field (or axis) the objects are sorted by. Once set,
		 *	its value is available to the client class of Sortable.
		 * @param string $order
		 *	This variable stores the sort order (i.e. 'asc' or 'desc'). Once set, its value
		 *	is available to the client class of Sortable.
		 * @param array $params (optional)
		 *	An array of parameters that can be passed to the context-based method.
		 */
		public static function init($object, &$result, &$sort, &$order, array $params = array()) {
			$sort = (isset($_REQUEST['sort'])) ? $_REQUEST['sort'] : null;
			$order = ($_REQUEST['order'] == 'desc' ? 'desc' : 'asc');

			$result = $object->sort($sort, $order, $params);
		}

		public static function buildTableHeaders($columns, $sort, $order, $extra_url_params = null) {
			$aTableHead = array();

			foreach($columns as $c) {
				if($c['sortable']) {

					if($c['handle'] == $sort) {
						$link = sprintf(
							'?sort=%s&amp;order=%s%s',
							$c['handle'], ($order == 'desc' ? 'asc' : 'desc'), $extra_url_params
						);
						$label = Widget::Anchor(
							$c['label'], $link,
							__('Sort by %1$s %2$s', array(($order == 'desc' ? __('ascending') : __('descending')), strtolower($c['label']))),
							'active'
						);
					}
					else {
						$link = sprintf(
							'?sort=%s&amp;order=asc%s',
							$c['handle'], $extra_url_params
						);
						$label = Widget::Anchor(
							$c['label'], $link,
							__('Sort by %1$s %2$s', array(__('ascending'), strtolower($c['label'])))
						);
					}

				}
				else {
					$label = $c['label'];
				}

				$aTableHead[] = array($label, 'col', $c['attrs']);
			}

			return $aTableHead;
		}

		/**
		 * Handler method for the `publish` driver.
		 *
		 * @param string $sort
		 *	This variable stores the field (or axis) the entries are sorted by. Once set,
		 *	its value is available to the client class of Sortable.
		 * @param string $order
		 *	This variable stores the sort order (i.e. 'asc' or 'desc'). Once set, its value
		 *	is available to the client class of Sortable.
		 * @param array $params (optional)
		 *	An array of parameters that can be passed to the method.
		 * @return array
		 *	An array of sorted entry objects.
		 */


	}
