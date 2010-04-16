<?php

	Class fieldTagList extends Field {
		public function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = __('Tag List');
		}

		public function set($field, $value){
			if($field == 'pre_populate_source' && !is_array($value)) $value = preg_split('/\s*,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
			$this->_fields[$field] = $value;
		}

		public function requiresSQLGrouping() {
			return true;
		}

		public function allowDatasourceParamOutput(){
			return true;
		}

		public function canFilter() {
			return true;
		}

		public function canImport(){
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
					'item', General::sanitize($value), array(
						'handle'	=> $data['handle'][$index]
					)
				));
			}

			$wrapper->appendChild($list);
		}

		function displayDatasourceFilterPanel(&$wrapper, $data=NULL, $errors=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){

			parent::displayDatasourceFilterPanel($wrapper, $data, $errors, $fieldnamePrefix, $fieldnamePostfix);

			if($this->get('pre_populate_source') != NULL) $this->prepopulateSource($wrapper);
		}

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){

			$value = NULL;
			if(isset($data['value'])){
				$value = (is_array($data['value']) ? self::__tagArrayToString($data['value']) : $data['value']);
			}

			$label = Widget::Label($this->get('label'));

			$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (strlen($value) != 0 ? $value : NULL)));

			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);

			if($this->get('pre_populate_source') != NULL) $this->prepopulateSource($wrapper);
		}

		function prepopulateSource(&$wrapper) {

			$existing_tags = $this->findAllTags();

			if(is_array($existing_tags) && !empty($existing_tags)){
				$taglist = Symphony::Parent()->Page->createElement('ul');
				$taglist->setAttribute('class', 'tags');

				foreach($existing_tags as $tag) $taglist->appendChild(Symphony::Parent()->Page->createElement('li', $tag));

				$wrapper->appendChild($taglist);
			}

		}

		function findAllTags(){

			if(!is_array($this->get('pre_populate_source'))) return;

			$values = array();

			foreach($this->get('pre_populate_source') as $item){

				$result = Symphony::Database()->query("
					SELECT
						DISTINCT `value`
					FROM
						`tbl_entries_data_%d`
					ORDER BY
						`value` ASC
					",
					($item == 'existing') ? $this->get('id') : $item
				);

				if(!$result->valid()) continue;

				$values = array_merge($values, $result->resultColumn('value'));
			}

			return array_unique($values);
		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){

			$status = self::__OK__;

			$data = preg_split('/\,\s*/i', $data, -1, PREG_SPLIT_NO_EMPTY);
			$data = array_map('trim', $data);

			if(empty($data)) return;

			// Do a case insensitive removal of duplicates
			$data = General::array_remove_duplicates($data, true);

			sort($data);

			$result = array();
			foreach($data as $value){
				$result['value'][] = $value;
				$result['handle'][] = Lang::createHandle($value);
			}

			return $result;
		}

		static private function __tagArrayToString(array $tags){

			if(empty($tags)) return NULL;

			sort($tags);

			return implode(', ', $tags);

		}

		function prepareTableValue($data, SymphonyDOMElement $link=NULL){

			if(!is_array($data) || empty($data)) return;

			$value = NULL;
			if(isset($data['value'])){
				$value = (is_array($data['value']) ? self::__tagArrayToString($data['value']) : $data['value']);
			}

			return parent::prepareTableValue(array('value' => General::sanitize($value)), $link);
		}

		function commit(){

			if(!parent::commit()) return false;

			$field_id = $this->get('id');
			$handle = $this->handle();

			if($field_id === false) return false;

			$fields = array(
				'field_id' => $field_id,
				'pre_populate_source' => (is_null($this->get('pre_populate_source'))) ? NULL : implode(',', $this->get('pre_populate_source')),
				'validator' => ($fields['validator'] == 'custom' ? NULL : $this->get('validator'))
			);

			Symphony::Database()->delete('tbl_fields_' . $handle, array($field_id), "`field_id` = %d LIMIT 1");
			$field_id = Symphony::Database()->insert('tbl_fields_' . $handle, $fields);

			return ($field_id == 0 || !$field_id) ? false : true;
		}

		function findDefaults(&$fields){
			if(!isset($fields['pre_populate_source'])) $fields['pre_populate_source'] = array('existing');
		}

		function canPrePopulate(){
			return true;
		}

		public function displaySettingsPanel(SymphonyDOMElement &$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			$label = Widget::Label(__('Suggestion List'));

			$suggestion_list_source = $this->get('suggestion-list-source');

			$options = array(
				array('existing', (is_array($suggestion_list_source) && in_array('existing', $suggestion_list_source)), __('Existing Values')),
			);


/*		    $sections = SectionManager::instance()->fetch(NULL, 'ASC', 'name');
			$field_groups = array();

			if(is_array($sections) && !empty($sections))
				foreach($sections as $section) $field_groups[$section->get('id')] = array('fields' => $section->fetchFields(), 'section' => $section);


			foreach($field_groups as $group){

				if(!is_array($group['fields'])) continue;

				$fields = array();
				foreach($group['fields'] as $f){
					if($f->get('id') != $this->get('id') && $f->canPrePopulate()) $fields[] = array($f->get('id'), (in_array($f->get('id'), $this->get('pre_populate_source'))), $f->get('label'));
				}

				if(is_array($fields) && !empty($fields)) $options[] = array('label' => $group['section']->get('name'), 'options' => $fields);
			}
			*/

			$label->appendChild(Widget::Select('suggestion-list-source', $options, array('multiple' => 'multiple')));
			$wrapper->appendChild($label);

			$this->buildValidationSelect($wrapper, $this->get('validator'), 'validator');

			$options_list = Symphony::Parent()->Page->createElement('ul');
			$options_list->setAttribute('class', 'options-list');
			$this->appendShowColumnCheckbox($options_list);
			$wrapper->appendChild($options_list);
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
	}

