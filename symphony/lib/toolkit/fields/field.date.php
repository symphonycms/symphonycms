<?php

	/**
	 * @package toolkit
	 */

	require_once FACE . '/interface.exportablefield.php';
	require_once FACE . '/interface.importablefield.php';

	/**
	 * A simple Date field that stores a full ISO date. Symphony will attempt
	 * to localize the date on a per Author basis. The field essentially maps to
	 * PHP's `strtotime`, so it is very flexible in terms of what an Author can
	 * input into it.
	 */
	class FieldDate extends Field implements ExportableField, ImportableField {
		const SIMPLE = 0;
		const REGEXP = 1;
		const RANGE = 3;
		const ERROR = 4;

		private $key;

		public function __construct() {
			parent::__construct();
			$this->_name = __('Date');
			$this->_required = true;
			$this->key = 1;

			$this->set('required', 'no');
			$this->set('location', 'sidebar');
		}

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function canFilter() {
			return true;
		}

		public function isSortable() {
			return true;
		}

		public function canPrePopulate() {
			return true;
		}

		public function allowDatasourceOutputGrouping() {
			return true;
		}

		public function allowDatasourceParamOutput() {
			return true;
		}

	/*-------------------------------------------------------------------------
		Setup:
	-------------------------------------------------------------------------*/

		public function createTable() {
			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `value` varchar(80) default NULL,
				  `date` DATETIME default NULL,
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `entry_id` (`entry_id`),
				  KEY `value` (`value`),
				  KEY `date` (`date`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}

	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/

		/**
		 * Given a string, this function builds the range of dates that match it.
		 * The strings should be in ISO8601 style format, or a natural date, such
		 * as 'last week' etc.
		 *
		 * @since Symphony 2.2.2
		 * @param array $string
		 *  The date string to be parsed
		 * @param string $direction
		 *  Either later or earlier, defaults to null.
		 * @param boolean $equal_to
		 *  If the filter is equal_to or not, defaults to false.
		 * @return array
		 *  An associative array containing a date in ISO8601 format (or natural)
		 *  with two keys, start and end.
		 */
		public static function parseDate($string, $direction = null, $equal_to = false) {
			$parts = array(
				'start' => null,
				'end' => null
			);

			// Year
			if(preg_match('/^\d{1,4}$/', $string, $matches)) {
				$year = current($matches);

				$parts['start'] = "$year-01-01 00:00:00";
				$parts['end'] = "$year-12-31 23:59:59";

				$parts = self::isEqualTo($parts, $direction, $equal_to);
			}
			// Year/Month/Day/Time
			else if(preg_match('/^\d{1,4}[-\/]\d{1,2}[-\/]\d{1,2}\s\d{1,2}:\d{2}/', $string, $matches)) {
				// Handles the case of `to` filters
				if($equal_to || is_null($direction)) {
					$parts['start'] = $parts['end'] = DateTimeObj::get('Y-m-d H:i:s', $string);
				}
				else {
					$parts['start'] = DateTimeObj::get('Y-m-d H:i:s', $string . ' - 1 second');
					$parts['end'] = DateTimeObj::get('Y-m-d H:i:s', $string . ' + 1 second');
				}
			}
			// Year/Month/Day
			else if(preg_match('/^\d{1,4}[-\/]\d{1,2}[-\/]\d{1,2}$/', $string, $matches)) {
				$year_month_day = current($matches);

				$parts['start'] = "$year_month_day 00:00:00";
				$parts['end'] = "$year_month_day 23:59:59";

				$parts = self::isEqualTo($parts, $direction, $equal_to);
			}
			// Year/Month
			else if(preg_match('/^\d{1,4}[-\/]\d{1,2}$/', $string, $matches)) {
				$year_month = current($matches);

				$parts['start'] = "$year_month-01 00:00:00";
				$parts['end'] = DateTimeObj::get('Y-m-t', $parts['start']) . " 23:59:59";

				$parts = self::isEqualTo($parts, $direction, $equal_to);
			}
			// Relative date, aka '+ 3 weeks'
			else {
				// Handles the case of `to` filters
				if($equal_to || is_null($direction)) {
					$parts['start'] = $parts['end'] = DateTimeObj::get('Y-m-d H:i:s', $string);
				}
				else {
					$parts['start'] = DateTimeObj::get('Y-m-d H:i:s', $string . ' - 1 second');
					$parts['end'] = DateTimeObj::get('Y-m-d H:i:s', $string . ' + 1 second');
				}
			}

			return $parts;
		}

		/**
		 * Builds the correct date array depending if the filter should include
		 * the filter as well, ie. later than 2011, is effectively the same as
		 * equal to or later than 2012.
		 *
		 * @since Symphony 2.2.2
		 * @param array $parts
		 *  An associative array containing a date in ISO8601 format (or natural)
		 *  with two keys, start and end.
		 * @param string $direction
		 *  Either later or earlier, defaults to null.
		 * @param boolean $equal_to
		 *  If the filter is equal_to or not, defaults to false.
		 * @return array
		 */
		public static function isEqualTo(array $parts, $direction, $equal_to = false) {
			if(!$equal_to) return $parts;

			if($direction == 'later') {
				$parts['end'] = $parts['start'];
			}
			else {
				$parts['start'] = $parts['end'];
			}

			return $parts;
		}

		public static function parseFilter(&$string) {
			$string = self::cleanFilterString($string);

			// Relative check, earlier or later
			if(preg_match('/^(equal to or )?(earlier|later) than (.*)$/i', $string, $match)) {
				$string = $match[3];

				// Validate date
				if(!DateTimeObj::validate($string)) return self::ERROR;

				// Date is equal to or earlier/later than
				// Date is earlier/later than
				$parts = self::parseDate($string, $match[2], $match[1] == "equal to or ");

				$earlier = $parts['start'];
				$later = $parts['end'];

				// Switch between earlier than and later than logic
				// The earlier/later range is defined by MySQL's support. RE: #1560
				// @link http://dev.mysql.com/doc/refman/5.0/en/datetime.html
				switch($match[2]) {
					case 'later':
						$string = $later . ' to 9999-12-31 23:59:59';
						break;

					case 'earlier':
						$string = '1000-01-01 00:00:00 to ' . $earlier;
						break;
				}
			}

			// Look to see if its a shorthand date (year only), and convert to full date
			// Look to see if the give date is a shorthand date (year and month) and convert it to full date
			// Match single dates
			else if(
				preg_match('/^(1|2)\d{3}$/i', $string)
				|| preg_match('/^(1|2)\d{3}[-\/]\d{1,2}$/i', $string)
				|| !preg_match('/\s+to\s+/i', $string)
			) {
				// Validate
				if(!DateTimeObj::validate($string)) return self::ERROR;

				$parts = self::parseDate($string);
				$string = $parts['start'] . ' to ' . $parts['end'];
			}

			// Match date ranges
			elseif(preg_match('/\s+to\s+/i', $string)) {
				if(!$parts = preg_split('/\s+to\s+/', $string, 2, PREG_SPLIT_NO_EMPTY)) return self::ERROR;

				foreach($parts as $i => &$part) {
					// Validate
					if(!DateTimeObj::validate($part)) return self::ERROR;

					$part = self::parseDate($part);
				}

				$string = $parts[0]['start'] . " to " . $parts[1]['end'];
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

		public static function cleanFilterString($string) {
			$string = trim($string, ' -/');

			return urldecode($string);
		}

		public function buildRangeFilterSQL($data, &$joins, &$where, $andOperation=false) {
			$field_id = $this->get('id');

			if(empty($data)) return;

			if($andOperation) {
				foreach($data as $date) {
					$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
					$where .= " AND (`t$field_id".$this->key."`.date >= '" . DateTimeObj::getGMT('Y-m-d H:i:s', $date['start']) . "'
								AND `t$field_id".$this->key."`.date <= '" . DateTimeObj::getGMT('Y-m-d H:i:s', $date['end']) . "') ";

					$this->key++;
				}
			}

			else {
				$tmp = array();

				foreach($data as $date) {
					$tmp[] = "`t$field_id".$this->key."`.date >= '" . DateTimeObj::getGMT('Y-m-d H:i:s', $date['start']) . "'
								AND `t$field_id".$this->key."`.date <= '" . DateTimeObj::getGMT('Y-m-d H:i:s', $date['end']) . "' ";
				}

				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
				$where .= " AND (".implode(' OR ', $tmp).") ";

				$this->key++;
			}
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function findDefaults(array &$settings) {
			if(!isset($settings['pre_populate'])) $settings['pre_populate'] = 'yes';
		}

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			$div = new XMLElement('div', NULL, array('class' => 'two columns'));
			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);
			$wrapper->appendChild($div);

			$div = new XMLElement('div', NULL, array('class' => 'two columns'));

			$label = Widget::Label();
			$label->setAttribute('class', 'column');
			$input = Widget::Input('fields['.$this->get('sortorder').'][pre_populate]', 'yes', 'checkbox');
			if($this->get('pre_populate') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Pre-populate with current date', array($input->generate())));
			$div->appendChild($label);

			$wrapper->appendChild($div);
		}

		public function commit() {
			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			$fields['pre_populate'] = ($this->get('pre_populate') ? $this->get('pre_populate') : 'no');

			return FieldManager::saveSettings($id, $fields);
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null) {
			$name = $this->get('element_name');
			$value = null;

			// New entry
			if((is_null($data) || empty($data)) && is_null($flagWithError) && $this->get('pre_populate') == 'yes') {
				$value = DateTimeObj::format('now', DateTimeObj::getSetting('datetime_format'));
			}

			// Error entry, display original data
			else if(!is_null($flagWithError)) {
				$value = $_POST['fields'][$name];
			}

			// Empty entry
			else if(isset($data['value'])) {
				$value = DateTimeObj::format($data['value'], DateTimeObj::getSetting('datetime_format'));
			}

			$label = Widget::Label($this->get('label'));
			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));
			$label->appendChild(Widget::Input("fields{$fieldnamePrefix}[{$name}]", $value));
			$label->setAttribute('class', 'date');

			if(!is_null($flagWithError)) {
				$label = Widget::Error($label, $flagWithError);
			}

			$wrapper->appendChild($label);
		}

		public function checkPostFieldData($data, &$message, $entry_id=NULL) {
			$message = NULL;

			// If this field is required
			if($this->get('required') == 'yes' && strlen(trim($data)) == 0){
				$message = __('‘%s’ is a required field.', array($this->get('label')));
				return self::__MISSING_FIELDS__;
			}
			else if(empty($data)) {
				return self::__OK__;
			}

			// Handle invalid dates
			if(!DateTimeObj::validate($data)) {
				$message = __('The date specified in ‘%s’ is invalid.', array($this->get('label')));
				return self::__INVALID_FIELDS__;
			}

			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, &$message=null, $simulate = false, $entry_id = null) {
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
					'date' => DateTimeObj::getGMT('Y-m-d H:i:s', $timestamp)
				);
			}

			// Invalid date
			else {
				return array(
					'value' => null,
					'date' => null
				);
			}
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
			if(isset($data['value'])) {

				// Get date
				if(is_array($data['value'])) {
					$date = current($data['value']);
				}
				else {
					$date = $data['value'];
				}

				// Append date
				$wrapper->appendChild(General::createXMLDateObject($date, $this->get('element_name')));
			}
		}

		public function prepareTableValue($data, XMLElement $link=NULL, $entry_id = null) {
			$value = null;

			if(isset($data['value'])) {
				$value = DateTimeObj::format($data['value'], DateTimeObj::getSetting('datetime_format'), true);
			}

			return parent::prepareTableValue(array('value' => $value), $link, $entry_id = null);
		}

		public function getParameterPoolValue(array $data, $entry_id=NULL){
			return DateTimeObj::get('Y-m-d H:i:s', $data['value']);
		}

	/*-------------------------------------------------------------------------
		Import:
	-------------------------------------------------------------------------*/

		public function getImportModes() {
			return array(
				'getValue' =>		ImportableField::STRING_VALUE,
				'getPostdata' =>	ImportableField::ARRAY_VALUE
			);
		}

		public function prepareImportValue($data, $mode, $entry_id = null) {
			$value = $status = $message = null;
			$modes = (object)$this->getImportModes();

			// Prepopulate date:
			if ($data === null || $data === '') {
				if ($this->get('pre_populate') == 'yes') {
					$timestamp = time();
				}
			}

			// DateTime to timestamp:
			else if ($data instanceof DateTime) {
				$timestamp = $data->getTimestamp();
			}

			// Convert given date to timestamp:
			else if (DateTimeObj::validate($data)) {
				$timestamp = DateTimeObj::get('U', $data);
			}

			// Valid date found:
			if (isset($timestamp)) {
				$value = DateTimeObj::get('c', $timestamp);
			}
			
			if($mode === $modes->getValue) {
				return $value;
			}
			else if($mode === $modes->getPostdata) {
				return $this->processRawFieldData($data, $status, $message, true, $entry_id);
			}

			return null;
		}

	/*-------------------------------------------------------------------------
		Export:
	-------------------------------------------------------------------------*/

		/**
		 * Return a list of supported export modes for use with `prepareExportValue`.
		 *
		 * @return array
		 */
		public function getExportModes() {
			return array(
				'getObject' =>		ExportableField::OBJECT,
				'getPostdata' =>	ExportableField::POSTDATA
			);
		}

		/**
		 * Give the field some data and ask it to return a value using one of many
		 * possible modes.
		 *
		 * @param mixed $data
		 * @param integer $mode
		 * @param integer $entry_id
		 * @return DateTime|null
		 */
		public function prepareExportValue($data, $mode, $entry_id = null) {
			$modes = (object)$this->getExportModes();

			if ($mode === $modes->getObject) {
				$timezone = Symphony::Configuration()->get('timezone', 'region');
				$date = new DateTime(
					isset($data['value'])
						? $data['value']
						: 'now'
				);
				$date->setTimezone(new DateTimeZone($timezone));

				return $date;
			}

			else if ($mode === $modes->getPostdata) {
				return isset($data['value'])
					? $data['value']
					: null;
			}

			return null;
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation=false) {
			if(self::isFilterRegex($data[0])) {
				$this->buildRegexSQL($data[0], array('value'), $joins, $where);
			}

			else {
				$parsed = array();

				// For the filter provided, loop over each piece
				foreach($data as $string) {
					$type = self::parseFilter($string);

					if($type == self::ERROR) return false;

					if(!is_array($parsed[$type])) $parsed[$type] = array();

					$parsed[$type][] = $string;
				}

				foreach($parsed as $value) {
					$this->buildRangeFilterSQL($value, $joins, $where, $andOperation);
				}
			}

			return true;
		}

	/*-------------------------------------------------------------------------
		Sorting:
	-------------------------------------------------------------------------*/

		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC') {
			if(in_array(strtolower($order), array('random', 'rand'))) {
				$sort = 'ORDER BY RAND()';
			}
			else {
				$sort = sprintf(
					'ORDER BY (
						SELECT %s
						FROM tbl_entries_data_%d AS `ed`
						WHERE entry_id = e.id
					) %s',
					'`ed`.date',
					$this->get('id'),
					$order
				);
			}
		}

	/*-------------------------------------------------------------------------
		Grouping:
	-------------------------------------------------------------------------*/

		public function groupRecords($records) {
			if(!is_array($records) || empty($records)) return;

			$groups = array('year' => array());

			foreach($records as $r) {
				$data = $r->getData($this->get('id'));

				$timestamp = DateTimeObj::get('U', $data['value']);
				$info = getdate($timestamp);

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

	}
