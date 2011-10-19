<?php

	/**
	 * @package content
	 */
	/**
	 * This class handles sortable objects via `$_REQUEST` parameters.
	 *
	 * @since Symphony 2.3
	 */

	class Sortable {

		/**
		 * A private variable that stores the sorted objects.
		 * @var array
		 */
		private $_result = array();

		/**
		 * The Constructor initializes the `$sort` and `$order` variables by looking at
		 * `$_REQUEST`. Then, based on the `driver` key of the page callback, it calls
		 * a private, context-based helper method that returns the sorted set of objects.
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
		public function __construct(&$sort, &$order, array $params = array()) {
			$sort = (isset($_REQUEST['sort'])) ? $_REQUEST['sort'] : null;
			$order = ($_REQUEST['order'] == 'desc' ? 'desc' : 'asc');

			$callback = Administration::instance()->getPageCallback();
			$function = $callback['driver'];

			if(!method_exists($this, $function)) {
				throw new Exception('Unable to find handler. Please make sure a handler exists for this page.');
			}

			$this->_result = $this->$function($sort, $order, $params);
		}

		/**
		 * This methods returns an array of sorted objects and is the only public method
		 * for this class.
		 *
		 * @return array
		 *	An array of sorted objects.
		 */
		public function sort() {
			return $this->_result;
		}

		/**
		 * Helper method for the `publish` driver.
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
		private function publish(&$sort, &$order, $params) {
			if(is_numeric($_REQUEST['sort'])){
				$section = $params['current-section'];

				if($section->get('entry_order') != $sort || $section->get('entry_order_direction') != $order){
					SectionManager::edit(
						$section->get('id'),
						array('entry_order' => $sort, 'entry_order_direction' => $order)
					);

					redirect(Administration::instance()->getCurrentPageURL() . $params['filters']);
				}
			}
			else if(isset($_REQUEST['unsort'])){
				SectionManager::edit(
					$section->get('id'),
					array('entry_order' => NULL, 'entry_order_direction' => NULL)
				);

				redirect(Administration::instance()->getCurrentPageURL());
			}

			if(is_null(EntryManager::getFetchSorting()->field) && is_null(EntryManager::getFetchSorting()->direction)){
				EntryManager::setFetchSortingDirection('DESC');
			}
		}

		/**
		 * Helper method for the `blueprintsdatasources` driver.
		 *
		 * @param string $sort
		 *	This variable stores the field (or axis) the datasources are sorted by. Once set,
		 *	its value is available to the client class of Sortable.
		 * @param string $order
		 *	This variable stores the sort order (i.e. 'asc' or 'desc'). Once set, its value
		 *	is available to the client class of Sortable.
		 * @param array $params (optional)
		 *	An array of parameters that can be passed to the method.
		 * @return array
		 *	An array of sorted datasource data.
		 */
		private function blueprintsdatasources(&$sort, &$order, $params) {
			if(is_null($sort)) $sort = 'name';

			return ResourceManager::fetch(RESOURCE_TYPE_DS, array(), array(), $sort . ' ' . $order);
		}

		/**
		 * Helper method for the `blueprintsevents` driver.
		 *
		 * @param string $sort
		 *	This variable stores the field (or axis) the events are sorted by. Once set,
		 *	its value is available to the client class of Sortable.
		 * @param string $order
		 *	This variable stores the sort order (i.e. 'asc' or 'desc'). Once set, its value
		 *	is available to the client class of Sortable.
		 * @param array $params (optional)
		 *	An array of parameters that can be passed to the method.
		 * @return array
		 *	An array of sorted event data.
		 */
		private function blueprintsevents(&$sort, &$order, $params) {
			if(is_null($sort)) $sort = 'name';

			return ResourceManager::fetch(RESOURCE_TYPE_EVENT, array(), array(), $sort . ' ' . $order);
		}

	}
