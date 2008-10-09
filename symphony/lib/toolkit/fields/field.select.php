<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	Class fieldSelect extends Field{
		
		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = 'Select Box';
			
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
		
		function canPrePopulate(){
			return true;
		}	

		function isSortable(){
			return true;
		}
		
		function appendFormattedElement(&$wrapper, $data, $encode=false){

			if(!is_array($data) || empty($data)) return;
			
			$list = new XMLElement($this->get('element_name'));
			if(!is_array($data['value'])) $data['value'] = array($data['value']);
			
			foreach($data['value'] as $value){
				$list->appendChild(new XMLElement('item', ($encode ? General::sanitize($value) : $value), array('handle' => $data['handle'])));
			}

			$wrapper->appendChild($list);
		}		
		
		function fetchAssociatedEntrySearchValue($data){
			if(!is_array($data)) return $data;
			
			return $data['value'];
		}
		
		function fetchAssociatedEntryCount($value){
			return $this->_engine->Database->fetchVar('count', 0, "SELECT count(*) AS `count` FROM `tbl_entries_data_".$this->get('id')."` WHERE `value` = '".addslashes($value)."'");
		}
		
		function fetchAssociatedEntryIDs($value){
			return $this->_engine->Database->fetchCol('entry_id', "SELECT `entry_id` FROM `tbl_entries_data_".$this->get('id')."` WHERE `value` = '".addslashes($value)."'");
		}	
			
		function getToggleStates(){
			
			$values = preg_split('/,\s*/i', $this->get('static_options'), -1, PREG_SPLIT_NO_EMPTY);

			if($this->get('dynamic_options') != '') $this->findAndAddDynamicOptions($values);
			
			$values = array_map('trim', $values);
			
			$states = array();
			foreach($values as $value) $states[$value] = $value;
			
			return $states;
		}
		
		function toggleFieldData($data, $newState){
			$data['value'] = General::sanitize($newState);
			$data['handle'] = Lang::createHandle($newState);
			return $data;
		}

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){

			$states = $this->getToggleStates();
			
			if(!is_array($data['value'])) $data['value'] = array($data['value']);
			
			$options = array();

			foreach($states as $handle => $v){
				$options[] = array($v, in_array($v, $data['value']), $v);
			}
			
			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';
			
			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple') : NULL)));
			
			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);		
		}

		function displayDatasourceFilterPanel(&$wrapper, $data=NULL, $errors=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			
			parent::displayDatasourceFilterPanel($wrapper, $data, $errors, $fieldnamePrefix, $fieldnamePostfix);
			
			$data = preg_split('/,\s*/i', $data);
			$data = array_map('trim', $data);
			
			$existing_options = $this->getToggleStates();

			if(is_array($existing_options) && !empty($existing_options)){
				$optionlist = new XMLElement('ul');
				$optionlist->setAttribute('class', 'tags');
				
				foreach($existing_options as $option) $optionlist->appendChild(new XMLElement('li', $option));
						
				$wrapper->appendChild($optionlist);
			}
					
		}

		function findAndAddDynamicOptions(&$values){

			if(!is_array($values)) $values = array();

			$sql = "SELECT DISTINCT `value` FROM `tbl_entries_data_".$this->get('dynamic_options')."`
					ORDER BY `value` DESC";
					
			if($results = $this->Database->fetchCol('value', $sql)) $values = array_merge($values, $results);

		}

		function prepareTableValue($data, XMLElement $link=NULL){
			$value = $data['value'];
			
			if(!is_array($value)) $value = array($value);
			
			return parent::prepareTableValue(array('value' => @implode(', ', $value)), $link);
		}

		function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){
			
			$status = self::__OK__;

			if(!is_array($data)) return array('value' => General::sanitize($data), 'handle' => Lang::createHandle($data));

			if(empty($data)) return NULL;
			
			$result = array('value' => array(), 'handle' => array());

			foreach($data as $value){ 
				$result['value'][] = General::sanitize($value);
				$result['handle'][] = Lang::createHandle($value);
			}
			
			return $result;
		}

		function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation=false){
			
			$field_id = $this->get('id');
			
			if(self::isFilterRegex($data[0])):
				
				$pattern = str_replace('regexp:', '', $data[0]);
				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
				$where .= " AND (`t$field_id`.value REGEXP '$pattern' OR `t$field_id`.handle REGEXP '$pattern') ";

			
			elseif($andOperation):
			
				foreach($data as $key => $bit){
					$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id$key` ON (`e`.`id` = `t$field_id$key`.entry_id) ";
					$where .= " AND (`t$field_id$key`.value = '$bit' OR `t$field_id$key`.handle = '$bit') ";
				}
							
			else:
			
				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
				$where .= " AND (`t$field_id`.value IN ('".@implode("', '", $data)."') OR `t$field_id`.handle IN ('".@implode("', '", $data)."')) ";
						
			endif;
			
			return true;
			
		}
		
		function commit(){
			
			if(!parent::commit()) return false;
			
			$id = $this->get('id');

			if($id === false) return false;
			
			$fields = array();
			
			$fields['field_id'] = $id;
			if($this->get('static_options') != '') $fields['static_options'] = $this->get('static_options');
			if($this->get('dynamic_options') != '') $fields['dynamic_options'] = $this->get('dynamic_options');
			$fields['allow_multiple_selection'] = ($this->get('allow_multiple_selection') ? $this->get('allow_multiple_selection') : 'no');

			$this->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			
			if(!$this->Database->insert($fields, 'tbl_fields_' . $this->handle())) return false;
			
			$this->removeSectionAssociation($id);
			$this->createSectionAssociation(NULL, $id, $this->get('dynamic_options'));
			
			return true;
					
		}
		
		function checkFields(&$errors, $checkForDuplicates=true){
			
			if(!is_array($errors)) $errors = array();

			if($this->get('static_options') == '' && ($this->get('dynamic_options') == '' || $this->get('dynamic_options') == 'none')) 
				$errors['dynamic_options'] = 'At least one source must be specified, dynamic or static.';

			parent::checkFields($errors, $checkForDuplicates);
			
		}
		
		function findDefaults(&$fields){
			if(!isset($fields['allow_multiple_selection'])) $fields['allow_multiple_selection'] = 'no';
		}
				
		function displaySettingsPanel(&$wrapper, $errors=NULL){		
			
			parent::displaySettingsPanel($wrapper, $errors);

			$div = new XMLElement('div', NULL, array('class' => 'group'));
			
			$label = Widget::Label('Static Options <i>Optional</i> ');
			$input = Widget::Input('fields['.$this->get('sortorder').'][static_options]', General::sanitize($this->get('static_options')));
			$label->appendChild($input);
			$div->appendChild($label);
			
			
			$label = Widget::Label('Dynamic Options');
			
			$sectionManager = new SectionManager($this->_engine);
		    $sections = $sectionManager->fetch(NULL, 'ASC', 'name');
			$field_groups = array();
			
			if(is_array($sections) && !empty($sections))
				foreach($sections as $section) $field_groups[$section->get('id')] = array('fields' => $section->fetchFields(), 'section' => $section);
			
			$options = array(
				array('', false, 'None'),
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
			$div->appendChild($label);
						
			if(isset($errors['dynamic_options'])) $wrapper->appendChild(Widget::wrapFormElementWithError($div, $errors['dynamic_options']));
			else $wrapper->appendChild($div);
						
			## Allow selection of multiple items
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][allow_multiple_selection]', 'yes', 'checkbox');
			if($this->get('allow_multiple_selection') == 'yes') $input->setAttribute('checked', 'checked');			
			$label->setValue($input->generate() . ' Allow selection of multiple options');
			$wrapper->appendChild($label);
			
			$this->appendShowColumnCheckbox($wrapper);
						
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
		
		function createTable(){
			
			return $this->_engine->Database->query(
			
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `handle` varchar(255) default NULL,
				  `value` varchar(255) default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `handle` (`handle`),
				  KEY `value` (`value`)
				) TYPE=MyISAM;"
			
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
			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple') : NULL)));
			
			return $label;
		}			

	}

