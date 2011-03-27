<?php

	/**
	 * @package toolkit
	 */
	/**
	 * A simple Date field that stores a full ISO date. Symphony will attempt
	 * to localize the date on a per Author basis. The field essentially maps to
	 * PHP's `strtotime`, so it is very flexible in terms of what an Author can
	 * input into it.
	 */

	Class fieldDate extends Field{

		const SIMPLE = 0;
		const REGEXP = 1;
		const RANGE = 3;
		const ERROR = 4;

		private $key;

		public function __construct(&$parent) {
			parent::__construct($parent);
			$this->_name = __('Date');
			$this->key = 1;

			$this->set('location', 'sidebar');
		}

		public function allowDatasourceOutputGrouping() {
			return true;
		}

		public function allowDatasourceParamOutput() {
			return true;
		}

		public function canPrePopulate() {
			return true;
		}

		public function canFilter() {
			return true;
		}

		public function canImport() {
			return true;
		}

		public function isSortable() {
			return true;
		}

		public function displayPublishPanel(&$wrapper, $data = null, $error = null, $prefix = null, $postfix = null) {
			$name = $this->get('element_name');
			$value = null;

			// New entry
			if(is_null($data) && is_null($error) && $this->get('pre_populate') == 'yes') {
				$value = DateTimeObj::format('now', __SYM_DATETIME_FORMAT__);
			}

			// Error entry, display original data
			else if(!is_null($error)) {
				$value = $_POST['fields'][$name];
			}

			// Empty entry
			else if(isset($data['gmt']) && !is_null($data['gmt'])) {
				$value = DateTimeObj::format($data['gmt'], __SYM_DATETIME_FORMAT__);
			}

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Input("fields{$prefix}[{$name}]", $value));
			$label->setAttribute('class', 'date');

			if(!is_null($error)) {
				$label = Widget::wrapFormElementWithError($label, $error);
			}

			$wrapper->appendChild($label);
		}

		public function checkPostFieldData($data, &$message, $entry_id=NULL) {
			if(empty($data)) return self::__OK__;
			$message = NULL;

			// Handle invalid dates
			if(!DateTimeObj::validate($data)) {
				$message = __("The date specified in '%s' is invalid.", array($this->get('label')));
				return self::__INVALID_FIELDS__;
			}

			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL) {
			$status = self::__OK__;
			$timestamp = null;

			// Prepopulate date
			if(is_null($data) || $data == '') {
				if($this->get('pre_populate') == 'yes') {
					$timestamp = time();
				}
			}

			// Convert given date to timestamp
			else if($status == self::__OK__ && DateTimeObj::validate($data)) {
				$timestamp = DateTimeObj::get('U', $data);
			}

			// Valid date
			if(!is_null($timestamp)) {
				return array(
					'value' => DateTimeObj::get('c', $timestamp),
					'local' => strtotime(DateTimeObj::get('c', $timestamp)),
					'gmt' => strtotime(DateTimeObj::getGMT('c', $timestamp))
				);
			}

			// Invalid date
			else {
				return array(
					'value' => null,
					'local' => null,
					'gmt' => null
				);
			}
		}

		public function appendFormattedElement($wrapper, $data, $encode = false) {
			if(isset($data['gmt']) && !is_null($data['gmt'])) {

				// Get date
				if(is_array($data['local'])) {
					$date = current($data['local']);
				}
				else {
					$date = $data['local'];
				}

				// Append date
				$wrapper->appendChild(General::createXMLDateObject($date, $this->get('element_name')));
			}
		}

		public function prepareTableValue($data, XMLElement $link=NULL) {
			$value = null;

			if(isset($data['gmt']) && !is_null($data['gmt'])) {
				$value = DateTimeObj::format($data['gmt'], __SYM_DATETIME_FORMAT__, true);
			}

			return parent::prepareTableValue(array('value' => $value), $link);
		}

		public function getParameterPoolValue($data) {
			return DateTimeObj::get('Y-m-d H:i:s', $data['local']);
		}

		public function groupRecords($records) {
			if(!is_array($records) || empty($records)) return;

			$groups = array('year' => array());

			foreach($records as $r) {
				$data = $r->getData($this->get('id'));

				$info = getdate($data['local']);

				$year = $info['year'];
				$month = ($info['mon'] < 10 ? '0' . $info['mon'] : $info['mon']);

				if(!isset($groups['year'][$year])) {
					$groups['year'][$year] = array(
						'attr' => array('value' => $year),
						'records' => array(),
						'groups' => array()
					);
				}

				if(!isset($groups['year'][$year]['groups']['month'])) {
					$groups['year'][$year]['groups']['month'] = array();
				}

				if(!isset($groups['year'][$year]['groups']['month'][$month])) {
					$groups['year'][$year]['groups']['month'][$month] = array(
						'attr' => array('value' => $month),
						'records' => array(),
						'groups' => array()
					);
				}

				$groups['year'][$year]['groups']['month'][$month]['records'][] = $r;

			}

			return $groups;
		}

		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC') {
			$joins .= "LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
			$sort = 'ORDER BY ' . (in_array(strtolower($order), array('random', 'rand')) ? 'RAND()' : "`ed`.`gmt` $order");
		}

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation=false) {
			if(self::isFilterRegex($data[0])) return parent::buildDSRetrievalSQL($data, $joins, $where, $andOperation);

			$parsed = array();

			foreach($data as $string) {
				$type = self::parseFilter($string);

				if($type == self::ERROR) return false;

				if(!is_array($parsed[$type])) $parsed[$type] = array();

				$parsed[$type][] = $string;
			}

			foreach($parsed as $type => $value) {

				switch($type) {

					case self::RANGE:
						$this->buildRangeFilterSQL($value, $joins, $where, $andOperation);
						break;

					case self::SIMPLE:
						$this->buildSimpleFilterSQL($value, $joins, $where, $andOperation);
						break;

				}
			}

			return true;
		}

		public function buildRangeFilterSQL($data, &$joins, &$where, $andOperation=false) {
			$field_id = $this->get('id');

			if(empty($data)) return;

			if($andOperation) {
				foreach($data as $date) {
					$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
					$where .= " AND (DATE_FORMAT(`t$field_id".$this->key."`.value, '%Y-%m-%d %H:%i:%s') >= '" . DateTimeObj::get('Y-m-d H:i:s', $date['start']) . "'
								AND DATE_FORMAT(`t$field_id".$this->key."`.value, '%Y-%m-%d %H:%i:%s') <= '" . DateTimeObj::get('Y-m-d H:i:s', $date['end']) . "') ";

					$this->key++;
				}
			}

			else {
				$tmp = array();

				foreach($data as $date) {
					$tmp[] = "(DATE_FORMAT(`t$field_id".$this->key."`.value, '%Y-%m-%d %H:%i:%s') >= '" . DateTimeObj::get('Y-m-d H:i:s', $date['start']) . "'
								AND DATE_FORMAT(`t$field_id".$this->key."`.value, '%Y-%m-%d %H:%i:%s') <= '" . DateTimeObj::get('Y-m-d H:i:s', $date['end']) . "') ";
				}

				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
				$where .= " AND (".implode(' OR ', $tmp).") ";

				$this->key++;
			}
		}

		public static function cleanFilterString($string) {
			$string = trim($string);
			$string = trim($string, '-/');

			return urldecode($string);
		}

		public static function parseFilter(&$string) {
			$string = self::cleanFilterString($string);

			// Look to see if its a shorthand date (year only), and convert to full date
			if(preg_match('/^(1|2)\d{3}$/i', $string)) {
				$string = "$string-01-01 to $string-12-31 23:59:59";
			}

			// Relative check
			elseif(preg_match('/^(equal to or )?(earlier|later) than (.*)$/i', $string, $match)) {
				$string = $match[3];

				// Validate date
				if(!DateTimeObj::validate($string)) return self::ERROR;

				// Date is equal to or earlier/later than
				if($match[1] == "equal to or ") {
					$earlier = DateTimeObj::get('Y-m-d H:i:s', $string);
					$later = DateTimeObj::get('Y-m-d H:i:s', $string);
				}

				// Date is earlier/later than
				else {
					$later = DateTimeObj::get('Y-m-d H:i:s', $string . ' + 1 day');
					$earlier = DateTimeObj::get('Y-m-d H:i:s', $string . ' - 1 day');
				}

				// Check to see if a time has been provided, otherwise default to 23:59:59
				if(preg_match('/00:00:00$/', $earlier)) {
					$earlier = preg_replace('/00:00:00$/', "23:59:59", $earlier);
				}

				// Switch between ealier than and later than logic
				switch($match[2]) {

					case 'later':
						$string = $later . ' to 2038-01-01 23:59:59';
						break;

					case 'earlier':
						$string = '1970-01-01 to ' . $earlier;
						break;
				}
			}

			// Look to see if the give date is a shorthand date (year and month) and convert it to full date
			elseif(preg_match('/^(1|2)\d{3}[-\/]\d{1,2}$/i', $string)) {
				$start = "{$string}-01";

				// Validate
				if(!DateTimeObj::validate($start)) return self::ERROR;

				$string = "{$start} to {$string}-" . DateTimeObj::get('t', $start);
			}

			// Match single dates
			elseif(!preg_match('/\s+to\s+/i', $string)) {

				// Y-m-d format
				if(preg_match('/^(1|2)\d{3}[-\/]\d{1,2}[-\/]\d{1,2}$/i', $string)) {
					$string = "{$string} to {$string} 23:59:59";
				}

				// Different format
				else {

					// Validate
					if(!DateTimeObj::validate($string)) return self::ERROR;

					$date = DateTimeObj::get('Y-m-d', $string);
					$string = $date . ' 00:00:00 to ' . $date . ' 23:59:59';
				}
			}

			// Match date ranges
			elseif(preg_match('/\s+to\s+/i', $string)) {
				if(!$parts = preg_split('/\s+to\s+/', $string, 2, PREG_SPLIT_NO_EMPTY)) return self::ERROR;

				foreach($parts as $i => &$part) {

					// Validate
					if(!DateTimeObj::validate($part)) return self::ERROR;

					$part = DateTimeObj::get('Y-m-d H:i:s', strtotime($part));
				}

				// If no time provided (hence 00:00:00), make it 23:59:59
				// This handles the case where you expect a date to be inclusive
				// when no time is provided, but instead it will default to 00:00:00,
				// which essentially removes the day from the range.
				if(preg_match('/00:00:00$/', $parts[1])) {
					$parts[1] = preg_replace('/00:00:00$/', "23:59:59", $parts[1]);
				}

				$string = "$parts[0] to $parts[1]";
			}

			// Parse the full date range and return an array
			if(!$parts = preg_split('/\s+to\s+/i', $string, 2, PREG_SPLIT_NO_EMPTY)) return self::ERROR;

			$parts = array_map(array('self', 'cleanFilterString'), $parts);

			list($start, $end) = $parts;

			// Validate
			if(!DateTimeObj::validate($start) || !DateTimeObj::validate($end)) return self::ERROR;

			$string = array('start' => $start, 'end' => $end);

			return self::RANGE;
		}

		public function commit() {
			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			$fields['field_id'] = $id;
			$fields['pre_populate'] = ($this->get('pre_populate') ? $this->get('pre_populate') : 'no');

			Symphony::Database()->query("DELETE FROM `tbl_fields_" . $this->handle() . "` WHERE `field_id` = '$id' LIMIT 1");
			Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
		}

		public function findDefaults(&$fields) {
			if(!isset($fields['pre_populate'])) $fields['pre_populate'] = 'yes';
		}

		public function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			$div = new XMLElement('div', NULL, array('class' => 'compact'));
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][pre_populate]', 'yes', 'checkbox');
			if($this->get('pre_populate') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Pre-populate this field with today’s date', array($input->generate())));
			$div->appendChild($label);

			$this->appendShowColumnCheckbox($div);
			$wrapper->appendChild($div);
		}

		public function createTable() {
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `value` varchar(80) default NULL,
				  `local` int(11) default NULL,
				  `gmt` int(11) default NULL,
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `entry_id` (`entry_id`),
				  KEY `value` (`value`)
				) ENGINE=MyISAM;"
			);
		}

		/**
		 * @deprecated This function is never called by Symphony as all filtering is a range
		 * now that time is taken into consideration. This will be removed in the next major version
		 */
		public function buildSimpleFilterSQL($data, &$joins, &$where, $andOperation=false) {
			$field_id = $this->get('id');

			if($andOperation) {
				foreach($data as $date) {
					$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
					$where .= " AND DATE_FORMAT(`t$field_id".$this->key."`.value, '%Y-%m-%d %H:%i:%s') = '".DateTimeObj::get('Y-m-d H:i:s', $date)."' ";

					$this->key++;
				}
			}

			else {
				$tmp = array();
				foreach($data as $date) {
					$tmp[] = DateTimeObj::get('Y-m-d H:i:s', $date);
				}

				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
				$where .= " AND DATE_FORMAT(`t$field_id".$this->key."`.value, '%Y-%m-%d %H:%i:%s') IN ('".implode("', '", $tmp)."') ";
				$this->key++;
			}
		}

	}
