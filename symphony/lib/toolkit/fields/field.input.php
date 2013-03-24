<?php

	/**
	 * @package toolkit
	 */

	require_once TOOLKIT . '/class.xsltprocess.php';
	require_once FACE . '/interface.exportablefield.php';
	require_once FACE . '/interface.importablefield.php';

	/**
	 * A simple Input field that essentially maps to HTML's `<input type='text'/>`.
	 */
	class FieldInput extends Field implements ExportableField, ImportableField {
		public function __construct(){
			parent::__construct();
			$this->_name = __('Text Input');
			$this->_required = true;

			$this->set('required', 'no');
		}

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function canFilter(){
			return true;
		}

		public function canPrePopulate(){
			return true;
		}

		public function isSortable(){
			return true;
		}

		public function allowDatasourceOutputGrouping(){
			return true;
		}

		public function allowDatasourceParamOutput(){
			return true;
		}

	/*-------------------------------------------------------------------------
		Setup:
	-------------------------------------------------------------------------*/

		public function createTable(){
			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `handle` varchar(255) default NULL,
				  `value` varchar(255) default NULL,
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `entry_id` (`entry_id`),
				  KEY `handle` (`handle`),
				  KEY `value` (`value`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}

	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/

		private function __applyValidationRules($data){
			$rule = $this->get('validator');
			return ($rule ? General::validateString($data, $rule) : true);
		}

		private function __replaceAmpersands($value) {
			return preg_replace('/&(?!(#[0-9]+|#x[0-9a-f]+|amp|lt|gt);)/i', '&amp;', trim($value));
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function setFromPOST(array $settings = array()) {
			parent::setFromPOST($settings);
			if($this->get('validator') == '') $this->remove('validator');
		}

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			$this->buildValidationSelect($wrapper, $this->get('validator'), 'fields['.$this->get('sortorder').'][validator]');

			$div = new XMLElement('div', NULL, array('class' => 'two columns'));
			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);
			$wrapper->appendChild($div);
		}

		public function commit(){
			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array('validator'=>null);

			$fields['validator'] = ($fields['validator'] == 'custom' ? NULL : $this->get('validator'));

			return FieldManager::saveSettings($id, $fields);
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){
			$value = General::sanitize(isset($data['value']) ? $data['value'] : null);
			$label = Widget::Label($this->get('label'));
			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));
			$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (strlen($value) != 0 ? $value : NULL)));

			if($flagWithError != NULL) $wrapper->appendChild(Widget::Error($label, $flagWithError));
			else $wrapper->appendChild($label);
		}

		public function checkPostFieldData($data, &$message, $entry_id=NULL){
			$message = NULL;

			if(is_array($data) && isset($data['value'])) {
				$data = $data['value'];
			}

			if($this->get('required') == 'yes' && strlen($data) == 0){
				$message = __('‘%s’ is a required field.', array($this->get('label')));
				return self::__MISSING_FIELDS__;
			}

			if(!$this->__applyValidationRules($data)){
				$message = __('‘%s’ contains invalid data. Please check the contents.', array($this->get('label')));
				return self::__INVALID_FIELDS__;
			}

			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, &$message=null, $simulate = false, $entry_id = null) {
			$status = self::__OK__;

			if (strlen(trim($data)) == 0) return array();

			$result = array(
				'value' => $data
			);

			$result['handle'] = Lang::createHandle($result['value']);

			return $result;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null){
			$value = $data['value'];

			if($encode === true){
				$value = General::sanitize($value);
			}

			else{
				include_once(TOOLKIT . '/class.xsltprocess.php');

				if(!General::validateXML($data['value'], $errors, false, new XsltProcess)){
					$value = html_entity_decode($data['value'], ENT_QUOTES, 'UTF-8');
					$value = $this->__replaceAmpersands($value);

					if(!General::validateXML($value, $errors, false, new XsltProcess)){
						$value = General::sanitize($data['value']);
					}
				}
			}

			$wrapper->appendChild(
				new XMLElement(
					$this->get('element_name'), $value, array('handle' => $data['handle'])
				)
			);
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
			$message = $status = null;
			$modes = (object)$this->getImportModes();

			if($mode === $modes->getValue) {
				return $data;
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
				'getHandle' =>		ExportableField::HANDLE,
				'getUnformatted' =>	ExportableField::UNFORMATTED,
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
		 * @return string|null
		 */
		public function prepareExportValue($data, $mode, $entry_id = null) {
			$modes = (object)$this->getExportModes();

			// Export handles:
			if ($mode === $modes->getHandle) {
				if (isset($data['handle'])) {
					return $data['handle'];
				}

				else if (isset($data['value'])) {
					return General::createHandle($data['value']);
				}
			}

			// Export unformatted:
			else if ($mode === $modes->getUnformatted || $mode === $modes->getPostdata) {
				return isset($data['value'])
					? $data['value']
					: null;
			}

			return null;
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');

			if (self::isFilterRegex($data[0])) {
				$this->buildRegexSQL($data[0], array('value', 'handle'), $joins, $where);
			}
			else if ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->cleanValue($value);
					$joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";
					$where .= "
						AND (
							t{$field_id}_{$this->_key}.value = '{$value}'
							OR t{$field_id}_{$this->_key}.handle = '{$value}'
						)
					";
				}
			}

			else {
				if (!is_array($data)) $data = array($data);

				foreach ($data as &$value) {
					$value = $this->cleanValue($value);
				}

				$this->_key++;
				$data = implode("', '", $data);
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND (
						t{$field_id}_{$this->_key}.value IN ('{$data}')
						OR t{$field_id}_{$this->_key}.handle IN ('{$data}')
					)
				";
			}

			return true;
		}

	/*-------------------------------------------------------------------------
		Sorting:
	-------------------------------------------------------------------------*/

		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
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
					'`ed`.value',
					$this->get('id'),
					$order
				);
			}
		}

	/*-------------------------------------------------------------------------
		Grouping:
	-------------------------------------------------------------------------*/

		public function groupRecords($records){
			if(!is_array($records) || empty($records)) return;

			$groups = array($this->get('element_name') => array());

			foreach($records as $r){
				$data = $r->getData($this->get('id'));
				$value = General::sanitize($data['value']);

				if(!isset($groups[$this->get('element_name')][$data['handle']])){
					$groups[$this->get('element_name')][$data['handle']] = array(
						'attr' => array('handle' => $data['handle'], 'value' => $value),
						'records' => array(),
						'groups' => array()
					);
				}

				$groups[$this->get('element_name')][$data['handle']]['records'][] = $r;
			}

			return $groups;
		}

	}
