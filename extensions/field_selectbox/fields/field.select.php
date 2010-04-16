<?php

	Class fieldSelect extends Field {
		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = __('Select Box');

			// Set default
			$this->set('show_column', 'no');
		}

		function canToggle(){
			return ($this->get('allow_multiple_selection') == 'yes' ? false : true);
		}

		function allowDatasourceOutputGrouping(){
			## Grouping follows the same rule as toggling.
			return $this->canToggle();
		}

		function allowDatasourceParamOutput(){
			return true;
		}

		function canFilter(){
			return true;
		}

		public function canImport(){
			return true;
		}

		function canPrePopulate(){
			return true;
		}

		function isSortable(){
			return true;
		}

		public function appendFormattedElement(&$wrapper, $data, $encode = false) {
			if (!is_array($data) or empty($data)) return;

			$list = Symphony::Parent()->Page->createElement($this->get('element_name'));

			if (!is_array($data['handle']) and !is_array($data['value'])) {
				$data = array(
					'handle'	=> array($data['handle']),
					'value'		=> array($data['value'])
				);
			}

			foreach ($data['value'] as $index => $value) {
				$list->appendChild(Symphony::Parent()->Page->createElement(
					'item',
					General::sanitize($value),
					array(
						'handle'	=> $data['handle'][$index]
					)
				));
			}

			$wrapper->appendChild($list);
		}

		function fetchAssociatedEntrySearchValue($data){
			if(!is_array($data)) return $data;

			return $data['value'];
		}

		function fetchAssociatedEntryCount($value){
			$result = Symphony::Database()->query("
				SELECT
					`entry_id`
				FROM
					`tbl_entries_data_%d`
				WHERE
					`value` = '%s
				",
				$this->get('id'),
				$value
			);

			return ($result->valid()) ? $result->current->count : false;
		}

		function fetchAssociatedEntryIDs($value){
			$result = Symphony::Database()->query("
				SELECT
					count(*) AS `count`
				FROM
					`tbl_entries_data_%d`
				WHERE
					`value` = '%s
				",
				$this->get('id'),
				$value
			);

			return ($result->valid()) ? $result->resultColumn('entry_id') : false;
		}

		public function getToggleStates() {
			$values = preg_split('/,\s*/i', $this->get('static_options'), -1, PREG_SPLIT_NO_EMPTY);

			if ($this->get('dynamic_options') != '') $this->findAndAddDynamicOptions($values);

			$values = array_map('trim', $values);
			$states = array();

			foreach ($values as $value) {
				$states[$value] = $value;
			}

			return $states;
		}

		function toggleFieldData($data, $newState){
			$data['value'] = $newState;
			$data['handle'] = Lang::createHandle($newState);
			return $data;
		}

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			$states = $this->getToggleStates();
			natsort($states);

			if(!is_array($data['value'])) $data['value'] = array($data['value']);

			$options = array();

			foreach($states as $handle => $v){
				$options[] = array(General::sanitize($v), in_array($v, $data['value']), General::sanitize($v));
			}

			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options,
				($this->get('allow_multiple_selection') == 'yes') ? array('multiple' => 'multiple') : array()
			));

			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);
		}

		function displayDatasourceFilterPanel(&$wrapper, $data=NULL, $errors=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){

			parent::displayDatasourceFilterPanel($wrapper, $data, $errors, $fieldnamePrefix, $fieldnamePostfix);

			$data = preg_split('/,\s*/i', $data);
			$data = array_map('trim', $data);

			$existing_options = $this->getToggleStates();

			if(is_array($existing_options) && !empty($existing_options)){
				$optionlist = Symphony::Parent()->Page->createElement('ul');
				$optionlist->setAttribute('class', 'tags');

				foreach($existing_options as $option)
					$optionlist->appendChild(
						Symphony::Parent()->Page->createElement('li', $option)
					);

				$wrapper->appendChild($optionlist);
			}

		}

		function findAndAddDynamicOptions(&$values){

			if(!is_array($values)) $values = array();

			$result = Symphony::Database()->query("
				SELECT
					DISTINCT `value`
				FROM
					`tbl_entries_data_%d`
				ORDER BY
					`value` DESC
				",
				$this->get('dynamic_options')
			);

			if($result->valid()) $values = array_merge($values, $result->resultColumn('value'));
		}

		function prepareTableValue($data, SymphonyDOMElement $link=NULL){
			$value = $data['value'];

			if(!is_array($value)) $value = array($value);

			return parent::prepareTableValue(array('value' => @implode(', ', $value)), $link);
		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){

			$status = self::__OK__;

			if(!is_array($data)) return array('value' => $data, 'handle' => Lang::createHandle($data));

			if(empty($data)) return NULL;

			$result = array('value' => array(), 'handle' => array());

			foreach($data as $value){
				$result['value'][] = $value;
				$result['handle'][] = Lang::createHandle($value);
			}

			return $result;
		}

		public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');

			if (self::isFilterRegex($data[0])) {
				$this->_key++;
				$pattern = str_replace('regexp:', '', $this->escape($data[0]));
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND (
						t{$field_id}_{$this->_key}.value REGEXP '{$pattern}'
						OR t{$field_id}_{$this->_key}.handle REGEXP '{$pattern}'
					)
				";

			} elseif ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->escape($value);
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

			} else {
				if (!is_array($data)) $data = array($data);

				foreach ($data as &$value) {
					$value = $this->escape($value);
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

		function commit(){

			if(!parent::commit()) return false;

			$field_id = $this->get('id');
			$handle = $this->handle();

			if($field_id === false) return false;

			$fields = array(
				'field_id' => $field_id,
				'static_options' => ($this->get('static_options') != '') ? $this->get('static_options') : NULL,
				'dynamic_options' => ($this->get('dynamic_options') != '') ? $this->get('dynamic_options') : NULL,
				'allow_multiple_selection' => ($this->get('allow_multiple_selection') ? $this->get('allow_multiple_selection') : 'no')
			);

			Symphony::Database()->delete('tbl_fields_' . $handle, array($field_id), "`field_id` = %d LIMIT 1");
			$f_id = Symphony::Database()->insert('tbl_fields_' . $handle, $fields);

			if ($f_id == 0 || !$f_id) return false;

			$this->removeSectionAssociation($field_id);
			$this->createSectionAssociation(NULL, $field_id, $this->get('dynamic_options'));

			return true;

		}

		function checkFields(&$errors, $checkForDuplicates=true){

			if(!is_array($errors)) $errors = array();

			if($this->get('static_options') == '' && ($this->get('dynamic_options') == '' || $this->get('dynamic_options') == 'none'))
				$errors['dynamic_options'] = __('At least one source must be specified, dynamic or static.');

			parent::checkFields($errors, $checkForDuplicates);

		}

		function findDefaults(&$fields){
			if(!isset($fields['allow_multiple_selection'])) $fields['allow_multiple_selection'] = 'no';
		}

		public function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			//$div = new XMLElement('div', NULL, array('class' => 'group'));

			$label = Widget::Label(__('Static Options'));
			$label->appendChild(Symphony::Parent()->Page->createElement('i', __('Optional')));
			$input = Widget::Input('static_options', General::sanitize($this->get('static_options')));
			$label->appendChild($input);
			$wrapper->appendChild($label);


			$label = Widget::Label(__('Dynamic Options'));

			$options = array(
				array('', false, __('None')),
			);

			//TODO: Integrate into new Section class

		    /*$sections = SectionManager::instance()->fetch(NULL, 'ASC', 'name');
			$field_groups = array();

			if(is_array($sections) && !empty($sections))
				foreach($sections as $section) $field_groups[$section->get('id')] = array('fields' => $section->fetchFields(), 'section' => $section);



			foreach($field_groups as $group){

				if(!is_array($group['fields'])) continue;

				$fields = array();
				foreach($group['fields'] as $f){
					if($f->get('id') != $this->get('id') && $f->canPrePopulate()) $fields[] = array($f->get('id'), ($this->get('dynamic_options') == $f->get('id')), $f->get('label'));
				}

				if(is_array($fields) && !empty($fields)) $options[] = array('label' => $group['section']->get('name'), 'options' => $fields);
			}*/

			$label->appendChild(Widget::Select('dynamic_options', $options));

			if(isset($errors['dynamic_options'])) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $errors['dynamic_options']));
			else $wrapper->appendChild($label);

			$options_list = Symphony::Parent()->Page->createElement('ul');
			$options_list->setAttribute('class', 'options-list');

			## Allow selection of multiple items
			$label = Widget::Label();
			$input = Widget::Input('allow_multiple_selection', 'yes', 'checkbox');
			if($this->get('allow_multiple_selection') == 'yes') $input->setAttribute('checked', 'checked');

			$label->appendChild($input);
			$label->setValue(__('Allow selection of multiple options'));
			$options_list->appendChild($label);

			$this->appendShowColumnCheckbox($options_list);
			$wrapper->appendChild($options_list);

		}

		function groupRecords($records){

			if(!is_array($records) || empty($records)) return;

			$groups = array($this->get('element_name') => array());

			foreach($records as $r){
				$data = $r->getData($this->get('id'));

				$value = $data['value'];
				$handle = Lang::createHandle($value);

				if(!isset($groups[$this->get('element_name')][$handle])){
					$groups[$this->get('element_name')][$handle] = array('attr' => array('handle' => $handle, 'value' => $value),
																		 'records' => array(), 'groups' => array());
				}

				$groups[$this->get('element_name')][$handle]['records'][] = $r;

			}

			return $groups;
		}

		public function createTable(){
			return Symphony::Database()->query(
				sprintf(
					'CREATE TABLE IF NOT EXISTS `tbl_data_%s_%s` (
						`id` int(11) unsigned NOT NULL auto_increment,
						`entry_id` int(11) unsigned NOT NULL,
						`handle` varchar(255) default NULL,
						`value` varchar(255) default NULL,
						PRIMARY KEY  (`id`),
						KEY `entry_id` (`entry_id`),
						KEY `handle` (`handle`),
						KEY `value` (`value`)
					)',
					$this->get('section'),
					$this->get('element_name')
				)
			);
		}

		public function getExampleFormMarkup(){
			$states = $this->getToggleStates();

			$options = array();

			foreach($states as $handle => $v){
				$options[] = array($v, NULL, $v);
			}

			$fieldname = 'fields['.$this->get('element_name').']';
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options,
				($this->get('allow_multiple_selection') == 'yes') ? array('multiple' => 'multiple') : array()
			));

			return $label;
		}

	}

