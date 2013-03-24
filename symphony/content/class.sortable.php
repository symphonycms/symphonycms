<?php

	/**
	 * @package content
	 */
	/**
	 * This class handles sortable objects via the `$_REQUEST` parameters. `Sortable`
	 * standardizes the ordering parameters, and provides a method, `buildTableHeaders`,
	 * which builds the correct URL's and markup to sort backend objects and update the
	 * Table UI appropriately.
	 *
	 * This class is designed to work in the Symphony backend only, and not on the
	 * Frontend.
	 *
	 * @since Symphony 2.3
	 */

	Class Sortable {

		/**
		 * This method initializes the `$result`, `$sort` and `$order` variables by using the
		 * `$_REQUEST` array. The `$result` is passed by reference, and is return of calling the
		 * `$object->sort()` method. It is this method that actually invokes the sorting inside
		 * the `$object`.
		 *
		 * @param object $object
		 *	The object responsible for sorting the items. It must implement a `sort()` method.
		 * @param array $result
		 *	This variable stores an array sorted objects. Once set, its value is available
		 *	to the client class of Sortable.
		 * @param string $sort
		 *	This variable stores the field (or axis) the objects are sorted by. Once set,
		 *	its value is available to the client class of `Sortable`.
		 * @param string $order
		 *	This variable stores the sort order (i.e. 'asc' or 'desc'). Once set, its value
		 *	is available to the client class of Sortable.
		 * @param array $params (optional)
		 *	An array of parameters that can be passed to the context-based method.
		 */
		public static function initialize($object, &$result, &$sort, &$order, array $params = array()) {
			if(isset($_REQUEST['sort'])){
				$sort = $_REQUEST['sort'];
			}
			else {
				$sort = null;
			}

			if(isset($_REQUEST['order'])){
				$order = ($_REQUEST['order'] == 'desc' ? 'desc' : 'asc');
			}
			else {
				$order = null;
			}

			$result = $object->sort($sort, $order, $params);
		}

		/**
		 * This method builds the markup for sorting-aware table headers. It accepts an
		 * `$columns` array, as well as the current sorting axis `$sort` and the
		 * current sort order, `$order`. If `$extra_url_params` are provided, they are
		 * appended to the redirect string upon clicking on a table header.
		 *
		 *		'label' => 'Column label',
		 *		'sortable' => (true|false),
		 *		'handle' => 'handle for the column (i.e. the field ID), used as value for $sort',
		 *		'attrs' => array(
		 *			'HTML <a> attribute' => 'value',
		 *			[...]
		 *		)
		 *
		 * @param array $columns
		 *	An array of columns that will be converted into table headers.
		 * @param string $sort
		 *	The current field (or axis) the objects are sorted by.
		 * @param string $order
		 *	The current sort order (i.e. 'asc' or 'desc').
		 * @param string $extra_url_params (optional)
		 *	A string of URL parameters that will be appended to the redirect string.
		 * @return array
		 *	An array of table headers that can be directly passed to `Widget::TableHead`.
		 */
		public static function buildTableHeaders($columns, $sort, $order, $extra_url_params = null) {
			$aTableHead = array();

			foreach($columns as $c) {
				if($c['sortable']) {

					$label = new XMLElement('span', $c['label']);
					$label = $label->generate();

					if($c['handle'] == $sort) {
						$link = sprintf(
							'?sort=%s&amp;order=%s%s',
							$c['handle'], ($order == 'desc' ? 'asc' : 'desc'), $extra_url_params
						);
						$th = Widget::Anchor(
							$label, $link,
							__('Sort by %1$s %2$s', array(($order == 'desc' ? __('ascending') : __('descending')), strtolower($c['label']))),
							'active'
						);
					}
					else {
						$link = sprintf(
							'?sort=%s&amp;order=asc%s',
							$c['handle'], $extra_url_params
						);
						$th = Widget::Anchor(
							$label, $link,
							__('Sort by %1$s %2$s', array(__('ascending'), strtolower($c['label'])))
						);
					}

				}
				else {
					$th = $c['label'];
				}

				$aTableHead[] = array($th, 'col', isset($c['attrs']) ? $c['attrs'] : NULL);
			}

			return $aTableHead;
		}

	}
