<?php

	include_once(TOOLKIT . '/class.section.php');

	Class SectionManager{
		
	    var $_Parent;
		var $Database;
	    
        function __construct(&$parent){
			$this->_Parent = $parent;						
	        $this->Database = $this->_Parent->Database;
        }
		
		function &create(){	
			$obj =& new Section($this);
			return $obj;
		}
		
		function fetchIDFromHandle($handle){
			return $this->_Parent->Database->fetchVar('id', 0, "SELECT `id` FROM `tbl_sections` WHERE `handle` = '$handle' LIMIT 1");
		}
		
		function fetch($id=NULL, $order='ASC', $sortfield='name'){
			
			if($id && is_numeric($id)) $returnSingle = true;

			$sql = "SELECT `s`.*, count(`e`.`id`) as `entry_count`
			
					FROM `tbl_sections` AS `s`
					LEFT JOIN `tbl_entries` AS `e` ON `s`.id = `e`.`section_id`
					
					".($id ? " WHERE `s`.`id` = '$id' " : '')."
					GROUP BY `s`.id
					".(!$id ? " ORDER BY `s`.`$sortfield` $order" : '');

			if(!$sections = Symphony::Database()->fetch($sql)) return false;									
												
			$ret = array();

			foreach($sections as $s){
			
				$obj =& $this->create();
			
				foreach($s as $name => $value){
					$obj->set($name, $value);
				}
				
				$ret[] = $obj;
			}
			
			return (count($ret) <= 1 && $returnSingle ? $ret[0] : $ret);
		}
			
		function add($fields){
			
			if(!$this->_Parent->Database->insert($fields, 'tbl_sections')) return false;
			$section_id = $this->_Parent->Database->getInsertID();
					
			return $section_id;
		}

		function edit($id, $fields){
		
			if(!$this->_Parent->Database->update($fields, 'tbl_sections', " `id` = '$id'")) return false;

			return true;			
		}
	
		function delete($section_id){

			$query = "SELECT `id`, `sortorder` FROM tbl_sections WHERE `id` = '$section_id'";
			$details = $this->_Parent->Database->fetchRow(0, $query);

			## Delete all the entries
			include_once(TOOLKIT . '/class.entrymanager.php');
			$entryManager = new EntryManager($this->_Parent);
			$entries = $this->_Parent->Database->fetchCol('id', "SELECT `id` FROM `tbl_entries` WHERE `section_id` = '$section_id'");			
			$entryManager->delete($entries);
			
			## Delete all the fields
			$fieldManager = new FieldManager($this->_Parent);
			$fields = $this->_Parent->Database->fetchCol('id', "SELECT `id` FROM `tbl_fields` WHERE `parent_section` = '$section_id'");				
			
			if(is_array($fields) && !empty($fields)){
				foreach($fields as $field_id) $fieldManager->delete($field_id);
			}
			
			## Delete the section
			$this->_Parent->Database->delete('tbl_sections', " `id` = '$section_id'");

			## Update the sort orders
			$this->_Parent->Database->query("UPDATE tbl_sections SET `sortorder` = (`sortorder` - 1) WHERE `sortorder` > '".$details['sortorder']."'");	
			
			## Delete the section associations
			$this->_Parent->Database->delete('tbl_sections_association', " `parent_section_id` = '$section_id'");				
			
			return true;
		}
	}

