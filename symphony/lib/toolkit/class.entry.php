<?php
	
	define_safe('__ENTRY_OK__', 0);
	define_safe('__ENTRY_FIELD_ERROR__', 100);
	
	Class Entry{
		
		var $_fields;
		var $_Parent;
		var $_data;
		var $creationDate;
		var $_engine;
		
		function __construct(&$parent){
			$this->_Parent =& $parent;
			$this->_fields = array();
			$this->_data = array();

			if(class_exists('Administration')) $this->_engine = Administration::instance();
			elseif(class_exists('Frontend')) $this->_engine = Frontend::instance();
			else trigger_error(__('No suitable engine object found'), E_USER_ERROR);
			
			$this->creationDate = DateTimeObj::getGMT('c');
		}
		
		function set($field, $value){
			$this->_fields[$field] = $value;
		}

		function get($field=NULL){			
			if($field == NULL) return $this->_fields;		
			return $this->_fields[$field];
		}
		
		public function fetchAllAssociatedEntryCounts($associated_sections=NULL) {
			if(is_null($this->get('section_id'))) return NULL;
			
			if(is_null($associated_sections)) {
				$section = $this->_Parent->sectionManager->fetch($this->get('section_id'));
				$associated_sections = $section->fetchAssociatedSections();
			}

			if(!is_array($associated_sections) || empty($associated_sections)) return NULL;
			
			$counts = array();
			
			foreach($associated_sections as $as){
				
				$field = $this->_Parent->fieldManager->fetch($as['child_section_field_id']);

				$parent_section_field_id = $as['parent_section_field_id'];

				$search_value = NULL;
						
				if(!is_null($parent_section_field_id)){
					$search_value = $field->fetchAssociatedEntrySearchValue(
							$this->getData($as['parent_section_field_id']), 
							$as['parent_section_field_id'],
							$this->get('id')
					);
				}
				
				else{
					$search_value = $this->get('id');	
				} 

				$counts[$as['child_section_id']] = $field->fetchAssociatedEntryCount($search_value);			
									
			}

			return $counts;

		}
		
		function checkPostData($data, &$errors, $ignore_missing_fields=false){
			$errors = NULL;
			$status = __ENTRY_OK__;
			
			$SectionManager = new SectionManager($this->_engine);
			$EntryManager = new EntryManager($this->_engine);

			$section = $SectionManager->fetch($this->get('section_id'));
			$schema = $section->fetchFieldsSchema();

			foreach($schema as $info){
				$result = NULL;

				$field = $EntryManager->fieldManager->fetch($info['id']);

				if($ignore_missing_fields && !isset($data[$field->get('element_name')])) continue;

				if(Field::__OK__ != $field->checkPostFieldData((isset($data[$info['element_name']]) ? $data[$info['element_name']] : NULL), $message, $this->get('id'))){
					$strict = false;
					$status = __ENTRY_FIELD_ERROR__;

					$errors[$info['id']] = $message;
				}

			}
			
			return $status;			
		}
		
		public function assignEntryId() {
			$fields = $this->get();
			$fields['creation_date'] = DateTimeObj::get('Y-m-d H:i:s');
			$fields['creation_date_gmt'] = DateTimeObj::getGMT('Y-m-d H:i:s');
			$fields['author_id'] = is_null($this->get('author_id')) ? '1' : $this->get('author_id'); // Author_id cannot be NULL
			
			Symphony::Database()->insert($fields, 'tbl_entries');
			
			if (!$entry_id = Symphony::Database()->getInsertID()) return null;
			
			$this->set('id', $entry_id);
			
			return $entry_id;
		}
		
		function setDataFromPost($data, &$error, $simulate=false, $ignore_missing_fields=false){

			$error = NULL;
			
			$status = __ENTRY_OK__;
			
			// Entry has no ID, create it:
			if (!$this->get('id') && $simulate == false) {
				$entry_id = $this->assignEntryId();
				
				if (is_null($entry_id)) return __ENTRY_FIELD_ERROR__;
			}			
			
			$SectionManager = new SectionManager($this->_engine);
			$EntryManager = new EntryManager($this->_engine);
			
			$section = $SectionManager->fetch($this->get('section_id'));		
			$schema = $section->fetchFieldsSchema();

			foreach($schema as $info){
				$result = NULL;

				$field = $EntryManager->fieldManager->fetch($info['id']);
				
				if($ignore_missing_fields && !isset($data[$field->get('element_name')])) continue;
				
				$result = $field->processRawFieldData(
					(isset($data[$info['element_name']]) ? $data[$info['element_name']] : NULL), $s, $simulate, $this->get('id')
				);
				
				if($s != Field::__OK__){
					$status = __ENTRY_FIELD_ERROR__;
					$error = array('field_id' => $info['id'], 'message' => $m);
				}

				$this->setData($info['id'], $result);
			}

			// Failed to create entry, cleanup
			if($status != __ENTRY_OK__ and !is_null($entry_id)) {
				Symphony::Database()->delete('tbl_entries', " `id` = '$entry_id' ");
			}			
			
			return $status;
		}
		
		function setData($field_id, $data){
			$this->_data[$field_id] = $data;
		}
		
		function getData($field_id=NULL, $asObject=false){
			if(!$field_id) return $this->_data;
			return ($asObject == true ? (object)$this->_data[$field_id] : $this->_data[$field_id]);
		}
		
		function findDefaultData(){
			
			$SectionManager = new SectionManager($this->_engine);
			
			$section = $SectionManager->fetch($this->get('section_id'));		
			$schema = $section->fetchFields();
			
			foreach($schema as $field){
				if(isset($this->_data[$field->get('field_id')])) continue;
				
				$result = $field->processRawFieldData(NULL, $status, false, $this->get('id'));
				$this->setData($field->get('field_id'), $result);
			}
			
			if(!$this->get('creation_date')) $this->set('creation_date', DateTimeObj::get('c'));
			
			if(!$this->get('creation_date_gmt')) $this->set('creation_date_gmt', DateTimeObj::getGMT('c'));
						
		}
		
		function commit(){
			$this->findDefaultData();
			$EntryManager = new EntryManager($this->_engine);
			return ($this->get('id') ? $EntryManager->edit($this) : $EntryManager->add($this));	
		}
		
	}
	
