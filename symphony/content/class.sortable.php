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

		private $_result;

		public function __construct($context, &$sort, &$order) {
			if(!isset($context) || trim($context) == '')
				throw new Exception('Unable to sort data. Please verify the parameters are not empty.');

			$sort = (isset($_REQUEST['sort']) && is_numeric($_REQUEST['sort'])) ? intval($_REQUEST['sort']) : 0;
			$order = ($_REQUEST['order'] == 'desc' ? 'desc' : 'asc');

			$function = str_replace('/', '_', substr($context, 1, strlen($context) - 2));

			if(!method_exists($this, $function)) {
				throw new Exception('Unable to find handler. Please make sure a handler exists for this context.');
			}

			$this->_result = $this->$function($sort, $order);
		}

		public function sort() {
			return $this->_result;
		}

		private function blueprints_datasources($sort, $order) {

			switch($sort){
				case 1:
					$axis = 'source';
					break;
				case 3:
					$axis = 'release-date';
					break;
				case 4:
					$axis = 'author';
					break;
				default:
					$axis = 'name';
					break;
			}

			return DatasourceManager::fetch(array(), array(), $axis . ' ' . $order);
		}

		private function blueprints_events($sort, $order) {

			switch($sort){
				case 1:
					$axis = 'source';
					break;
				case 3:
					$axis = 'release-date';
					break;
				case 4:
					$axis = 'author';
					break;
				default:
					$axis = 'name';
					break;
			}

			return EventManager::fetch(array(), array(), $axis . ' ' . $order);
		}

	}
