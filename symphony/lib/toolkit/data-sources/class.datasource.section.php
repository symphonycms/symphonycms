<?php

	/**
	 * @package data-sources
	 */
	/**
	 * The `SectionDatasource` allows a user to retrieve entries from a given
	 * section on the Frontend. This datasource type exposes the filtering provided
	 * by the Fields in the given section to narrow the result set down. The resulting
	 * entries can be grouped, sorted and allows pagination. Results can be chained
	 * from other `SectionDatasource`'s using output parameters.
	 *
	 * @since Symphony 2.3
	 * @link http://symphony-cms.com/learn/concepts/view/data-sources/
	 */
	Class SectionDatasource extends Datasource {

		/**
		 * An array of Field objects that this Datasource has created to display
		 * the results.
		 */
		private static $_fieldPool = array();

		/**
		 * An array of the Symphony meta data parameters.
		 */
		private static $_system_parameters = array(
			'system:id',
			'system:author',
			'system:date'
		);

		/**
		 * Set's the Section ID that this Datasource will use as it's source
		 *
		 * @param integer $source
		 */
		public function setSource($source) {
			$this->_source = (int)$source;
		}

		/**
		 * Return's the Section ID that this datasource is using as it's source
		 *
		 * @return integer
		 */
		public function getSource() {
			return $this->_source;
		}

		/**
		 * If this Datasource requires System Parameters to be output, this function
		 * will return true, otherwise false.
		 *
		 * @return boolean
		 */
		public function canProcessSystemParameters() {
			if(!is_array($this->dsParamPARAMOUTPUT)) return false;

			foreach(self::$_system_parameters as $system_parameter) {
				if(in_array($system_parameter, $this->dsParamPARAMOUTPUT) === true) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Given an Entry object, this function will iterate over the `dsParamPARAMOUTPUT`
		 * setting to see any of the Symphony system parameters need to be set.
		 * The current system parameters supported are `system:id`, `system:author`
		 * and `system:date`. If these parameters are found, the result is added
		 * to the `$param_pool` array using the key, `ds-datasource-handle.parameter-name`
		 * For the moment, this function also supports the pre Symphony 2.3 syntax,
		 * `ds-datasource-handle` which did not support multiple parameters.
		 *
		 * @param Entry $entry
		 *  The Entry object that contains the values that may need to be added
		 *  into the parameter pool.
		 * @param array $param_pool
		 *  An array of parameters, passed by reference, where the key is the parameter
		 *  name and an array of values.
		 */
		public function processSystemParameters(Entry $entry, array &$param_pool) {
			if(!isset($this->dsParamPARAMOUTPUT)) return;

			// Support the legacy parameter `ds-datasource-handle`
			$key = 'ds-' . $this->dsParamROOTELEMENT;
			$singleParam = count($this->dsParamPARAMOUTPUT) == 1;

			foreach($this->dsParamPARAMOUTPUT as $param) {
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

		public function processOutputParameters(Entry $entry, array &$param_pool, $field_id, array $values) {
			if(!isset($this->dsParamPARAMOUTPUT)) return;

			// Support the legacy parameter `ds-datasource-handle`
			$key = 'ds-' . $this->dsParamROOTELEMENT;
			$singleParam = count($this->dsParamPARAMOUTPUT) == 1;
			if($singleParam && (!isset($param_pool[$key]) || !is_array($param_pool[$key]))) $param_pool[$key] = array();

			foreach($this->dsParamPARAMOUTPUT as $param) {
				if(self::$_fieldPool[$field_id]->get('element_name') !== $param) continue;

				// The new style of paramater is `ds-datasource-handle.field-handle`
				$param_key = $key . '.' . str_replace(':', '-', $param);

				if(!isset($param_pool[$param_key]) || !is_array($param_pool[$param_key])) $param_pool[$param_key] = array();

				$param_pool_values = self::$_fieldPool[$field_id]->getParameterPoolValue($values, $entry->get('id'));

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

		/**
		 * An entry may be associated to other entries from various fields through
		 * the section associations. This function will set the number of related
		 * entries as attributes to the main `<entry>` element grouped by the
		 * related entry's section.
		 *
		 * @param XMLElement $xEntry
		 *  The <entry> XMLElement that the associated section counts will
		 *  be set on
		 * @param Entry $entry
		 *  The current entry object
		 * @param array $associated_sections
		 *  An array of the associated sections for this entryS
		 */
		public function setAssociatedEntryCounts(XMLElement &$xEntry, Entry $entry, array $associated_sections) {
			$associated_entry_counts = $entry->fetchAllAssociatedEntryCounts($associated_sections);
			if(!empty($associated_entry_counts)){
				foreach($associated_entry_counts as $section_id => $count){
					foreach($associated_sections as $section) {
						if ($section['id'] == $section_id) $xEntry->setAttribute($section['handle'], (string)$count);
					}
				}
			}
		}

		public function execute(array &$param_pool) {
			$result = new XMLElement($this->dsParamROOTELEMENT);

			$where = NULL;
			$joins = NULL;
			$group = false;

			include_once(TOOLKIT . '/class.entrymanager.php');

			if(!$section = SectionManager::fetch((int)$this->getSource())){
				$about = $this->about();
				trigger_error(__('The section associated with the data source %s could not be found.', array('<code>' . $about['name'] . '</code>')), E_USER_ERROR);
			}

			$sectioninfo = new XMLElement('section', General::sanitize($section->get('name')), array(
				'id' => $section->get('id'),
				'handle' => $section->get('handle')
			));

			if($this->_force_empty_result == true){
				$this->_force_empty_result = false; //this is so the section info element doesn't disappear.
				$result = $this->emptyXMLSet();
				$result->prependChild($sectioninfo);
				return;
			}

			if(is_array($this->dsParamINCLUDEDELEMENTS)) {
				$include_pagination_element = in_array('system:pagination', $this->dsParamINCLUDEDELEMENTS);
			}

			if(isset($this->dsParamPARAMOUTPUT) && !is_array($this->dsParamPARAMOUTPUT)) {
				$this->dsParamPARAMOUTPUT = array($this->dsParamPARAMOUTPUT);
			}

			$this->_can_process_system_parameters = $this->canProcessSystemParameters();

			if(!isset($this->dsParamPAGINATERESULTS)) {
				$this->dsParamPAGINATERESULTS = 'yes';
			}

			// Process Filters
			if(is_array($this->dsParamFILTERS) && !empty($this->dsParamFILTERS)){
				$pool = FieldManager::fetch(array_keys($this->dsParamFILTERS));
				self::$_fieldPool += $pool;

				foreach($this->dsParamFILTERS as $field_id => $filter){

					if((is_array($filter) && empty($filter)) || trim($filter) == '') continue;

					if(!is_array($filter)){
						$filter_type = $this->__determineFilterType($filter);

						$value = preg_split('/'.($filter_type == DS_FILTER_AND ? '\+' : '(?<!\\\\),').'\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);
						$value = array_map('trim', $value);
						$value = array_map(array('Datasource', 'removeEscapedCommas'), $value);
					}

					else $value = $filter;

					if($field_id != 'id' && $field_id != 'system:date' && !(self::$_fieldPool[$field_id] instanceof Field)){
						throw new Exception(
							__(
								'Error creating field object with id %1$d, for filtering in data source %2$s. Check this field exists.',
								array($field_id, '<code>' . $this->dsParamROOTELEMENT . '</code>')
							)
						);
					}

					if($field_id == 'id') {
						$c = 'IN';
						if(stripos($value[0], 'not:') === 0) {
							$value[0] = preg_replace('/^not:\s*/', null, $value[0]);
							$c = 'NOT IN';
						}

						$where = " AND `e`.id " . $c . " ('".implode("', '", $value)."') ";
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
						if(!self::$_fieldPool[$field_id]->buildDSRetrivalSQL($value, $joins, $where, ($filter_type == DS_FILTER_AND ? true : false))){ $this->_force_empty_result = true; return; }
						if(!$group) $group = self::$_fieldPool[$field_id]->requiresSQLGrouping();
					}
				}
			}

			// Process Sorting
			if($this->dsParamSORT == 'system:id') {
				EntryManager::setFetchSorting('id', $this->dsParamORDER);
			}
			else if($this->dsParamSORT == 'system:date') {
				EntryManager::setFetchSorting('date', $this->dsParamORDER);
			}
			else {
				EntryManager::setFetchSorting(
					FieldManager::fetchFieldIDFromElementName($this->dsParamSORT, $this->getSource()),
					$this->dsParamORDER
				);
			}

			// combine INCLUDEDELEMENTS and PARAMOUTPUT into an array of field names
			$datasource_schema = $this->dsParamINCLUDEDELEMENTS;
			if (!is_array($datasource_schema)) $datasource_schema = array();
			if (is_array($this->dsParamPARAMOUTPUT)) {
				$datasource_schema = array_merge($datasource_schema, $this->dsParamPARAMOUTPUT);
			}
			if ($this->dsParamGROUP) {
				$datasource_schema[] = FieldManager::fetchHandleFromID($this->dsParamGROUP);
			}

			$entries = EntryManager::fetchByPage(
				($this->dsParamPAGINATERESULTS == 'yes' && $this->dsParamSTARTPAGE > 0 ? $this->dsParamSTARTPAGE : 1),
				$this->getSource(),
				($this->dsParamPAGINATERESULTS == 'yes' && $this->dsParamLIMIT >= 0 ? $this->dsParamLIMIT : NULL),
				$where, $joins, $group,
				(!$include_pagination_element ? true : false),
				true,
				array_unique($datasource_schema)
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
						self::$_fieldPool[$this->dsParamGROUP] =& FieldManager::fetch($this->dsParamGROUP);
						$groups = self::$_fieldPool[$this->dsParamGROUP]->groupRecords($entries['records']);

						foreach($groups as $element => $group){
							foreach($group as $g) {
								$this->processRecordGroup($result, $element, $g, $param_pool);
							}
						}

					else:

						if (!isset($this->dsParamASSOCIATEDENTRYCOUNTS) || $this->dsParamASSOCIATEDENTRYCOUNTS == 'yes') {
							$associated_sections = $section->fetchAssociatedSections();
						}

						if(isset($entries['records'][0])) {
							$data = $entries['records'][0]->getData();
							$pool = FieldManager::fetch(array_keys($data));
							self::$_fieldPool += $pool;
						}

						foreach($entries['records'] as $entry){
							$data = $entry->getData();

							$xEntry = new XMLElement('entry');
							$xEntry->setAttribute('id', $entry->get('id'));

							if(!empty($associated_sections)) {
								$this->setAssociatedEntryCounts($xEntry, $entry, $associated_sections);
							}

							if($this->_can_process_system_parameters) {
								$this->processSystemParameters($entry, $param_pool);
							}

							foreach($data as $field_id => $values) {
								if(!isset(self::$_fieldPool[$field_id]) || !is_object(self::$_fieldPool[$field_id])) {
									self::$_fieldPool[$field_id] =& FieldManager::fetch($field_id);
								}

								$this->processOutputParameters($entry, $param_pool, $field_id, $values);

								if (!$this->_param_output_only) foreach ($this->dsParamINCLUDEDELEMENTS as $handle) {
									list($handle, $mode) = preg_split('/\s*:\s*/', $handle, 2);
									if(self::$_fieldPool[$field_id]->get('element_name') == $handle) {
										self::$_fieldPool[$field_id]->appendFormattedElement($xEntry, $values, ($this->dsParamHTMLENCODE ? true : false), $mode, $entry->get('id'));
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

			return $result;
		}

		public function processRecordGroup(XMLElement $result, $element, $group, &$param_pool){
			$associated_sections = NULL;

			$xGroup = new XMLElement($element, NULL, $group['attr']);

			if(!isset($this->dsParamASSOCIATEDENTRYCOUNTS) || $this->dsParamASSOCIATEDENTRYCOUNTS == 'yes'){
				$associated_sections = $section->fetchAssociatedSections();
			}

			if(is_array($group['records']) && !empty($group['records'])){
				if(isset($group['records'][0])) {
					$data = $group['records'][0]->getData();
					$pool = FieldManager::fetch(array_keys($data));
					self::$_fieldPool += $pool;
				}

				foreach($group['records'] as $entry){
					$data = $entry->getData();

					$xEntry = new XMLElement('entry');
					$xEntry->setAttribute('id', $entry->get('id'));

					if(!empty($associated_sections)) {
						$this->setAssociatedEntryCounts($xEntry, $entry, $associated_sections);
					}

					if($this->_can_process_system_parameters) {
						$this->processSystemParameters($entry, $param_pool);
					}

					foreach($data as $field_id => $values){
						if(!isset(self::$_fieldPool[$field_id]) || !is_object(self::$_fieldPool[$field_id])) {
							self::$_fieldPool[$field_id] =& FieldManager::fetch($field_id);
						}

						$this->processOutputParameters($entry, $param_pool, $field_id, $values);

						if (!$this->_param_output_only) if (is_array($this->dsParamINCLUDEDELEMENTS)) foreach ($this->dsParamINCLUDEDELEMENTS as $handle) {
							list($handle, $mode) = preg_split('/\s*:\s*/', $handle, 2);
							if(self::$_fieldPool[$field_id]->get('element_name') == $handle) {
								self::$_fieldPool[$field_id]->appendFormattedElement($xEntry, $values, ($this->dsParamHTMLENCODE ? true : false), $mode, $entry->get('id'));
							}
						}
					}

					if($this->_param_output_only) continue;

					if(is_array($this->dsParamINCLUDEDELEMENTS) && in_array('system:date', $this->dsParamINCLUDEDELEMENTS)){
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

			if(is_array($group['groups']) && !empty($group['groups'])){
				foreach($group['groups'] as $element => $group){
					foreach($group as $g) {
						$this->processRecordGroup($xGroup, $element, $g, $param_pool);
					}
				}
			}

			if(!$this->_param_output_only) $result->appendChild($xGroup);
		}

	}
