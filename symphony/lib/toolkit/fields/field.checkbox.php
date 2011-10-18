<?php

	/**
	 * @package toolkit
	 */
	/**
	 * Checkbox field simulates a HTML checkbox field, in that it represents a
	 * simple yes/no field. Optionally a Developer can give a Checkbox field a
	 * long description for the Publish page, as well as referencing the Checkbox
	 * field with it's short name.
	 */
	Class fieldCheckbox extends Field {

		public function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = __('Checkbox');

			$this->set('location', 'sidebar');
		}
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function canToggle(){
			return true;
		}

		public function getToggleStates(){
			return array(
				'yes' => __('Yes'),
				'no' => __('No')
			);
		}

		public function toggleFieldData($data, $newState){
			$data['value'] = $newState;
			return $data;
		}

		public function canFilter(){
			return true;
		}

		public function canImport(){
			return true;
		}

		public function isSortable(){
			return true;
		}

		public function allowDatasourceOutputGrouping(){
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
				  `value` enum('yes','no') NOT NULL default '".($this->get('default_state') == 'on' ? 'yes' : 'no')."',
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `entry_id` (`entry_id`),
				  KEY `value` (`value`)
				) ENGINE=MyISAM;
			");
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function findDefaults(&$fields){
			if(!isset($fields['default_state'])) $fields['default_state'] = 'off';
		}

		public function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			## Long Description
			$label = Widget::Label(__('Long Description'));
			$label->appendChild(new XMLElement('i', __('Optional')));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][description]', $this->get('description')));
			$wrapper->appendChild($label);

			$div = new XMLElement('div', NULL, array('class' => 'compact'));

			## Checkbox Default State
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][default_state]', 'on', 'checkbox');
			if($this->get('default_state') == 'on') $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Checked by default', array($input->generate())));
			$div->appendChild($label);

			$this->appendShowColumnCheckbox($div);
			$wrapper->appendChild($div);
		}

		public function commit(){
			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			$fields['field_id'] = $id;
			$fields['default_state'] = ($this->get('default_state') ? $this->get('default_state') : 'off');
			if(trim($this->get('description')) != '') $fields['description'] = $this->get('description');

			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL, $entry_id = null){

			if(!$data){
				## TODO: Don't rely on $_POST
				if(isset($_POST) && !empty($_POST)) $value = 'no';
				elseif($this->get('default_state') == 'on') $value = 'yes';
				else $value = 'no';
			}

			else $value = ($data['value'] == 'yes' ? 'yes' : 'no');

			$label = Widget::Label();
			$input = Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, 'yes', 'checkbox', ($value == 'yes' ? array('checked' => 'checked') : NULL));

			$label->setValue($input->generate(false) . ' ' . ($this->get('description') != NULL ? $this->get('description') : $this->get('label')));

			$wrapper->appendChild($label);
		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){
			$status = self::__OK__;

			return array(
				'value' => (strtolower($data) == 'yes' || strtolower($data) == 'on' ? 'yes' : 'no')
			);
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function appendFormattedElement(&$wrapper, $data, $encode=false, $mode=NULL, $entry_id=NULL) {
			$value = ($data['value'] == 'yes' ? 'Yes' : 'No');

			$wrapper->appendChild(new XMLElement($this->get('element_name'), ($encode ? General::sanitize($value) : $value)));
		}

		public function prepareTableValue($data, XMLElement $link=NULL, $entry_id = null){
			return ($data['value'] == 'yes' ? __('Yes') : __('No'));
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		public function displayDatasourceFilterPanel(&$wrapper, $data=NULL, $errors=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			parent::displayDatasourceFilterPanel($wrapper, $data, $errors, $fieldnamePrefix, $fieldnamePostfix);

			$existing_options = array('yes', 'no');

			if(is_array($existing_options) && !empty($existing_options)){
				$optionlist = new XMLElement('ul');
				$optionlist->setAttribute('class', 'tags');

				foreach($existing_options as $option) $optionlist->appendChild(new XMLElement('li', $option));

				$wrapper->appendChild($optionlist);
			}
		}

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');
			$default_state = ($this->get('default_state') == "on") ? 'yes' : 'no';

			if ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->cleanValue($value);
					$joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";

					if($default_state == $value) {
						$where .= "
							AND (
								t{$field_id}_{$this->_key}.value = '{$value}'
								OR
								t{$field_id}_{$this->_key}.value IS NULL
							)
						";
					}
					else {
						$where .= "
							AND (t{$field_id}_{$this->_key}.value = '{$value}')
						";
					}
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

				if(strpos($data, $default_state) !== false) {
					$where .= "
				    	AND (
				    		t{$field_id}_{$this->_key}.value IN ('{$data}')
				            OR
				            t{$field_id}_{$this->_key}.value IS NULL
				    	)
				    ";
				}
				else {
					$where .= "
				        AND (t{$field_id}_{$this->_key}.value IN ('{$data}'))
				    ";
				}
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

				$value = $data['value'];

				if(!isset($groups[$this->get('element_name')][$value])){
					$groups[$this->get('element_name')][$value] = array(
						'attr' => array('value' => $value),
						'records' => array(),
						'groups' => array()
					);
				}

				$groups[$this->get('element_name')][$value]['records'][] = $r;
			}

			return $groups;
		}

	/*-------------------------------------------------------------------------
		Events:
	-------------------------------------------------------------------------*/

		public function getExampleFormMarkup(){
			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Input('fields['.$this->get('element_name').']', NULL, 'checkbox', ($this->get('default_state') == 'on' ? array('checked' => 'checked') : NULL)));

			return $label;
		}

	}
