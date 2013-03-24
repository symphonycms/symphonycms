<?php

	/**
	 * @package toolkit
	 */

	require_once FACE . '/interface.exportablefield.php';
	require_once FACE . '/interface.importablefield.php';

	/**
	 * A simple Select field that essentially maps to HTML's `<select/>`. The
	 * options for this field can be static, or feed from another field.
	 */
	class FieldSelect extends Field implements ExportableField, ImportableField {
		public function __construct(){
			parent::__construct();
			$this->_name = __('Select Box');
			$this->_required = true;
			$this->_showassociation = true;

			// Set default
			$this->set('show_column', 'yes');
			$this->set('location', 'sidebar');
			$this->set('required', 'no');
		}

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function canToggle(){
			return ($this->get('allow_multiple_selection') == 'yes' ? false : true);
		}

		public function getToggleStates() {
			$values = preg_split('/,\s*/i', $this->get('static_options'), -1, PREG_SPLIT_NO_EMPTY);

			if ($this->get('dynamic_options') != '') $this->findAndAddDynamicOptions($values);

			$values = array_map('trim', $values);
			$states = array_combine($values, $values);

			if($this->get('sort_options') == 'yes') {
				natsort($states);
			}

			return $states;
		}

		public function toggleFieldData(array $data, $newState, $entry_id=null){
			$data['value'] = $newState;
			$data['handle'] = Lang::createHandle($newState);
			return $data;
		}

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
			// Grouping follows the same rule as toggling.
			return $this->canToggle();
		}

		public function allowDatasourceParamOutput(){
			return true;
		}

		public function requiresSQLGrouping(){
			// SQL grouping follows the opposite rule as toggling.
			return !$this->canToggle();
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
				  KEY `entry_id` (`entry_id`),
				  KEY `handle` (`handle`),
				  KEY `value` (`value`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}

	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/

		public function fetchAssociatedEntryCount($value){
			return Symphony::Database()->fetchVar('count', 0, "SELECT count(*) AS `count` FROM `tbl_entries_data_".$this->get('id')."` WHERE `value` = '".Symphony::Database()->cleanValue($value)."'");
		}

		public function fetchAssociatedEntryIDs($value){
			return Symphony::Database()->fetchCol('entry_id', "SELECT `entry_id` FROM `tbl_entries_data_".$this->get('id')."` WHERE `value` = '".Symphony::Database()->cleanValue($value)."'");
		}

		public function fetchAssociatedEntrySearchValue($data, $field_id = null, $parent_entry_id = null){
			if(!is_array($data)) return $data;

			return $data['value'];
		}

		public function findAndAddDynamicOptions(&$values){
			if(!is_array($values)) $values = array();

			$results = false;

			// Ensure that the table has a 'value' column
			if((boolean)Symphony::Database()->fetchVar('Field', 0, sprintf("
					SHOW COLUMNS FROM `tbl_entries_data_%d` LIKE '%s'
				", $this->get('dynamic_options'), 'value'
			))) {
				$results = Symphony::Database()->fetchCol('value', sprintf("
						SELECT DISTINCT `value`
						FROM `tbl_entries_data_%d`
						ORDER BY `value` ASC
					", $this->get('dynamic_options')
				));
			}

			// In the case of a Upload field, use 'file' instead of 'value'
			if(($results == false) && (boolean)Symphony::Database()->fetchVar('Field', 0, sprintf("
					SHOW COLUMNS FROM `tbl_entries_data_%d` LIKE '%s'
				", $this->get('dynamic_options'), 'file'
			))) {
				$results = Symphony::Database()->fetchCol('file', sprintf("
						SELECT DISTINCT `file`
						FROM `tbl_entries_data_%d`
						ORDER BY `file` ASC
					", $this->get('dynamic_options')
				));
			}

			if($results) {
				if($this->get('sort_options') == 'no') {
					natsort($results);
				}

				$values = array_merge($values, $results);
			}
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function findDefaults(array &$settings){
			if(!isset($settings['allow_multiple_selection'])) $settings['allow_multiple_selection'] = 'no';
			if(!isset($settings['show_association'])) $settings['show_association'] = 'no';
			if(!isset($settings['sort_options'])) $settings['sort_options'] = 'no';
		}

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			$div = new XMLElement('div', NULL, array('class' => 'two columns'));

			// Predefined Values
			$label = Widget::Label(__('Predefined Values'));
			$label->setAttribute('class', 'column');
			$label->appendChild(new XMLElement('i', __('Optional')));
			$input = Widget::Input('fields['.$this->get('sortorder').'][static_options]', General::sanitize($this->get('static_options')));
			$label->appendChild($input);
			$div->appendChild($label);

			// Dynamic Values
			$label = Widget::Label(__('Dynamic Values'));
			$label->setAttribute('class', 'column');
			$label->appendChild(new XMLElement('i', __('Optional')));

			$sections = SectionManager::fetch(NULL, 'ASC', 'name');
			$field_groups = array();

			if(is_array($sections) && !empty($sections))
				foreach($sections as $section) $field_groups[$section->get('id')] = array('fields' => $section->fetchFields(), 'section' => $section);

			$options = array(
				array('', false, __('None')),
			);

			foreach($field_groups as $group){
				if(!is_array($group['fields'])) continue;

				$fields = array();
				foreach($group['fields'] as $f){
					if($f->get('id') != $this->get('id') && $f->canPrePopulate()) $fields[] = array($f->get('id'), ($this->get('dynamic_options') == $f->get('id')), $f->get('label'));
				}

				if(is_array($fields) && !empty($fields)) $options[] = array('label' => $group['section']->get('name'), 'options' => $fields);
			}

			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][dynamic_options]', $options));

			if(isset($errors['dynamic_options'])) $div->appendChild(Widget::Error($label, $errors['dynamic_options']));
			else $div->appendChild($label);

			$wrapper->appendChild($div);

			$div = new XMLElement('div', NULL, array('class' => 'two columns'));

			// Allow selection of multiple items
			$label = Widget::Label();
			$label->setAttribute('class', 'column');
			$input = Widget::Input('fields['.$this->get('sortorder').'][allow_multiple_selection]', 'yes', 'checkbox');
			if($this->get('allow_multiple_selection') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Allow selection of multiple options', array($input->generate())));
			$div->appendChild($label);

			$this->appendShowAssociationCheckbox($div, __('available when using Dynamic Values'));

			// Sort options?
			$label = Widget::Label();
			$label->setAttribute('class', 'column');
			$input = Widget::Input('fields['.$this->get('sortorder').'][sort_options]', 'yes', 'checkbox');
			if($this->get('sort_options') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Sort all options alphabetically', array($input->generate())));
			$div->appendChild($label);
			$wrapper->appendChild($div);

			$div = new XMLElement('div', NULL, array('class' => 'two columns'));
			$this->appendShowColumnCheckbox($div);
			$this->appendRequiredCheckbox($div);
			$wrapper->appendChild($div);
		}

		public function checkFields(array &$errors, $checkForDuplicates = true){
			if(!is_array($errors)) $errors = array();

			if($this->get('static_options') == '' && ($this->get('dynamic_options') == '' || $this->get('dynamic_options') == 'none'))
				$errors['dynamic_options'] = __('At least one source must be specified, dynamic or static.');

			parent::checkFields($errors, $checkForDuplicates);
		}

		public function commit(){
			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			if($this->get('static_options') != '') $fields['static_options'] = $this->get('static_options');
			if($this->get('dynamic_options') != '') $fields['dynamic_options'] = $this->get('dynamic_options');
			$fields['allow_multiple_selection'] = ($this->get('allow_multiple_selection') ? $this->get('allow_multiple_selection') : 'no');
			$fields['sort_options'] = $this->get('sort_options') == 'yes' ? 'yes' : 'no';
			$fields['show_association'] = $this->get('show_association') == 'yes' ? 'yes' : 'no';

			if(!FieldManager::saveSettings($id, $fields)) { return false; }

			$this->removeSectionAssociation($id);

			// Dynamic Options isn't an array like in Select Box Link
			$field_id = $this->get('dynamic_options');

			if (!is_null($field_id)) {
				$this->createSectionAssociation(NULL, $id, $field_id, $this->get('show_association') == 'yes' ? true : false);
			}

			return true;
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){
			$states = $this->getToggleStates();
			$value = isset($data['value']) ? $data['value'] : null;

			if(!is_array($value)) $value = array($value);

			$options = array(
				array(null, false, null)
			);

			foreach($states as $handle => $v){
				$options[] = array(General::sanitize($v), in_array($v, $value), General::sanitize($v));
			}

			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->get('label'));
			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));
			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple', 'size' => count($options)) : NULL)));

			if($flagWithError != null) $wrapper->appendChild(Widget::Error($label, $flagWithError));
			else $wrapper->appendChild($label);
		}

		public function processRawFieldData($data, &$status, &$message=null, $simulate=false, $entry_id=NULL){
			$status = self::__OK__;

			if(!is_array($data)) {
				return array(
					'value' => $data,
					'handle' => Lang::createHandle($data)
				);
			}

			if(empty($data)) return null;

			$result = array(
				'value' => array(),
				'handle' => array()
			);

			foreach($data as $value){
				$result['value'][] = $value;
				$result['handle'][] = Lang::createHandle($value);
			}

			return $result;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
			if (!is_array($data) or is_null($data['value'])) return;

			$list = new XMLElement($this->get('element_name'));

			if (!is_array($data['handle']) and !is_array($data['value'])) {
				$data = array(
					'handle'	=> array($data['handle']),
					'value'		=> array($data['value'])
				);
			}

			foreach ($data['value'] as $index => $value) {
				$list->appendChild(new XMLElement(
					'item',
					General::sanitize($value),
					array(
						'handle'	=> $data['handle'][$index]
					)
				));
			}

			$wrapper->appendChild($list);
		}

		public function prepareTableValue($data, XMLElement $link=NULL, $entry_id = null){
			$value = $this->prepareExportValue($data, ExportableField::LIST_OF + ExportableField::VALUE, $entry_id);

			return parent::prepareTableValue(array('value' => implode(', ', $value)), $link, $entry_id = null);
		}

		public function getParameterPoolValue(array $data, $entry_id = null) {
			return $this->prepareExportValue($data, ExportableField::LIST_OF + ExportableField::HANDLE, $entry_id);
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

			if(!is_array($data)) {
				$data = array($data);
			}

			if($mode === $modes->getValue) {
				if ($this->get('allow_multiple_selection') === 'no') {
					$data = array(implode('', $data));
				}

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
				'listHandle' =>			ExportableField::LIST_OF
										+ ExportableField::HANDLE,
				'listValue' =>			ExportableField::LIST_OF
										+ ExportableField::VALUE,
				'listHandleToValue' =>	ExportableField::LIST_OF
										+ ExportableField::HANDLE
										+ ExportableField::VALUE,
				'getPostdata' =>		ExportableField::POSTDATA
			);
		}

		/**
		 * Give the field some data and ask it to return a value using one of many
		 * possible modes.
		 *
		 * @param mixed $data
		 * @param integer $mode
		 * @param integer $entry_id
		 * @return array
		 */
		public function prepareExportValue($data, $mode, $entry_id = null) {
			$modes = (object)$this->getExportModes();

			if (isset($data['handle']) && is_array($data['handle']) === false) {
				$data['handle'] = array(
					$data['handle']
				);
			}

			if (isset($data['value']) && is_array($data['value']) === false) {
				$data['value'] = array(
					$data['value']
				);
			}

			// Handle => Value pairs:
			if ($mode === $modes->listHandleToValue) {
				return isset($data['handle'], $data['value'])
					? array_combine($data['handle'], $data['value'])
					: array();
			}

			// Array of handles:
			else if ($mode === $modes->listHandle) {
				return isset($data['handle'])
					? $data['handle']
					: array();
			}

			// Array of values:
			else if ($mode === $modes->listValue || $mode === $modes->getPostdata) {
				return isset($data['value'])
					? $data['value']
					: array();
			}
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		public function displayDatasourceFilterPanel(XMLElement &$wrapper, $data = null, $errors = null, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			parent::displayDatasourceFilterPanel($wrapper, $data, $errors, $fieldnamePrefix, $fieldnamePostfix);

			$data = preg_split('/,\s*/i', $data);
			$data = array_map('trim', $data);

			$existing_options = $this->getToggleStates();

			if(is_array($existing_options) && !empty($existing_options)){
				$optionlist = new XMLElement('ul');
				$optionlist->setAttribute('class', 'tags');

				foreach($existing_options as $option) {
					$optionlist->appendChild(
						new XMLElement('li', General::sanitize($option))
					);
				};

				$wrapper->appendChild($optionlist);
			}
		}

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

	/*-------------------------------------------------------------------------
		Events:
	-------------------------------------------------------------------------*/

		public function getExampleFormMarkup(){
			$states = $this->getToggleStates();

			$options = array();

			foreach($states as $handle => $v){
				$options[] = array($v, NULL, $v);
			}

			$fieldname = 'fields['.$this->get('element_name').']';
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple') : NULL)));

			return $label;
		}

	}
