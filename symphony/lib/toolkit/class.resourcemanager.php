<?php

	/**
	 * @package toolkit
	 */
	/**
	 * The ResourcesManager is a class used to collect some methods for both Datasources
	 * and Events.
	 *
	 * @since Symphony 2.3
	 */

	Class ResourceManager {

		private static function getColumnFromType($type) {
			switch($type) {
				case RESOURCE_TYPE_EVENT:
					return 'events';
				case RESOURCE_TYPE_DS:
					return 'data_sources';
			}
		}

		private static function getManagerFromType($type) {
			switch($type) {
				case RESOURCE_TYPE_EVENT:
					return 'EventManager';
				case RESOURCE_TYPE_DS:
					return 'DatasourceManager';
			}
		}

		/**
		 * This function will return an associative array of resource information. The
		 * information returned is defined by the `$select` parameter, which will allow
		 * a developer to restrict what information is returned about the resource.
		 * Optionally, `$where` (not implemented) and `$order_by` parameters allow a developer to
		 * further refine their query.
		 *
		 * @param integer $type
		 *  The type of the resource (needed to retrieve the correct Manager)
		 * @param array $select (optional)
		 *  Accepts an array of keys to return from the manager listAll() method. If omitted,
		 *  all keys will be returned.
		 * @param array $where (optional)
		 *  Not implemented.
		 * @param string $order_by (optional)
		 *  Allows a developer to return the resources in a particular order. The syntax is the
		 *  same as other `fetch` methods. If omitted this will return resources ordered by `name`.
		 * @return array
		 *  An associative array of resource information, formatted in the same way as the resource's
		 *  manager listAll() method.
		 */
		public static function fetch($type, array $select = array(), array $where = array(), $order_by = null) {
			$manager = self::getManagerFromType($type);
			$resources = $manager::listAll();

			// For future reference: we'll need to check if $where is empty too
			if(empty($select) && is_null($order_by)) return $resources;

			if(!is_null($order_by)){

				$order_by = array_map('strtolower', explode(' ', $order_by));
				$order = ($order_by[1] == 'desc') ? SORT_DESC : SORT_ASC;
				$sort = $order_by[0];

				if($sort == 'author'){
					foreach($resources as $key => $about){
						$author[$key] = $about['author']['name'];
						$label[$key] = $key;
					}

					array_multisort($author, $order, $label, SORT_ASC, $resources);
				}
				else if($sort == 'release-date'){
					foreach($resources as $key => $about){
						$author[$key] = $about['release-date'];
						$label[$key] = $key;
					}

					array_multisort($author, $order, $label, SORT_ASC, $resources);
				}
				else if($sort == 'source'){
					foreach($resources as $key => $about){
						$source[$key] = $about['source'];
						$label[$key] = $key;
					}

					array_multisort($source, $order, $label, SORT_ASC, $resources);
				}
				else if($sort == 'name'){
					if($order == SORT_ASC) krsort($resources);
				}

			}

			$data = array();

			foreach($resources as $i => $r) {
				$data[$i] = array();
				foreach($r as $key => $value) {
					// If $select is empty, we assume every field is requested
					if(in_array($key, $select) || empty($select)) $data[$i][$key] = $value;
				}
			}

			return $data;
		}

		/**
		 * Given the type and handle of a resource, return the extension it belongs to.
		 *
		 * @param integer $type
		 *  The type of the resource.
		 * @param string $r_handle
		 *  The handle of the resource.
		 * @return string
		 *  The extension handle.
		 */
		public static function __getExtensionFromHandle($type, $r_handle) {
			$type = str_replace('_', '-', self::getColumnFromType($type));
			$manager = self::getManagerFromType($type);

			preg_match('/extensions\/(.*)\/' . $type . '/', $manager::__getClassPath($r_handle), $data);

			$data = array_splice($data, 1);
			return $data[0];
		}

		/**
		 * Given the resource handle, this function will return an associative array of Page information,
		 * filtered by the pages the resource is attached to.
		 *
		 * @param integer $type
		 *  The type of the resource.
		 * @param string $r_handle
		 *  The handle of the resource.
		 * @return array
		 *  An associative array of Page information, according to the pages the resource is attached to.
		 */
		public static function getAttachedPages($type, $r_handle){
			$col = self::getColumnFromType($type);

			$pages = PageManager::fetch(false, array('id', 'title'), array(sprintf(
				'`%s` = "%s" OR `%s` REGEXP "%s"',
				$col, $r_handle,
				$col, '^' . $r_handle . ',|,' . $r_handle . ',|,' . $r_handle . '$'
			)));

			return (is_null($pages) ? array() : $pages);
		}

		/**
		 * Given a resource and a page, this function attaches that resource to that page.
		 *
		 * @param integer $type
		 *  The type of the resource.
		 * @param string $r_handle
		 *  The handle of the resource.
		 * @param integer $page_id
		 *  The ID of the page.
		 */
		public static function attach($type, $r_handle, $page_id) {
			$col = self::getColumnFromType($type);

			$pages = PageManager::fetch(false, array($col), array(sprintf(
				'`id` = %d', $page_id
			)));

			if (is_array($pages) && count($pages) == 1) {
				$result = $pages[0][$resource];

				if (!in_array($r_handle, explode(',', $result))) {

					if (strlen($result) > 0) $result .= ',';
					$result .= $r_handle;

					Symphony::Database()->update(
						array($col => MySQL::cleanValue($result)),
						'tbl_pages', 
						sprintf('`id` = %d', $page_id)
					);
				}
			}
		}

		/**
		 * Given a resource and a page, this function detaches that resource from that page.
		 *
		 * @param integer $type
		 *  The type of the resource.
		 * @param string $r_handle
		 *  The handle of the resource.
		 * @param integer $page_id
		 *  The ID of the page.
		 */
		public static function detach($type, $r_handle, $page_id) {
			$col = self::getColumnFromType($type);

			$pages = PageManager::fetch(false, array($col), array(sprintf(
				'`id` = %d', $page_id
			)));

			if (is_array($pages) && count($pages) == 1) {
				$result = $pages[0][$resource];

				$values = explode(',', $result);
				$idx = array_search($r_handle, $values, false);

				if ($idx !== false) {
					array_splice($values, $idx, 1);
					$result = implode(',', $values);

					Symphony::Database()->update(
						array($col => MySQL::cleanValue($result)),
						'tbl_pages', 
						sprintf('`id` = %d', $page_id)
					);
				}
			}
		}

	}

	/**
	 * The integer value for event-type resources.
	 * @var integer
	 */
	define_safe('RESOURCE_TYPE_EVENT', 20);

	/**
	 * The integer value for datasource-type resources.
	 * @var integer
	 */
	define_safe('RESOURCE_TYPE_DS', 21);
