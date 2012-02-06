<?php

	Class SectionDatasource extends Datasource{

		public function processSystemParameters(Datasource $ds, Entry $entry, &$param_pool) {
			if(!isset($ds->dsParamPARAMOUTPUT)) return;

			// Support the legacy parameter `ds-datasource-handle`
			$key = 'ds-' . $ds->dsParamROOTELEMENT;
			$singleParam = count($ds->dsParamPARAMOUTPUT) == 1;

			foreach($ds->dsParamPARAMOUTPUT as $param) {
				// The new style of paramater is `ds-datasource-handle.field-handle`
				$param_key = $key . '.' . str_replace(':', '-', $param);

				if($param == 'system:id') {
					$param_pool[$param_key][] = $entry->get('id');
					if($singleParam) $param_pool[$key][] = $entry->get('id');
				}
				else if($param == 'system:author') {
					$param_pool[$param_key][] = $entry->get('author_id');
					if($singleParam) $param_pool[$key][] = $entry->get('author_id');
				}
				else if($param == 'system:date') {
					$param_pool[$param_key][] = DateTimeObj::get('c', $entry->creationDate);
					if($singleParam) $param_pool[$key][] = DateTimeObj::get('c', $entry->creationDate);
				}
			}
		}

		public function processParameters(Datasource $ds, Entry $entry, array $fieldPool, $field_id, array $values, &$param_pool) {
			if(!isset($ds->dsParamPARAMOUTPUT)) return;

			// Support the legacy parameter `ds-datasource-handle`
			$key = 'ds-' . $ds->dsParamROOTELEMENT;
			$singleParam = count($ds->dsParamPARAMOUTPUT) == 1;

			foreach($ds->dsParamPARAMOUTPUT as $param) {
				if($fieldPool[$field_id]->get('element_name') !== $param) continue;

				// The new style of paramater is `ds-datasource-handle.field-handle`
				$param_key = $key . '.' . str_replace(':', '-', $param);

				if(!isset($param_pool[$param_key]) || !is_array($param_pool[$param_key])) $param_pool[$param_key] = array();
				if($singleParam && (!isset($param_pool[$key]) || !is_array($param_pool[$key]))) $param_pool[$key] = array();

				$param_pool_values = $fieldPool[$field_id]->getParameterPoolValue($values, $entry->get('id'));

				if(is_array($param_pool_values)){
					$param_pool[$param_key] = array_merge($param_pool_values, $param_pool[$param_key]);

					if($singleParam) $param_pool[$key] = array_merge($param_pool_values, $param_pool[$key]);
				}
				else{
					$param_pool[$param_key][] = $param_pool_values;

					if($singleParam) $param_pool[$key][] = $param_pool_values;
				}
			}
		}

		public function processRecordGroup(&$wrapper, $element, $group, $ds, &$fieldPool, &$param_pool, $param_output_only=false){
			$associated_sections = NULL;

			$xGroup = new XMLElement($element, NULL, $group['attr']);

			if(!$section = SectionManager::fetch($ds->getSource())){
				$about = $ds->about();
				throw new Exception(__('The section associated with the data source %s could not be found.', array('<code>' . $about['name'] . '</code>')));
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

					$this->processSystemParameters($ds, $entry, $param_pool);

					$pool = FieldManager::fetch(array_keys($data));
					$fieldPool += $pool;

					foreach($data as $field_id => $values){

						if(!isset($fieldPool[$field_id]) || !is_object($fieldPool[$field_id])) {
							$fieldPool[$field_id] =& FieldManager::fetch($field_id);
						}

						$this->processParameters($ds, $entry, $fieldPool, $field_id, $values, $param_pool);

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
					foreach($group as $g) $this->processRecordGroup($xGroup, $element, $g, $ds, $fieldPool, $param_pool, $param_output_only);
				}
			}

			if(!$param_output_only) $wrapper->appendChild($xGroup);

			return;
		}

		public function execute() {

			$fieldPool = array();
			$where = NULL;
			$joins = NULL;
			$group = false;

			include_once(TOOLKIT . '/class.entrymanager.php');

			if(!$section = SectionManager::fetch($this->getSource())){
				$about = $this->about();
				trigger_error(__('The section associated with the data source %s could not be found.', array('<code>' . $about['name'] . '</code>')), E_USER_ERROR);
			}

			$sectioninfo = new XMLElement('section', General::sanitize($section->get('name')), array('id' => $section->get('id'), 'handle' => $section->get('handle')));

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
				$pool = FieldManager::fetch(array_keys($this->dsParamFILTERS));
				$fieldPool += $pool;

				foreach($this->dsParamFILTERS as $field_id => $filter){

					if((is_array($filter) && empty($filter)) || trim($filter) == '') continue;

					if(!is_array($filter)){
						$filter_type = $this->__determineFilterType($filter);

						$value = preg_split('/'.($filter_type == DS_FILTER_AND ? '\+' : '(?<!\\\\),').'\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);
						$value = array_map('trim', $value);

						$value = array_map(array('Datasource', 'removeEscapedCommas'), $value);
					}

					else $value = $filter;

					if($field_id != 'id' && $field_id != 'system:date' && !($fieldPool[$field_id] instanceof Field)){
						throw new Exception(
							__(
								'Error creating field object with id %1$d, for filtering in data source %2$s. Check this field exists.',
								array($field_id, '<code>' . $this->dsParamROOTELEMENT . '<code>')
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

			if($this->dsParamSORT == 'system:id') EntryManager::setFetchSorting('id', $this->dsParamORDER);
			elseif($this->dsParamSORT == 'system:date') EntryManager::setFetchSorting('date', $this->dsParamORDER);
			else EntryManager::setFetchSorting(FieldManager::fetchFieldIDFromElementName($this->dsParamSORT, $this->getSource()), $this->dsParamORDER);

			if(isset($this->dsParamPARAMOUTPUT) && !is_array($this->dsParamPARAMOUTPUT)) {
				$this->dsParamPARAMOUTPUT = array($this->dsParamPARAMOUTPUT);
			}

			// combine INCLUDEDELEMENTS and PARAMOUTPUT into an array of field names
			$datasource_schema = $this->dsParamINCLUDEDELEMENTS;
			if (!is_array($datasource_schema)) $datasource_schema = array();
			if (is_array($this->dsParamPARAMOUTPUT)) {
				foreach($this->dsParamPARAMOUTPUT as $p) {
					$datasource_schema[] = $p;
				}
			}
			if ($this->dsParamGROUP) $datasource_schema[] = FieldManager::fetchHandleFromID($this->dsParamGROUP);
			if(!isset($this->dsParamPAGINATERESULTS)) $this->dsParamPAGINATERESULTS = 'yes';

			$entries = EntryManager::fetchByPage(
				($this->dsParamPAGINATERESULTS == 'yes' && $this->dsParamSTARTPAGE > 0 ? $this->dsParamSTARTPAGE : 1),
				$this->getSource(),
				($this->dsParamPAGINATERESULTS == 'yes' && $this->dsParamLIMIT >= 0 ? $this->dsParamLIMIT : NULL),
				$where, $joins, $group,
				(!$include_pagination_element ? true : false),
				true,
				$datasource_schema
			);

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
			}

			else {
				if(!$this->_param_output_only){
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

				if(!isset($this->dsParamLIMIT) || $this->dsParamLIMIT > 0){

					if(isset($this->dsParamGROUP)):
						$fieldPool[$this->dsParamGROUP] =& FieldManager::fetch($this->dsParamGROUP);
						$groups = $fieldPool[$this->dsParamGROUP]->groupRecords($entries['records']);

						foreach($groups as $element => $group){
							foreach($group as $g) $this->processRecordGroup($result, $element, $g, $this, $fieldPool, $param_pool, $this->_param_output_only);
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

							$this->processSystemParameters($this, $entry, $param_pool);

							$pool = FieldManager::fetch(array_keys($data));
							$fieldPool += $pool;

							foreach($data as $field_id => $values){

								if(!isset($fieldPool[$field_id]) || !is_object($fieldPool[$field_id])) {
									$fieldPool[$field_id] =& FieldManager::fetch($field_id);
								}

								$this->processParameters($this, $entry, $fieldPool, $field_id, $values, $param_pool);

								if (!$this->_param_output_only) foreach ($this->dsParamINCLUDEDELEMENTS as $handle) {
									list($handle, $mode) = preg_split('/\s*:\s*/', $handle, 2);
									if($fieldPool[$field_id]->get('element_name') == $handle) {
										$fieldPool[$field_id]->appendFormattedElement($xEntry, $values, ($this->dsParamHTMLENCODE ? true : false), $mode, $entry->get('id'));
									}
								}
							}

							if($this->_param_output_only) continue;

							if(in_array('system:date', $this->dsParamINCLUDEDELEMENTS)){
								$xEntry->appendChild(
									General::createXMLDateObject(
										DateTimeObj::get('U', $entry->creationDate),
										'system-date'
									)
								);
							}

							$result->appendChild($xEntry);
						}

					endif;
				}

			}
		}
	}
