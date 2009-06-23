<?php

	if(!function_exists('processRecordGroup')){
		function processRecordGroup(&$wrapper, $element, $group, $ds, &$Parent, &$entryManager, &$fieldPool, &$param_pool, $param_output_only=false){
			
			$xGroup = new XMLElement($element, NULL, $group['attr']);
			$key = 'ds-' . $ds->dsParamROOTELEMENT;
			
			if(is_array($group['records']) && !empty($group['records'])){
				foreach($group['records'] as $entry){
					
					$data = $entry->getData();
					$fields = array();

					$xEntry = new XMLElement('entry');
					$xEntry->setAttribute('id', $entry->get('id'));
					
					$associated_entry_counts = $entry->fetchAllAssociatedEntryCounts();
					if(is_array($associated_entry_counts) && !empty($associated_entry_counts)){
						foreach($associated_entry_counts as $section_id => $count){
							$section_handle = $Parent->Database->fetchVar('handle', 0, "SELECT `handle` FROM `tbl_sections` WHERE `id` = '$section_id' LIMIT 1");
							$xEntry->setAttribute($section_handle, ''.$count.'');
						}
					}

					if(isset($ds->dsParamPARAMOUTPUT)){
						if($ds->dsParamPARAMOUTPUT == 'system:id') $param_pool[$key][] = $entry->get('id');
						elseif($ds->dsParamPARAMOUTPUT == 'system:date') $param_pool[$key][] = DateTimeObj::get('c', strtotime($entry->creationDate));
						elseif($ds->dsParamPARAMOUTPUT == 'system:author') $param_pool[$key][] = $entry->get('author_id');
					}

					foreach($data as $field_id => $values){

						if(!isset($fieldPool[$field_id]) || !is_object($fieldPool[$field_id]))
							$fieldPool[$field_id] =& $entryManager->fieldManager->fetch($field_id);

						if(isset($ds->dsParamPARAMOUTPUT) && $ds->dsParamPARAMOUTPUT == $fieldPool[$field_id]->get('element_name')){
							$param_pool[$key][] = $fieldPool[$field_id]->getParameterPoolValue($values);
						}
						
						if (!$param_output_only) foreach ($ds->dsParamINCLUDEDELEMENTS as $handle) {
							list($handle, $mode) = preg_split('/\s*:\s*/', $handle, 2);
							if($fieldPool[$field_id]->get('element_name') == $handle) {
								$fieldPool[$field_id]->appendFormattedElement($xEntry, $values, ($ds->dsParamHTMLENCODE ? true : false), $mode);
							}
						}
					}

					if(!$param_output_only){ 
						if(in_array('system:date', $ds->dsParamINCLUDEDELEMENTS)){ 
							$xEntry->appendChild(General::createXMLDateObject(strtotime($entry->creationDate), 'system-date'));
						}
						$xGroup->appendChild($xEntry);
					}
										
				} 
			}
			
			if(is_array($group['groups']) && !empty($group['groups'])){
				foreach($group['groups'] as $element => $group){
					foreach($group as $g) processRecordGroup($xGroup, $element, $g, $ds, $Parent, $entryManager, $fieldPool, $param_pool, $param_output_only);
				}	
			}
					
			if(!$param_output_only) $wrapper->appendChild($xGroup);
			
			return;
		}
	}
	
	$fieldPool = array();
	$where = NULL;
	$joins = NULL;
	$group = false;

	include_once(TOOLKIT . '/class.entrymanager.php');
	$entryManager = new EntryManager($this->_Parent);
	
	$include_pagination_element = @in_array('system:pagination', $this->dsParamINCLUDEDELEMENTS);
	
	if(is_array($this->dsParamFILTERS) && !empty($this->dsParamFILTERS)){
		foreach($this->dsParamFILTERS as $field_id => $filter){
			
			if((is_array($filter) && empty($filter)) || trim($filter) == '') continue;
			
			if(!is_array($filter)){
				$filter_type = $this->__determineFilterType($filter);
	
				$value = preg_split('/'.($filter_type == DS_FILTER_AND ? '\+' : ',').'\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);			
				$value = array_map('trim', $value);
			}
			
			else $value = $filter;
			
			if(!isset($fieldPool[$field_id]) || !is_object($fieldPool[$field_id]))
				$fieldPool[$field_id] =& $entryManager->fieldManager->fetch($field_id);
			
			if($field_id != 'id' && !($fieldPool[$field_id] instanceof Field)){
				throw new Exception(
					__('Error creating field object with id %1$d, for filtering in data source "%2$s". Check this field exists.', 
							$field_id, 
							$this->dsParamROOTELEMENT)
				);
			}
						
			if($field_id == 'id') $where = " AND `e`.id IN ('".@implode("', '", $value)."') ";
			else{ 
				if(!$fieldPool[$field_id]->buildDSRetrivalSQL($value, $joins, $where, ($filter_type == DS_FILTER_AND ? true : false))){ $this->_force_empty_result = true; return; }
				if(!$group) $group = $fieldPool[$field_id]->requiresSQLGrouping();
			}
			
		}
	}
	
	if($this->dsParamSORT == 'system:id') $entryManager->setFetchSorting('id', $this->dsParamORDER);
	elseif($this->dsParamSORT == 'system:date') $entryManager->setFetchSorting('date', $this->dsParamORDER);
	else $entryManager->setFetchSorting($entryManager->fieldManager->fetchFieldIDFromElementName($this->dsParamSORT, $this->getSource()), $this->dsParamORDER);

	$entries = $entryManager->fetchByPage($this->dsParamSTARTPAGE, 
										  $this->getSource(), 
										  ($this->dsParamLIMIT >= 0 ? $this->dsParamLIMIT : NULL), 
										  $where, $joins, $group, 
										  (!$include_pagination_element ? true : false), 
										  true);

	if(!$section = $entryManager->sectionManager->fetch($this->getSource())){
		$about = $this->about();
		trigger_error(__('The section associated with the data source <code>%s</code> could not be found.', array($about['name'])), E_USER_ERROR);
	}
	
	$sectioninfo = new XMLElement('section', $section->get('name'), array('id' => $section->get('id'), 'handle' => $section->get('handle')));
	
	$key = 'ds-' . $this->dsParamROOTELEMENT;
									
	if($entries['total-entries'] <= 0 && (!is_array($entries['records']) || empty($entries['records']))){
		if($this->dsParamREDIRECTONEMPTY == 'yes') $this->__redirectToErrorPage();
		$this->_force_empty_result = false;
		$result = $this->emptyXMLSet();
		$result->prependChild($sectioninfo);
		
		if($include_pagination_element) {
			$pagination_element = General::buildPaginationElement();
			
			if($pagination_element instanceof XMLElement && $result instanceof XMLElement){
				$result->prependChild($pagination_element); 
			}
		}
		
		$param_pool[$key][] = '';
	}
	
	else{
	
		if(!$this->_param_output_only){			
			$result = new XMLElement($this->dsParamROOTELEMENT);
		
			$result->appendChild($sectioninfo);
			
			if($include_pagination_element){
				
				$t = ($this->dsParamLIMIT >= 0 ? $this->dsParamLIMIT : $entries['total-entries']);
				
				$pagination_element = General::buildPaginationElement(
					$entries['total-entries'], 
					$entries['total-pages'], 
					$t, 
					$this->dsParamSTARTPAGE);

				if($pagination_element instanceof XMLElement && $result instanceof XMLElement){
					$result->prependChild($pagination_element); 
				}
				
			}
		}
		
		if(isset($this->dsParamPARAMOUTPUT) && !is_array($param_pool[$key])) $param_pool[$key] = array();
		
		if($this->dsParamLIMIT > 0){
		
			if(isset($this->dsParamGROUP)):
				$fieldPool[$this->dsParamGROUP] =& $entryManager->fieldManager->fetch($this->dsParamGROUP);		
				$groups = $fieldPool[$this->dsParamGROUP]->groupRecords($entries['records']);		
		
				foreach($groups as $element => $group){
					foreach($group as $g) processRecordGroup($result, $element, $g, $this, $this->_Parent, $entryManager, $fieldPool, $param_pool, $this->_param_output_only);
				}
		
			else:
	
				foreach($entries['records'] as $entry){

					$data = $entry->getData();
					$fields = array();
		
					$xEntry = new XMLElement('entry');
					$xEntry->setAttribute('id', $entry->get('id'));
					
					$associated_entry_counts = $entry->fetchAllAssociatedEntryCounts();
					if(is_array($associated_entry_counts) && !empty($associated_entry_counts)){
						foreach($associated_entry_counts as $section_id => $count){
							$section_handle = $this->_Parent->Database->fetchVar('handle', 0, "SELECT `handle` FROM `tbl_sections` WHERE `id` = '$section_id' LIMIT 1");
							$xEntry->setAttribute($section_handle, ''.$count.'');
						}
					}

					if(isset($this->dsParamPARAMOUTPUT)){
						if($this->dsParamPARAMOUTPUT == 'system:id') $param_pool[$key][] = $entry->get('id');
						elseif($this->dsParamPARAMOUTPUT == 'system:date') $param_pool[$key][] = DateTimeObj::get('c', strtotime($entry->creationDate));
						elseif($this->dsParamPARAMOUTPUT == 'system:author') $param_pool[$key][] = $entry->get('author_id');
					}
					
					foreach($data as $field_id => $values){

						if(!isset($fieldPool[$field_id]) || !is_object($fieldPool[$field_id]))
							$fieldPool[$field_id] =& $entryManager->fieldManager->fetch($field_id);
			
						if(isset($this->dsParamPARAMOUTPUT) && $this->dsParamPARAMOUTPUT == $fieldPool[$field_id]->get('element_name')){
							$param_pool[$key][] = $fieldPool[$field_id]->getParameterPoolValue($values);
						}

						if (!$this->_param_output_only) foreach ($this->dsParamINCLUDEDELEMENTS as $handle) {
							list($handle, $mode) = preg_split('/\s*:\s*/', $handle, 2);
							if($fieldPool[$field_id]->get('element_name') == $handle) {
								$fieldPool[$field_id]->appendFormattedElement($xEntry, $values, ($this->dsParamHTMLENCODE ? true : false), $mode);
							}
						}
					}

					if($this->_param_output_only) continue;
					
					if(in_array('system:date', $this->dsParamINCLUDEDELEMENTS)){ 
						$xEntry->appendChild(General::createXMLDateObject(strtotime($entry->creationDate), 'system-date'));
					}
					
					$result->appendChild($xEntry);
				}
		
			endif;
		}
		
	}

