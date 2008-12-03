<?php

	
	
	Class fieldTagList extends Field{
		
		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = 'Tag List';
		}

		function allowDatasourceParamOutput(){
			return true;
		}
		
		function canFilter(){
			return true;
		}
		
		function appendFormattedElement(&$wrapper, $data, $encode=false){
			
			if(empty($data)) return;
			
			if(!is_array($data['value']) && !is_array($data['handle'])) $data = array('handle' => array($data['handle']), 'value' => array($data['value']));
			
			$list = new XMLElement($this->get('element_name'));
			foreach($data['value'] as $value){
				$list->appendChild(new XMLElement('item', General::sanitize($value), array('handle' => Lang::createHandle($value))));
			}
			
			$wrapper->appendChild($list);
		}		

		function displayDatasourceFilterPanel(&$wrapper, $data=NULL, $errors=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			
			parent::displayDatasourceFilterPanel($wrapper, $data, $errors, $fieldnamePrefix, $fieldnamePostfix);
			
			if($this->get('pre_populate_source') != NULL){
				
				$existing_tags = $this->findAllTags();

				if(is_array($existing_tags) && !empty($existing_tags)){
					$taglist = new XMLElement('ul');
					$taglist->setAttribute('class', 'tags');
					
					foreach($existing_tags as $tag) $taglist->appendChild(new XMLElement('li', $tag));
							
					$wrapper->appendChild($taglist);
				}
			}			
		}

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){

			$value = $this->prepareTableValue($data);
			
			$label = Widget::Label($this->get('label'));
			
			$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (strlen($value) != 0 ? $value : NULL)));
		
			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);

			if($this->get('pre_populate_source') != NULL){
				
				$existing_tags = $this->findAllTags();

				if(is_array($existing_tags) && !empty($existing_tags)){
					$taglist = new XMLElement('ul');
					$taglist->setAttribute('class', 'tags');
					
					foreach($existing_tags as $tag) $taglist->appendChild(new XMLElement('li', $tag));
							
					$wrapper->appendChild($taglist);
				}
			}
		}
		
		function findAllTags(){			

			$sql = "SELECT DISTINCT `value` FROM `tbl_entries_data_" . ($this->get('pre_populate_source') == 'existing' ? $this->get('id') : $this->get('pre_populate_source')) . "` 
					ORDER BY `value` ASC";

			return $this->_engine->Database->fetchCol('value', $sql);
		}
		
		function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){
						
			$status = self::__OK__;
			
			$data = preg_split('/\,\s*/i', $data, -1, PREG_SPLIT_NO_EMPTY);
			$data = array_map('trim', $data);
			
			if(empty($data)) return;
			
			$data = General::array_remove_duplicates($data);
			
			sort($data);
			
			$result = array();
			foreach($data as $value){
				$result['value'][] = $value;
				$result['handle'][] = Lang::createHandle($value);
			}

			return $result;
		}
		
		function prepareTableValue($data, XMLElement $link=NULL){
			
			if(!is_array($data) || empty($data)) return;
			
			if(is_array($data['value']) && !empty($data['value'])) sort($data['value']);
			
			$values = (count($data['value']) > 1) ? @implode(', ', $data['value']) : $data['value'];

			return parent::prepareTableValue(array('value' => General::sanitize($values)), $link);
		}
		
		function commit(){

			if(!parent::commit()) return false;
			
			$id = $this->get('id');

			if($id === false) return false;
			
			$fields = array();
			
			$fields['field_id'] = $id;
			$fields['pre_populate_source'] = ($this->get('pre_populate_source') == 'none' ? NULL : $this->get('pre_populate_source'));
			$fields['validator'] = ($fields['validator'] == 'custom' ? NULL : $this->get('validator'));
			
			$this->_engine->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
				
			return $this->_engine->Database->insert($fields, 'tbl_fields_' . $this->handle());
					
		}
		
		function findDefaults(&$fields){
			if(!isset($fields['pre_populate_source'])) $fields['pre_populate_source'] = 'existing';
		}
		
		function canPrePopulate(){
			return true;
		}	
		
		function displaySettingsPanel(&$wrapper, $errors=NULL){
			
			parent::displaySettingsPanel($wrapper, $errors);

			$label = Widget::Label('Suggestion List');
			
			$sectionManager = new SectionManager($this->_engine);
		    $sections = $sectionManager->fetch(NULL, 'ASC', 'name');
			$field_groups = array();

			if(is_array($sections) && !empty($sections))
				foreach($sections as $section) $field_groups[$section->get('id')] = array('fields' => $section->fetchFields(), 'section' => $section);
			
			$options = array(
				array('none', false, 'None'),
				array('existing', ($this->get('pre_populate_source') == 'existing'), 'Existing Values'),
			);
			
			foreach($field_groups as $group){
				
				if(!is_array($group['fields'])) continue;
				
				$fields = array();
				foreach($group['fields'] as $f){
					if($f->get('id') != $this->get('id') && $f->canPrePopulate()) $fields[] = array($f->get('id'), ($this->get('pre_populate_source') == $f->get('id')), $f->get('label'));
				}
				
				if(is_array($fields) && !empty($fields)) $options[] = array('label' => $group['section']->get('name'), 'options' => $fields);
			}
			
			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][pre_populate_source]', $options));
			$wrapper->appendChild($label);
			
			$this->buildValidationSelect($wrapper, $this->get('validator'), 'fields['.$this->get('sortorder').'][validator]');		
			
			$this->appendShowColumnCheckbox($wrapper);
						
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

	}

