<?php

	
	
	require_once(TOOLKIT . '/class.field.php');
	
	Class FieldManager extends Object{

		var $_Parent;
		static $initialiased_fields = array();

		function __construct(&$parent){
			$this->_Parent = &$parent;
		}

	    function __find($type){
		 
		    if(@is_file(TOOLKIT . "/fields/field.$type.php")) return TOOLKIT . '/fields';
			else{	  
				
				$extensionManager = new ExtensionManager($this->_Parent);
				$extensions = $extensionManager->listInstalledHandles();

				if(is_array($extensions) && !empty($extensions)){
					foreach($extensions as $e){
						if(@is_file(EXTENSIONS . "/$e/fields/field.$type.php")) return EXTENSIONS . "/$e/fields";	
					}	
				}		    
	    	}
	    		    
		    return false;
	    }
	            
        function __getClassName($type){
	        return 'field' . $type;
        }
        
        function __getClassPath($type){
	        return $this->__find($type);
        }
        
        function __getDriverPath($type){	        
	        return $this->__getClassPath($type) . "/field.$type.php";
        }
		
		function &create($type=NULL){
	        
	        $classname = $this->__getClassName($type);	        
	        $path = $this->__getDriverPath($type);

	        if(!@is_file($path)){
		        trigger_error(__('Could not find Field <code>%1$s</code> at <code>%2$s</code>. If the Field was provided by an Extension, ensure that it is installed, and enabled.', array($type, $path)), E_USER_ERROR);	
		        return false;
	        }
	        
			if(!@class_exists($classname)) require_once($path);
			
			/*if($type){
				$classname = 'field' . ucfirst(strtolower($type));
				$classpath = TOOLKIT . '/fields/field.' . strtolower($type) . '.php';
				
				if(!is_file($classpath)) return false;

				include_once($classpath);
			}
			
			else $classname = 'field';*/
			
			$obj =& new $classname($this);
			
			if($obj->canShowTableColumn() && !$obj->get('show_column')) $obj->set('show_column', 'yes');
			
			return $obj;
			
			/*if(isset($this->_pool[$classname]) && is_object($this->_pool[$classname])) 
				return $this->_pool[$classname];
			
			$this->_pool[$classname] =& new $classname($this);

			return $this->_pool[$classname];*/
		}
		
		function fetchFieldTypeFromID($id){
			return Symphony::Database()->fetchVar('type', 0, "SELECT `type` FROM `tbl_fields` WHERE `id` = '$id' LIMIT 1");
		}
		
		## section_id allows for disambiguation
		function fetchFieldIDFromElementName($element_name, $section_id=NULL){
			return Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_fields` WHERE `element_name` = '$element_name' ".($section_id ? " AND `parent_section` = '$section_id' " : '')." LIMIT 1");
		}
		
		//function fetchTypeIDFromHandle($handle){
		//	return Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_fields_types` WHERE `handle` = '$handle' LIMIT 1");
		//}
		
		function fetchHandleFromElementName($id){
			return $this->_Parent->Database->fetchVar('element_name', 0, "SELECT `element_name` FROM `tbl_fields` WHERE `id` = '$id' LIMIT 1");
		}
		
		function fetchTypes(){
			$structure = General::listStructure(TOOLKIT . '/fields', '/field.[a-z0-9_-]+.php/i', false, 'asc', TOOLKIT . '/fields');
			
			$extensions = $this->_Parent->ExtensionManager->listInstalledHandles();

			if(is_array($extensions) && !empty($extensions)){
				foreach($extensions as $handle){
					if(is_dir(EXTENSIONS . '/' . $handle . '/fields')){
						$tmp = General::listStructure(EXTENSIONS . '/' . $handle . '/fields', '/field.[a-z0-9_-]+.php/i', false, 'asc', EXTENSIONS . '/' . $handle . '/fields');
						if(is_array($tmp['filelist']) && !empty($tmp['filelist'])) $structure['filelist'] = array_merge($structure['filelist'], $tmp['filelist']);
					}
				}
				
				$structure['filelist'] = General::array_remove_duplicates($structure['filelist']);
				
			}
			
			$types = array();
			
			for($ii = 0; $ii < count($structure['filelist']); $ii++)
				$types[] = str_replace(array('field.', '.php'), '', $structure['filelist'][$ii]);
			
			return $types;
		}
		
		function fetch($id=NULL, $section_id=NULL, $order='ASC', $sortfield='sortorder', $type=NULL, $location=NULL, $where=NULL, $restrict=Field::__FIELD_ALL__){

			if($id && is_numeric($id)) $returnSingle = true;
			
			$obj = null;
			
			foreach(self::$initialiased_fields as $initialised_field) {
				if ($initialised_field->get('id') == $id) {
					$obj = $initialised_field;
					$ret[] = $obj;
				}
			}
			
			if ($obj == null) {
				
				$sql = 	
					   "SELECT t1.* "
					 . "FROM tbl_fields as t1 "
					 . "WHERE 1 "
					 . ($type ? " AND t1.`type` = '$type' " : '')
					 . ($location ? " AND t1.`location` = '$location' " : '')
					 . ($section_id ? " AND t1.`parent_section` = '$section_id' " : '')
					 . $where
					 . ($id ? " AND t1.`id` = '$id' LIMIT 1" : " ORDER BY t1.`$sortfield` $order");

				if(!$fields = Symphony::Database()->fetch($sql)) return false;

				$ret = array();

				$total_time = NULL;

				foreach($fields as $f){

					$obj =& $this->create($f['type']);

					$obj->setArray($f);

					$context = Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_fields_".$obj->handle()."` WHERE `field_id` = '".$obj->get('id')."' LIMIT 1");

					unset($context['id']);
					$obj->setArray($context);

					if($restrict == Field::__FIELD_ALL__ 
							|| ($restrict == Field::__TOGGLEABLE_ONLY__ && $obj->canToggle()) 
							|| ($restrict == Field::__UNTOGGLEABLE_ONLY__ && !$obj->canToggle())
							|| ($restrict == Field::__FILTERABLE_ONLY__ && $obj->canFilter())
							|| ($restrict == Field::__UNFILTERABLE_ONLY__ && !$obj->canFilter())
					):	
						$ret[] = $obj;
					endif;

					self::$initialiased_fields[] = $obj;

				}
			}

			return (count($ret) <= 1 && $returnSingle ? $ret[0] : $ret);
		}
		
		function add($fields){
			
			if(!isset($fields['sortorder'])){
		        $next = Symphony::Database()->fetchVar("next", 0, 'SELECT MAX(`sortorder`) + 1 AS `next` FROM tbl_fields LIMIT 1');
				$fields['sortorder'] = ($next ? $next : '1');
			}
			
			if(!Symphony::Database()->insert($fields, 'tbl_fields')) return false;
			$field_id = Symphony::Database()->getInsertID();
	        
			return $field_id;
		}

		function edit($id, $fields){

			## Clean up if we are changing types			
			/*$existing = $this->fetch($id);
			if($fields['type'] != $existing->handle()) {
				Symphony::Database()->query("DELETE FROM `tbl_fields_".$existing->handle()."` WHERE `field_id` = '$id' LIMIT 1");	
			}*/
			
			if(!Symphony::Database()->update($fields, "tbl_fields", " `id` = '$id'")) return false;		

			return true;			
		}
		
		function delete($id){

			$existing = $this->fetch($id);

			Symphony::Database()->delete('tbl_fields', " `id` = '$id'");
			Symphony::Database()->delete('tbl_fields_'.$existing->handle(), " `field_id` = '$id'");
			Symphony::Database()->delete('tbl_sections_association', " `child_section_field_id` = '$id'"); 

			Symphony::Database()->query('DROP TABLE `tbl_entries_data_'.$id.'`');
					
			return true;
		}
	}
