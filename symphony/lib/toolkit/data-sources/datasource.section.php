<?php

	if(!function_exists('processRecordGroup')){
		function processRecordGroup(&$wrapper, $element, $group, $ds, &$entryManager, &$fieldPool, &$param_pool, $param_output_only=false){
			$associated_sections = NULL;

			$xGroup = new XMLElement($element, NULL, $group['attr']);
			$key = 'ds-' . $ds->dsParamROOTELEMENT;

			if(!$section = $entryManager->sectionManager->fetch($ds->getSource())){
				$about = $ds->about();
				throw new Exception(__('The section associated with the data source <code>%s</code> could not be found.', array($about['name'])));
			}

			if(!isset($ds->dsParamASSOCIATEDENTRYCOUNTS) || $ds->dsParamASSOCIATEDENTRYCOUNTS == 'yes'){
				$associated_sections = $section->fetchAssociatedSections();
			}

			if(is_array($group['records']) && !empty($group['records'])){
				foreach($group['records'] as $entry){

					$data = $entry->getData();
					$fields = array();

					$xEntry = new XMLElement('entry');
					$xEntry->setAttribute('id', $entry->get('id'));

					if(is_array($associated_sections)) {
						$associated_entry_counts = $entry->fetchAllAssociatedEntryCounts($associated_sections);
						if(is_array($associated_entry_counts) && !empty($associated_entry_counts)){
							foreach($associated_entry_counts as $section_id => $count){
								foreach($associated_sections as $section) {
									if ($section['id'] == $section_id) $xEntry->setAttribute($section['handle'], (string)$count);
								}
							}
						}
					}

					if(isset($ds->dsParamPARAMOUTPUT)){
						if($ds->dsParamPARAMOUTPUT == 'system:id') $param_pool[$key][] = $entry->get('id');
						elseif($ds->dsParamPARAMOUTPUT == 'system:date') $param_pool[$key][] = DateTimeObj::get('c', $entry->creationDate);
						elseif($ds->dsParamPARAMOUTPUT == 'system:author') $param_pool[$key][] = $entry->get('author_id');
					}

					foreach($data as $field_id => $values){

						if(!isset($fieldPool[$field_id]) || !is_object($fieldPool[$field_id]))
							$fieldPool[$field_id] =& $entryManager->fieldManager->fetch($field_id);

						if(isset($ds->dsParamPARAMOUTPUT) && $ds->dsParamPARAMOUTPUT == $fieldPool[$field_id]->get('element_name')){
							if(!isset($param_pool[$key]) || !is_array($param_pool[$key])) $param_pool[$key] = array();

							$param_pool_values = $fieldPool[$field_id]->getParameterPoolValue($values);

							if(is_array($param_pool_values)){
								$param_pool[$key] = array_merge($param_pool_values, $param_pool[$key]);
							}
							else{
								$param_pool[$key][] = $param_pool_values;
							}
						}

						if (!$param_output_only) if (is_array($ds->dsParamINCLUDEDELEMENTS)) foreach ($ds->dsParamINCLUDEDELEMENTS as $handle) {
							list($handle, $mode) = preg_split('/\s*:\s*/', $handle, 2);
							if($fieldPool[$field_id]->get('element_name') == $handle) {
								$fieldPool[$field_id]->appendFormattedElement($xEntry, $values, ($ds->dsParamHTMLENCODE ? true : false), $mode, $entry->get('id'));
							}
						}
					}

					if(!$param_output_only){
						if(is_array($ds->dsParamINCLUDEDELEMENTS) && in_array('system:date', $ds->dsParamINCLUDEDELEMENTS)){
							$xEntry->appendChild(
								General::createXMLDateObject(
									DateTimeObj::get('U', $entry->creationDate),
									'system-date'
								)
							);
						}
						$xGroup->appendChild($xEntry);
					}

				}
			}

			if(is_array($group['groups']) && !empty($group['groups'])){
				foreach($group['groups'] as $element => $group){
					foreach($group as $g) processRecordGroup($xGroup, $element, $g, $ds, $entryManager, $fieldPool, $param_pool, $param_output_only);
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

	$key = 'ds-' . $this->dsParamROOTELEMENT;

	include_once(TOOLKIT . '/class.entrymanager.php');
	$entryManager = new EntryManager($this->_Parent);

	if(!$section = $entryManager->sectionManager->fetch($this->getSource())){
		$about = $this->about();
		trigger_error(__('The section associated with the data source <code>%s</code> could not be found.', array($about['name'])), E_USER_ERROR);
	}

	$sectioninfo = new XMLElement('section', $section->get('name'), array('id' => $section->get('id'), 'handle' => $section->get('handle')));

	if($this->_force_empty_result == true){
		$this->_force_empty_result = false; //this is so the section info element doesn't dissapear.
		$result = $this->emptyXMLSet();
		$result->prependChild($sectioninfo);
		return;
	}

	if(is_array($this->dsParamINCLUDEDELEMENTS)) {
		$include_pagination_element = in_array('system:pagination', $this->dsParamINCLUDEDELEMENTS);
	}

	if(is_array($this->dsParamFILTERS) && !empty($this->dsParamFILTERS)){
		foreach($this->dsParamFILTERS as $field_id => $filter){

			if((is_array($filter) && empty($filter)) || trim($filter) == '') continue;

			if(!is_array($filter)){
				$filter_type = $this->__determineFilterType($filter);

				$value = preg_split('/'.($filter_type == DS_FILTER_AND ? '\+' : '(?<!\\\\),').'\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);
				$value = array_map('trim', $value);

				$value = array_map(array('Datasource', 'removeEscapedCommas'), $value);
			}

			else $value = $filter;

			if(!isset($fieldPool[$field_id]) || !is_object($fieldPool[$field_id]))
				$fieldPool[$field_id] =& $entryManager->fieldManager->fetch($field_id);

			if($field_id != 'id' && $field_id != 'system:date' && !($fieldPool[$field_id] instanceof Field)){
				throw new Exception(
					__(
						'Error creating field object with id %1$d, for filtering in data source "%2$s". Check this field exists.',
						array($field_id, $this->dsParamROOTELEMENT)
					)
				);
			}

			if($field_id == 'id') {
				$where = " AND `e`.id IN ('".implode("', '", $value)."') ";
			}
			else if($field_id == 'system:date') {
				require_once(TOOLKIT . '/fields/field.date.php');
				$date = new fieldDate(Frontend::instance());

				// Create an empty string, we don't care about the Joins, we just want the WHERE clause.
				$empty = "";
				$date->buildDSRetrievalSQL($value, $empty, $where, ($filter_type == DS_FILTER_AND ? true : false));

				$where = preg_replace('/`t\d+`.value/', '`e`.creation_date', $where);
			}
			else{
				// For deprecated reasons, call the old, typo'd function name until the switch to the
				// properly named buildDSRetrievalSQL function.
				if(!$fieldPool[$field_id]->buildDSRetrivalSQL($value, $joins, $where, ($filter_type == DS_FILTER_AND ? true : false))){ $this->_force_empty_result = true; return; }
				if(!$group) $group = $fieldPool[$field_id]->requiresSQLGrouping();
			}

		}
	}

	if($this->dsParamSORT == 'system:id') $entryManager->setFetchSorting('id', $this->dsParamORDER);
	elseif($this->dsParamSORT == 'system:date') $entryManager->setFetchSorting('date', $this->dsParamORDER);
	else $entryManager->setFetchSorting($entryManager->fieldManager->fetchFieldIDFromElementName($this->dsParamSORT, $this->getSource()), $this->dsParamORDER);

	// combine INCLUDEDELEMENTS and PARAMOUTPUT into an array of field names
	$datasource_schema = $this->dsParamINCLUDEDELEMENTS;
	if (!is_array($datasource_schema)) $datasource_schema = array();
	if ($this->dsParamPARAMOUTPUT) $datasource_schema[] = $this->dsParamPARAMOUTPUT;
	if ($this->dsParamGROUP) $datasource_schema[] = $entryManager->fieldManager->fetchHandleFromID($this->dsParamGROUP);
	if(!isset($this->dsParamPAGINATERESULTS)) $this->dsParamPAGINATERESULTS = 'yes';

	$entries = $entryManager->fetchByPage(($this->dsParamPAGINATERESULTS == 'yes' && $this->dsParamSTARTPAGE > 0 ? $this->dsParamSTARTPAGE : 1),
										  $this->getSource(),
										  ($this->dsParamPAGINATERESULTS == 'yes' && $this->dsParamLIMIT >= 0 ? $this->dsParamLIMIT : NULL),
										  $where, $joins, $group,
										  (!$include_pagination_element ? true : false),
										  true,
										  $datasource_schema);

	/**
	 * Immediately after building entries allow modification of the Data Source entry list
	 *
	 * @delegate DataSourceEntriesBuilt
	 * @param string $context
	 * '/frontend/'
	 * @param Datasource $datasource
	 * @param array $entries
	 * @param array $filters
	 */
	Symphony::ExtensionManager()->notifyMembers('DataSourceEntriesBuilt', '/frontend/', array(
		'datasource' => &$this,
		'entries' => &$entries,
		'filters' => $this->dsParamFILTERS
	));

	if(($entries['total-entries'] <= 0 || $include_pagination_element === true) && (!is_array($entries['records']) || empty($entries['records'])) || $this->dsParamSTARTPAGE == '0'){
		if($this->dsParamREDIRECTONEMPTY == 'yes'){
			throw new FrontendPageNotFoundException;
		}
		$this->_force_empty_result = false;
		$result = $this->emptyXMLSet();
		$result->prependChild($sectioninfo);

		if($include_pagination_element) {
			$pagination_element = General::buildPaginationElement();

			if($pagination_element instanceof XMLElement && $result instanceof XMLElement){
				$result->prependChild($pagination_element);
			}
		}

		if(isset($this->dsParamPARAMOUTPUT)){
			$param_pool[$key][] = '';
		}
	}

	else{

		if(!$this->_param_output_only){
			$result = new XMLElement($this->dsParamROOTELEMENT);

			$result->appendChild($sectioninfo);

			if($include_pagination_element){

				$t = ($this->dsParamPAGINATERESULTS == 'yes' && isset($this->dsParamLIMIT) && $this->dsParamLIMIT >= 0 ? $this->dsParamLIMIT : $entries['total-entries']);

				$pagination_element = General::buildPaginationElement(
					$entries['total-entries'],
					$entries['total-pages'],
					$t,
					($this->dsParamPAGINATERESULTS == 'yes' && $this->dsParamSTARTPAGE > 0 ? $this->dsParamSTARTPAGE : 1));

				if($pagination_element instanceof XMLElement && $result instanceof XMLElement){
					$result->prependChild($pagination_element);
				}

			}
		}

		if(isset($this->dsParamPARAMOUTPUT) && !is_array($param_pool[$key])) $param_pool[$key] = array();

		if(!isset($this->dsParamLIMIT) || $this->dsParamLIMIT > 0){

			if(isset($this->dsParamGROUP)):
				$fieldPool[$this->dsParamGROUP] =& $entryManager->fieldManager->fetch($this->dsParamGROUP);
				$groups = $fieldPool[$this->dsParamGROUP]->groupRecords($entries['records']);

				foreach($groups as $element => $group){
					foreach($group as $g) processRecordGroup($result, $element, $g, $this, $entryManager, $fieldPool, $param_pool, $this->_param_output_only);
				}

			else:

				if (!isset($this->dsParamASSOCIATEDENTRYCOUNTS) || $this->dsParamASSOCIATEDENTRYCOUNTS == 'yes') $associated_sections = $section->fetchAssociatedSections();

				foreach($entries['records'] as $entry){

					$data = $entry->getData();
					$fields = array();

					$xEntry = new XMLElement('entry');
					$xEntry->setAttribute('id', $entry->get('id'));

					if (is_array($associated_sections)) {
						$associated_entry_counts = $entry->fetchAllAssociatedEntryCounts($associated_sections);
						if(is_array($associated_entry_counts) && !empty($associated_entry_counts)){
							foreach($associated_entry_counts as $section_id => $count){
								foreach($associated_sections as $section) {
									if ($section['id'] == $section_id) $xEntry->setAttribute($section['handle'], (string)$count);
								}
							}
						}
					}

					if(isset($this->dsParamPARAMOUTPUT)){
						if($this->dsParamPARAMOUTPUT == 'system:id') $param_pool[$key][] = $entry->get('id');
						elseif($this->dsParamPARAMOUTPUT == 'system:date') $param_pool[$key][] = DateTimeObj::get('c', $entry->creationDate);
						elseif($this->dsParamPARAMOUTPUT == 'system:author') $param_pool[$key][] = $entry->get('author_id');
					}

					foreach($data as $field_id => $values){

						if(!isset($fieldPool[$field_id]) || !is_object($fieldPool[$field_id]))
							$fieldPool[$field_id] =& $entryManager->fieldManager->fetch($field_id);

						if(isset($this->dsParamPARAMOUTPUT) && $this->dsParamPARAMOUTPUT == $fieldPool[$field_id]->get('element_name')){
							if(!isset($param_pool[$key]) || !is_array($param_pool[$key])) $param_pool[$key] = array();

							$param_pool_values = $fieldPool[$field_id]->getParameterPoolValue($values);

							if(is_array($param_pool_values)){
								$param_pool[$key] = array_merge($param_pool_values, $param_pool[$key]);
							}
							else{
								$param_pool[$key][] = $param_pool_values;
							}
						}

						if (!$this->_param_output_only) foreach ($this->dsParamINCLUDEDELEMENTS as $handle) {
							list($handle, $mode) = preg_split('/\s*:\s*/', $handle, 2);
							if($fieldPool[$field_id]->get('element_name') == $handle) {
								$fieldPool[$field_id]->appendFormattedElement($xEntry, $values, ($this->dsParamHTMLENCODE ? true : false), $mode, $entry->get('id'));
							}
						}
					}

					$result->appendChild($xEntry);

					if(!$this->_param_output_only && in_array('system:date', $this->dsParamINCLUDEDELEMENTS)){
						$xEntry->appendChild(
							General::createXMLDateObject(
								DateTimeObj::get('U', $entry->creationDate),
								'system-date'
							)
						);
					}
				}

			endif;
		}

	}
