<?php

	include_once(TOOLKIT . '/class.section.php');

	Class SectionManager extends Manager{
		
	    var $_Parent;
		var $Database;
	    
        public function __construct(&$parent){
			$this->_Parent = $parent;
	        $this->Database = Symphony::Database();
        }
		
		public function &create(){	
			$obj = new Section($this);
			return $obj;
		}
		
		public function fetchIDFromHandle($handle){
			return Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_sections` WHERE `handle` = '$handle' LIMIT 1");
		}
		
		public function fetch($id=NULL, $order='ASC', $sortfield='name'){
			
			if($id && is_numeric($id)) $returnSingle = true;
			
			if(!is_array(self::$_pool)) $this->flush();
	
			if($returnSingle && isset(self::$_pool[$id])){
				return self::$_pool[$id];
			}
			
			$sql = "
					SELECT `s`.*
					FROM `tbl_sections` AS `s`
					" . ($id ? " WHERE `s`.`id` = '$id' " : '') . "
					" . (!$id ? " ORDER BY `s`.`$sortfield` $order" : '');

			if(!$sections = Symphony::Database()->fetch($sql)) return false;									
												
			$ret = array();

			foreach($sections as $s){
				$obj =& $this->create();
			
				foreach($s as $name => $value){
					$obj->set($name, $value);
				}
				
				self::$_pool[$obj->get('id')] = $obj;
				
				$ret[] = $obj;
			}
			
			return (count($ret) <= 1 && $returnSingle ? $ret[0] : $ret);
		}
			
		public function add($fields){
			
			if(!Symphony::Database()->insert($fields, 'tbl_sections')) return false;
			$section_id = Symphony::Database()->getInsertID();
					
			return $section_id;
		}

		public function edit($id, $fields){
		
			if(!Symphony::Database()->update($fields, 'tbl_sections', " `id` = '$id'")) return false;

			return true;			
		}
	
		public function delete($section_id){

			$query = "SELECT `id`, `sortorder` FROM tbl_sections WHERE `id` = '$section_id'";
			$details = Symphony::Database()->fetchRow(0, $query);

			## Delete all the entries
			include_once(TOOLKIT . '/class.entrymanager.php');
			$entryManager = new EntryManager($this->_Parent);
			$entries = Symphony::Database()->fetchCol('id', "SELECT `id` FROM `tbl_entries` WHERE `section_id` = '$section_id'");			
			$entryManager->delete($entries);
			
			## Delete all the fields
			$fieldManager = new FieldManager($this->_Parent);
			$fields = Symphony::Database()->fetchCol('id', "SELECT `id` FROM `tbl_fields` WHERE `parent_section` = '$section_id'");				
			
			if(is_array($fields) && !empty($fields)){
				foreach($fields as $field_id) $fieldManager->delete($field_id);
			}
			
			## Delete the section
			Symphony::Database()->delete('tbl_sections', " `id` = '$section_id'");

			## Update the sort orders
			Symphony::Database()->query("UPDATE tbl_sections SET `sortorder` = (`sortorder` - 1) WHERE `sortorder` > '".$details['sortorder']."'");	
			
			## Delete the section associations
			Symphony::Database()->delete('tbl_sections_association', " `parent_section_id` = '$section_id'");				
			
			return true;
		}
	}

