<?php

	Class ResourceManager {

#		public function __construct(&$data, &$sort, &$order) {

#			if (isset($_REQUEST['sort']) && is_numeric($_REQUEST['sort'])) {
#				$sort = intval($_REQUEST['sort']);
#				$order = ($_REQUEST['order'] == 'desc' ? 'desc' : 'asc');
#			}
#			else {
#				$sort = 0;
#				$order = 'desc';
#			}

#			if ($sort == 1)
#				Sorting::sortBySource($order, $data);
#			else if ($sort == 3)
#				Sorting::sortByDate($order, $data);
#			else if ($sort == 4)
#				Sorting::sortByAuthor($order, $data);
#			else
#				Sorting::sortByName($order, $data);
#		}

#		private function prepareArray(&$data) {
#			foreach($data as &$d) {
#				if (!isset($d['source']))
#					$d['source'] = "";
#				if (isset($d['type']))
#					$d['source'] = $d['type'];
#			}
#		}

		public function sortByName($order, &$data = array()) {
			if ($order == 'asc') krsort($data);

			return $data;
		}

		public function sortBySource($order, &$data = array()) {
			$this->prepareArray($data);

			foreach ($data as $key => $about) {
				$source[$key] = $about['source'];
				$label[$key] = $key;
			}

			$sort = ($order == 'desc') ? SORT_DESC : SORT_ASC;

			array_multisort($source, $sort, $label, SORT_ASC, $data);

			return $data;
		}

		public function sortByDate($order, &$data = array()) {
			foreach ($data as $key => $about) {
				$author[$key] = $about['release-date'];
				$label[$key] = $key;
			}

			$sort = ($order == 'desc') ? SORT_DESC : SORT_ASC;

			array_multisort($author, $sort, $label, SORT_ASC, $data);

			return $data;
		}

		public function sortByAuthor($order, &$data = array()) {
			foreach ($data as $key => $about) {
				$author[$key] = $about['author']['name'];
				$label[$key] = $key;
			}

			$sort = ($order == 'desc') ? SORT_DESC : SORT_ASC;

			array_multisort($author, $sort, $label, SORT_ASC, $data);

			return $data;
		}

		private static function getColumnFromType($type) {
			switch($type) {
				case RESOURCE_TYPE_EVENT:
					return 'events';
				case RESOURCE_TYPE_DS:
					return 'data_sources';
			}
		}

		public static function attach($type, $r_handle, $page_id) {
			$results = Symphony::Database()->fetch(sprintf("
					SELECT `%s`
					FROM `tbl_pages`
					WHERE `id` = '%s'
				",
				self::getColumnFromType($type), $page_id
			));

			if (is_array($results) && count($results) == 1) {
				$result = $results[0][$field];

				if (!in_array($r_handle, explode(',', $result))) {

					if (strlen($result) > 0) $result .= ",";
					$result .= $r_handle;

					Symphony::Database()->query(sprintf("
							UPDATE `tbl_pages`
							SET `%s` = '%s'
							WHERE `id` = '%s'
						",
						$col, MySQL::cleanValue($result), $page_id
					));
				}
			}
		}

		public static function detach($type, $r_handle, $page_id) {
			$results = Symphony::Database()->fetch(sprintf("
					SELECT `%s`
					FROM `tbl_pages`
					WHERE `id` = '%s'
				",
				self::getColumnFromType($type), $page_id
			));

			if (is_array($results) && count($results) == 1) {
				$result = $results[0][$field];

				$values = explode(',', $result);
				$idx = array_search($r_handle, $values, false);

				if ($idx !== false) {
					array_splice($values, $idx, 1);
					$result = implode(',', $values);

					Symphony::Database()->query(sprintf("
							UPDATE `tbl_pages`
							SET `%s` = '%s'
							WHERE `id` = '%s'
						",
						$col, MySQL::cleanValue($result), $page_id
					));
				}
			}
		}

	}

	define_safe('RESOURCE_TYPE_EVENT', 20);

	define_safe('RESOURCE_TYPE_DS', 21);
