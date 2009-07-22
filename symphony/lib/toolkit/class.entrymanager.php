<?php

	
	
	include_once(TOOLKIT . '/class.sectionmanager.php');
	
	include_once(TOOLKIT . '/class.authormanager.php');
	include_once(TOOLKIT . '/class.textformattermanager.php');
	include_once(TOOLKIT . '/class.entry.php');
	
	Class EntryManager{
		
		var $_Parent;
		var $formatterManager;
		var $sectionManager;
		var $fieldManager;
		var $_fetchSortField;
		var $_fetchSortDirection;
		
		function __construct(&$parent){
			$this->_Parent =& $parent;
			
			$this->formatterManager = new TextformatterManager($this->_Parent);		
			$this->sectionManager = new SectionManager($this->_Parent);		
			$this->fieldManager = new FieldManager($this->_Parent);
			
			$this->_fetchSortField = NULL;
			$this->_fetchSortDirection = NULL;
			
		}
		
		function create(){	
			$obj = new Entry($this);
			return $obj;
		}
		
		function delete($entries){
			
			if(!is_array($entries))	$entries = array($entries);
	
			foreach($entries as $id){
				$e = $this->fetch($id);
				
				if(!is_object($e[0])) continue;
				
				foreach($e[0]->getData() as $field_id => $data){
					$field = $this->fieldManager->fetch($field_id);
					$field->entryDataCleanup($id, $data);
				}
				
				$section = $this->sectionManager->fetch($e[0]->get('section_id'));
				
				if(!is_object($section)) continue;
				
				$associated_sections = $section->fetchAssociatedSections();
				
				if(is_array($associated_sections) && !empty($associated_sections)){
					foreach($associated_sections as $key => $as){
						
						if($as['cascading_deletion'] != 'yes') continue;
						
						$field = $this->fieldManager->fetch($as['child_section_field_id']);

						$search_value = ($associated_sections[$key]['parent_section_field_id'] ? $field->fetchAssociatedEntrySearchValue($e[0]->getData($as['parent_section_field_id'])) : $e[0]->get('id'));

						$associated_entry_ids = $field->fetchAssociatedEntryIDs($search_value);
						
						if(is_array($associated_entry_ids) && !empty($associated_entry_ids)) $this->delete($associated_entry_ids);
						
					}
				}
			}
			
			$entry_list = @implode("', '", $entries);
			$this->_Parent->Database->delete('tbl_entries', " `id` IN ('$entry_list') ");			
			
			return true;
		}
		
		function add(&$entry){
			
			$fields = $entry->get();
			
			$this->_Parent->Database->insert($fields, 'tbl_entries');
			
			if(!$entry_id = $this->_Parent->Database->getInsertID()) return false;

			foreach($entry->getData() as $field_id => $field){
				if(!is_array($field) || empty($field)) continue;
				
				$this->_Parent->Database->delete('tbl_entries_data_' . $field_id, " `entry_id` = '$entry_id'");
				
				$data = array(
					'entry_id' => $entry_id
				);
				
				$fields = array();
				
				foreach($field as $key => $value){
					
					if(is_array($value)){
						foreach($value as $ii => $v) $fields[$ii][$key] = $v;								
					}
					
					else{
						$fields[max(0, count($fields) - 1)][$key] = $value;
					}
				}
				
				for($ii = 0; $ii < count($fields); $ii++) $fields[$ii] = array_merge($data, $fields[$ii]);

				$this->_Parent->Database->insert($fields, 'tbl_entries_data_' . $field_id);				
				
			}	
			
			$entry->set('id', $entry_id);
			
			return true;	
			
		}
		
		function edit(&$entry){

			/*$fields = array(
				'section_id' => $entry->get('section_id'),
			);
			
			$this->_Parent->Database->update($fields, 'tbl_entries', " `id` = '".$entry->get('id')."' LIMIT 1");*/

			foreach($entry->getData() as $field_id => $field){
					
				$this->_Parent->Database->delete('tbl_entries_data_' . $field_id, " `entry_id` = '".$entry->get('id')."'");
				
				if(!is_array($field) || empty($field)) continue;
				
				$data = array(
					'entry_id' => $entry->get('id')
				);
				
				$fields = array();
				
				foreach($field as $key => $value){
					
					if(is_array($value)){
						foreach($value as $ii => $v) $fields[$ii][$key] = $v;								
					}
					
					else{
						$fields[max(0, count($fields) - 1)][$key] = $value;
					}
				}
				
				for($ii = 0; $ii < count($fields); $ii++) $fields[$ii] = array_merge($data, $fields[$ii]);

				$this->_Parent->Database->insert($fields, 'tbl_entries_data_' . $field_id);

			}

			return true;
			
		}
		
		function fetchByPage($page, $section_id, $entriesPerPage, $where=NULL, $joins=NULL, $group=false, $records_only=false, $buildentries=true){
			
			if(!is_string($entriesPerPage) && !is_numeric($entriesPerPage)){
				trigger_error(__('Entry limit specified was not a valid type. String or Integer expected.'), E_USER_WARNING);
				return NULL;
			}
			
			$start = (max(1, $page) - 1) * $entriesPerPage;
		
			$records = ($entriesPerPage == '0' ? NULL : $this->fetch(NULL, $section_id, $entriesPerPage, $start, $where, $joins, $group, $buildentries));
			
			if($records_only) return array('records' => $records);
			
			$entries = array(
				'total-entries' => $this->fetchCount($section_id, $where, $joins, $group),
				'records' => $records,
				'start' => max(1, $start),
				'limit' => $entriesPerPage
			);
			
			$entries['remaining-entries'] = max(0, $entries['total-entries'] - ($start + $entriesPerPage));
			$entries['total-pages'] = max(1, ceil($entries['total-entries'] * (1 / $entriesPerPage)));
			$entries['remaining-pages'] = max(0, $entries['total-pages'] - $page);

			return $entries;
			
		}

		function fetchCount($section_id=NULL, $where=NULL, $joins=NULL, $group=false){

			if(!$entry_id && !$section_id) return false;
			elseif(!$section_id) $section_id = $this->fetchEntrySectionID($entry_id);

			$section = $this->sectionManager->fetch($section_id);
			
			if(!is_object($section)) return false;
			
			$sort = NULL;
			
			## We want to sort if thereis a custom entry sort order
			/*if($this->_fetchSortField && $field = $this->fieldManager->fetch($this->_fetchSortField)){
				$field->buildSortingSQL($joins, $where, $sort, $this->_fetchSortDirection);
				if(!$group) $group = $field->requiresSQLGrouping();
			}
			
			elseif($section->get('entry_order') && $field = $this->fieldManager->fetch($section->get('entry_order'))){
				$field->buildSortingSQL($joins, $where, $sort, $section->get('entry_order_direction'));
				if(!$group) $group = $field->requiresSQLGrouping();
			}
			
			else{
				$sort = 'ORDER BY `e`.`id` DESC';
			}*/
			
			$sql = "
				
				SELECT count(".($group ? 'DISTINCT ' : '')."`e`.id) as `count`
				FROM `tbl_entries` AS `e`							
				$joins		
				WHERE 1
				".($section_id ? "AND `e`.`section_id` = '$section_id' " : '')."
				$where
			";

			return $this->_Parent->Database->fetchVar('count', 0, $sql);

		}
		
		function setFetchSortingField($field_id){
			$this->_fetchSortField = $field_id;
		}
		
		function setFetchSortingDirection($direction){
			$direction = strtoupper($direction);
			$this->_fetchSortDirection = (in_array($direction, array('RAND', 'ASC', 'DESC')) ? $direction : NULL);
		}
		
		function setFetchSorting($field_id, $direction='ASC'){
			$this->setFetchSortingField($field_id);
			$this->setFetchSortingDirection($direction);
		}
		
		public function getFetchSorting(){
			return (object)array(
				'field' => $this->_fetchSortField,
				'direction' => $this->_fetchSortDirection
			);
		}
		
		/***
		
			Warning: Do not provide $entry_id as an array if not specifiying the $section_id
		
		***/
		function fetch($entry_id=NULL, $section_id=NULL, $limit=NULL, $start=NULL, $where=NULL, $joins=NULL, $group=false, $buildentries=true){
			$sort = null;
			
			if (!$entry_id && !$section_id) return false;
			
			if (!$section_id) $section_id = $this->fetchEntrySectionID($entry_id);
			
			$section = $this->sectionManager->fetch($section_id);
			
			if (!is_object($section)) return false;

			## We want to sort if there is a custom entry sort order
			if ($this->_fetchSortField == 'date') {
				$sort = 'ORDER BY ' . ($this->_fetchSortDirection != 'RAND' ? "`e`.`creation_date` $this->_fetchSortDirection" : 'RAND() ');
			}
			
			else if ($this->_fetchSortField == 'id') {
				$sort = 'ORDER BY ' . ($this->_fetchSortDirection != 'RAND' ? "`e`.`id` $this->_fetchSortDirection" : 'RAND() ');
			}
			
			else if ($this->_fetchSortField && $field = $this->fieldManager->fetch($this->_fetchSortField)) {
				$field->buildSortingSQL($joins, $where, $sort, $this->_fetchSortDirection);
				if (!$group) $group = $field->requiresSQLGrouping();
			}
			
			else if ($section->get('entry_order') && $field = $this->fieldManager->fetch($section->get('entry_order'))) {
				$field->buildSortingSQL($joins, $where, $sort, $section->get('entry_order_direction'));
				if (!$group) $group = $field->requiresSQLGrouping();
			}
			
			else {
				$sort = 'ORDER BY ' . ($this->_fetchSortDirection != 'RAND' ? "`e`.`id` $this->_fetchSortDirection" : 'RAND() ');
			}
			
			if ($entry_id && !is_array($entry_id)) $entry_id = array($entry_id);
			
			$sql = "
				
				SELECT  ".($group ? 'DISTINCT ' : '')."`e`.id, 
						`e`.section_id, e.`author_id`, 
						UNIX_TIMESTAMP(e.`creation_date`) AS `creation_date`, 
						UNIX_TIMESTAMP(e.`creation_date_gmt`) AS `creation_date_gmt`
						
				FROM `tbl_entries` AS `e`
				$joins		
				WHERE 1
				".($entry_id ? "AND `e`.`id` IN ('".@implode("', '", $entry_id)."') " : '')."
				".($section_id ? "AND `e`.`section_id` = '$section_id' " : '')."
				$where
				$sort
				".($limit ? 'LIMIT ' . intval($start) . ', ' . intval($limit) : '')."
				
			";

			$rows = Symphony::Database()->fetch($sql);
			
			return ($buildentries && (is_array($rows) && !empty($rows)) ? $this->__buildEntries($rows, $section_id) : $rows);
			
		}
		
		## Do not pass this function ID values from across more than one section.
		function __buildEntries(array $id_list, $section_id){
			$entries = array();
			
			if (!is_array($id_list) || empty($id_list)) return $entries;
			
			$schema = Symphony::Database()->fetch("SELECT * FROM `tbl_fields` WHERE `parent_section` = '$section_id'");
			
			$tmp = array();
			foreach ($id_list as $r) {
				$tmp[$r['id']] = $r;
			}
			$id_list = $tmp;
						
			$raw = array();
			
			$id_list_string = @implode("', '", array_keys($id_list));
			
			// Append meta data:
			foreach ($id_list as $entry_id => $entry) {
				$raw[$entry_id]['meta'] = $entry;
			}
			
			// Append field data:
			foreach ($schema as $f) {
				$field_id = $f['id'];
				
				$row = Symphony::Database()->fetch("SELECT * FROM `tbl_entries_data_{$field_id}` WHERE `entry_id` IN ('$id_list_string') ORDER BY `id` ASC");
				
				if (!is_array($row) || empty($row)) continue;
				
				$tmp = array();
				
				foreach ($row as $r) {
					$entry_id = $r['entry_id'];
					
					unset($r['id']);					
					unset($r['entry_id']);
					
					if (!isset($raw[$entry_id]['fields'][$field_id])) {
						$raw[$entry_id]['fields'][$field_id] = $r;
					}
					
					else {
						foreach (array_keys($r) as $key) {
							if (isset($raw[$entry_id]['fields'][$field_id][$key]) && !is_array($raw[$entry_id]['fields'][$field_id][$key])) {
								$raw[$entry_id]['fields'][$field_id][$key] = array($raw[$entry_id]['fields'][$field_id][$key], $r[$key]);
							}
								
							else if (!isset($raw[$entry_id]['fields'][$field_id][$key])) {
								$raw[$entry_id]['fields'][$field_id] = array($r[$key]);
							}
							
							else {
								$raw[$entry_id]['fields'][$field_id][$key][] = $r[$key];
							}
						}
					}
				}
			}
			
			// Need to restore the correct ID ordering
			$tmp = array();
			
			foreach (array_keys($id_list) as $entry_id) {
				$tmp[$entry_id] = $raw[$entry_id];
			}
			
			$raw = $tmp;
			
			$fieldPool = array();
			
			foreach ($raw as $entry) {
				$obj = $this->create();
				
				$obj->creationDate = DateTimeObj::get('c', $entry['meta']['creation_date']);
				$obj->set('id', $entry['meta']['id']);
				$obj->set('author_id', $entry['meta']['author_id']);
				$obj->set('section_id', $entry['meta']['section_id']);
				
				foreach ($entry['fields'] as $field_id => $data) $obj->setData($field_id, $data);
				
				$entries[] = $obj;
			}
			
			return $entries;			
		}
		
		function fetchEntrySectionID($entry_id){
			return Symphony::Database()->fetchVar('section_id', 0, "SELECT `section_id` FROM `tbl_entries` WHERE `id` = '$entry_id' LIMIT 1");
		}
		
	}	

