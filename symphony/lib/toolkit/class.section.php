<?php

	require_once(TOOLKIT . '/class.fieldmanager.php');
	
	Class Section{
		
		var $_data;
		var $_Parent;
		var $_fields;
		var $_fieldManager;
		
		public function __construct(&$parent){
			$this->_Parent = $parent;
			$this->_data = $this->_fields = array();
			
			$this->_fieldManager = new FieldManager($this->_Parent);
		}
		
		public function fetchAssociatedSections(){
			return Symphony::Database()->fetch("SELECT * 
													FROM `tbl_sections_association` AS `sa`, `tbl_sections` AS `s` 
													WHERE `sa`.`parent_section_id` = '".$this->get('id')."' 
													AND `s`.`id` = `sa`.`child_section_id`
													ORDER BY `s`.`sortorder` ASC
													");
													
		}
		
		public function set($field, $value){
			$this->_data[$field] = $value;
		}

		public function get($field=NULL){			
			if($field == NULL) return $this->_data;		
			return $this->_data[$field];
		}
		
		public function addField(){
			$this->_fields[] = new Field($this->_fieldManager);
		}
		
		public function fetchVisibleColumns(){
			return $this->_fieldManager->fetch(NULL, $this->get('id'), 'ASC', 'sortorder', NULL, NULL, " AND t1.show_column = 'yes' ");	
		}
		
		public function fetchFields($type=NULL, $location=NULL){	
			return $this->_fieldManager->fetch(NULL, $this->get('id'), 'ASC', 'sortorder', $type, $location);
		}
		
		public function fetchFilterableFields($location=NULL){
			return $this->_fieldManager->fetch(NULL, $this->get('id'), 'ASC', 'sortorder', NULL, $location, NULL, Field::__FILTERABLE_ONLY__);
		}
				
		public function fetchToggleableFields($location=NULL){
			return $this->_fieldManager->fetch(NULL, $this->get('id'), 'ASC', 'sortorder', NULL, $location, NULL, Field::__TOGGLEABLE_ONLY__);
		}
		
		public function fetchFieldsSchema(){
			return Symphony::Database()->fetch("SELECT `id`, `element_name`, `type`, `location` FROM `tbl_fields` WHERE `parent_section` = '".$this->get('id')."' ORDER BY `sortorder` ASC");
		}		
				
		public function commit(){
			$fields = $this->_data;	
			$retVal = NULL;
			
			if(isset($fields['id'])){
				$id = $fields['id'];
				unset($fields['id']);
				$retVal = $this->_Parent->edit($id, $fields);
				
				if($retVal) $retVal = $id;
				
			}else{
				$retVal = $this->_Parent->add($fields);	
			}	
			
			if(is_numeric($retVal) && $retVal !== false){
				for($ii = 0; $ii < count($this->_fields); $ii++){
					$this->_fields[$ii]->set('parent_section', $retVal);
					$this->_fields[$ii]->commit();
				}
			}
		}
	}
	
