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
 * @since Symphony 3.0.0, it is abstract
 * @since Symphony 2.3
 * @link http://getsymphony.com/learn/concepts/view/data-sources/
 */
abstract class SectionDatasource extends Datasource
{
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
        'system:creation-date',
        'system:modification-date',
        'system:date' // deprecated
    );

    /**
     * If this Datasource requires System Parameters to be output, this function
     * will return true, otherwise false.
     *
     * @return boolean
     */
    public function canProcessSystemParameters()
    {
        if (!is_array($this->dsParamPARAMOUTPUT)) {
            return false;
        }

        foreach (self::$_system_parameters as $system_parameter) {
            if (in_array($system_parameter, $this->dsParamPARAMOUTPUT) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Given a name for the group, and an associative array that
     * contains three keys, `attr`, `records` and `groups`. Grouping
     * of Entries is done by the grouping Field at a PHP level, not
     * through the Database.
     *
     * @param string $element
     *  The name for the XML node for this group
     * @param array $group
     *  An associative array of the group data, includes `attr`, `records`
     *  and `groups` keys.
     * @throws Exception
     * @return XMLElement
     */
    public function processRecordGroup($element, array $group)
    {
        $xGroup = new XMLElement($element, null, $group['attr']);

        if (is_array($group['records']) && !empty($group['records'])) {
            if (isset($group['records'][0])) {
                $data = $group['records'][0]->getData();
                $pool = (new FieldManager)
                    ->select()
                    ->fields(array_keys($data))
                    ->execute()
                    ->rowsIndexedByColumn('id');
                self::$_fieldPool += $pool;
            }

            foreach ($group['records'] as $entry) {
                $xEntry = $this->processEntry($entry);

                if ($xEntry instanceof XMLElement) {
                    $xGroup->appendChild($xEntry);
                }
            }
        }

        if (is_array($group['groups']) && !empty($group['groups'])) {
            foreach ($group['groups'] as $element => $group) {
                foreach ($group as $g) {
                    $xGroup->appendChild(
                        $this->processRecordGroup($element, $g)
                    );
                }
            }
        }

        if (!$this->_param_output_only) {
            return $xGroup;
        }
    }

    /**
     * Given an Entry object, this function will generate an XML representation
     * of the Entry to be returned. It will also add any parameters selected
     * by this datasource to the parameter pool.
     *
     * @param Entry $entry
     * @throws Exception
     * @return XMLElement|boolean
     *  Returns boolean when only parameters are to be returned.
     */
    public function processEntry(Entry $entry)
    {
        $data = $entry->getData();

        $xEntry = new XMLElement('entry');
        $xEntry->setAttribute('id', $entry->get('id'));

        if (!empty($this->_associated_sections)) {
            $this->setAssociatedEntryCounts($xEntry, $entry);
        }

        if ($this->_can_process_system_parameters) {
            $this->processSystemParameters($entry);
        }

        foreach ($data as $field_id => $values) {
            if (!isset(self::$_fieldPool[$field_id]) || !is_object(self::$_fieldPool[$field_id])) {
                self::$_fieldPool[$field_id] = (new FieldManager)->select()->field($field_id)->execute()->next();
            }

            $this->processOutputParameters($entry, $field_id, $values);

            if (!$this->_param_output_only) {
                foreach ($this->dsParamINCLUDEDELEMENTS as $handle) {
                    list($handle, $mode) = preg_split('/\s*:\s*/', $handle, 2);

                    if (self::$_fieldPool[$field_id]->get('element_name') == $handle) {
                        try {
                            self::$_fieldPool[$field_id]->appendFormattedElement($xEntry, $values, ($this->dsParamHTMLENCODE === 'yes' ? true : false), $mode, $entry->get('id'));
                        } catch (Exception $ex) {
                            if (Symphony::Log()) {
                                Symphony::Log()->pushExceptionToLog($ex, true);
                            }
                            $this->appendFormattedError($handle, $xEntry, $ex);
                        } catch (Throwable $ex) {
                            if (Symphony::Log()) {
                                Symphony::Log()->pushExceptionToLog($ex, true);
                            }
                            $this->appendFormattedError($handle, $xEntry, $ex);
                        }
                    }
                }
            }
        }

        if ($this->_param_output_only) {
            return true;
        }

        // This is deprecated and will be removed in Symphony 3.0.0
        if (in_array('system:date', $this->dsParamINCLUDEDELEMENTS)) {
            if (Symphony::Log()) {
                Symphony::Log()->pushDeprecateWarningToLog('system:date', 'system:creation-date` or `system:modification-date', array(
                    'message-format' => __('The `%s` data source field is deprecated.')
                ));
            }
            $xDate = new XMLElement('system-date');
            $xDate->appendChild(
                General::createXMLDateObject(
                    DateTimeObj::get('U', $entry->get('creation_date')),
                    'created'
                )
            );
            $xDate->appendChild(
                General::createXMLDateObject(
                    DateTimeObj::get('U', $entry->get('modification_date')),
                    'modified'
                )
            );
            $xEntry->appendChild($xDate);
        }

        return $xEntry;
    }

    /**
     * Given an handle, it will create a XMLElement from its value.
     * The Throwable's message will be put into an error node.
     * The newly created XMLElement will then be appended to the $xEntry XMLElement.
     *
     * @param string $handle
     *  The name of the new XMLElement
     * @param XMLElement $xEntry
     *  The XMLElement to append a child intro
     * @param Throwable $ex
     *  The Throwable's message to use
     * @return void
     */
    private function appendFormattedError($handle, XMLElement &$xEntry, $ex)
    {
        $xmlField = new XMLElement($handle);
        $xmlField->appendChild(new XMLElement('error', General::wrapInCDATA($ex->getMessage())));
        $xEntry->appendChild($xmlField);
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
     * @throws Exception
     */
    public function setAssociatedEntryCounts(XMLElement &$xEntry, Entry $entry)
    {
        $associated_entry_counts = $entry->fetchAllAssociatedEntryCounts($this->_associated_sections);

        if (!empty($associated_entry_counts)) {
            foreach ($associated_entry_counts as $section_id => $fields) {
                foreach ($this->_associated_sections as $section) {
                    if (General::intval($section['id']) !== General::intval($section_id)) {
                        continue;
                    }

                    // For each related field show the count (#2083)
                    foreach ($fields as $field_id => $count) {
                        $field_handle = FieldManager::fetchHandleFromID($field_id);
                        $section_handle = $section['handle'];
                        // Make sure attribute does not begin with a digit
                        // @deprecated This needs to be removed in Symphony 4.0.0
                        if (preg_match('/^[0-9]/', $section_handle)) {
                            $section_handle = 'x-' . $section_handle;
                        }
                        if ($field_handle) {
                            $xEntry->setAttribute($section_handle . '-' . $field_handle, (string)$count);
                        }
                    }
                }
            }
        }
    }

    /**
     * Given an Entry object, this function will iterate over the `dsParamPARAMOUTPUT`
     * setting to see any of the Symphony system parameters need to be set.
     * The current system parameters supported are `system:id`, `system:author`,
     * `system:creation-date` and `system:modification-date`.
     * If these parameters are found, the result is added
     * to the `$param_pool` array using the key, `ds-datasource-handle.parameter-name`
     * For the moment, this function also supports the pre Symphony 2.3 syntax,
     * `ds-datasource-handle` which did not support multiple parameters.
     *
     * @param Entry $entry
     *  The Entry object that contains the values that may need to be added
     *  into the parameter pool.
     */
    public function processSystemParameters(Entry $entry)
    {
        if (!isset($this->dsParamPARAMOUTPUT)) {
            return;
        }

        // Support the legacy parameter `ds-datasource-handle`
        $key = 'ds-' . $this->dsParamROOTELEMENT;

        foreach ($this->dsParamPARAMOUTPUT as $param) {
            // The new style of paramater is `ds-datasource-handle.field-handle`
            $param_key = $key . '.' . str_replace(':', '-', $param);

            if ($param === 'system:id') {
                $this->_param_pool[$param_key][] = $entry->get('id');

            } elseif ($param === 'system:author') {
                $this->_param_pool[$param_key][] = $entry->get('author_id');

            } elseif ($param === 'system:creation-date' || $param === 'system:date') {
                if ($param === 'system:date' && Symphony::Log()) {
                    Symphony::Log()->pushDeprecateWarningToLog('system:date', 'system:creation-date', array(
                        'message-format' => __('The `%s` data source output parameter is deprecated.')
                    ));
                }
                $this->_param_pool[$param_key][] = $entry->get('creation_date');

            } elseif ($param === 'system:modification-date') {
                $this->_param_pool[$param_key][] = $entry->get('modification_date');

            }
        }
    }

    /**
     * Given an Entry object, a `$field_id` and an array of `$data`, this
     * function iterates over the `dsParamPARAMOUTPUT` and will call the
     * field's (identified by `$field_id`) `getParameterPoolValue` function
     * to add parameters to the `$this->_param_pool`.
     *
     * @param Entry $entry
     * @param integer $field_id
     * @param array $data
     */
    public function processOutputParameters(Entry $entry, $field_id, array $data)
    {
        if (!isset($this->dsParamPARAMOUTPUT)) {
            return;
        }

        // Support the legacy parameter `ds-datasource-handle`
        $key = 'ds-' . $this->dsParamROOTELEMENT;

        foreach ($this->dsParamPARAMOUTPUT as $param) {
            if (self::$_fieldPool[$field_id]->get('element_name') !== $param) {
                continue;
            }

            // The new style of parameter is `ds-datasource-handle.field-handle`
            $param_key = $key . '.' . str_replace(':', '-', $param);

            if (!isset($this->_param_pool[$param_key]) || !is_array($this->_param_pool[$param_key])) {
                $this->_param_pool[$param_key] = array();
            }

            try {
                $param_pool_values = self::$_fieldPool[$field_id]->getParameterPoolValue($data, $entry->get('id'));
            } catch (Exception $ex) {
                if (Symphony::Log()) {
                    Symphony::Log()->pushExceptionToLog($ex, true);
                }
                $param_pool_values = ['error' => $ex->getMessage()];
            } catch (Throwable $ex) {
                if (Symphony::Log()) {
                    Symphony::Log()->pushExceptionToLog($ex, true);
                }
                $param_pool_values = ['error' => $ex->getMessage()];
            }

            if (is_array($param_pool_values)) {
                $this->_param_pool[$param_key] = array_merge($param_pool_values, $this->_param_pool[$param_key]);

            } elseif (!is_null($param_pool_values)) {
                $this->_param_pool[$param_key][] = $param_pool_values;

            }
        }
    }

    /**
     * This function iterates over `dsParamFILTERS` and appends the relevant
     * where and join operations.
     * This SQL is generated with the Field's query builder.
     *
     * @see Field::getEntryQueryFieldAdapter()
     * @param EntryQuery $entryQuery
     * @throws Exception
     */
    public function processFilters(&$entryQuery)
    {
        if (!is_array($this->dsParamFILTERS) || empty($this->dsParamFILTERS)) {
            return;
        }

        $numericFilters = array_filter(array_keys($this->dsParamFILTERS), 'is_numeric');
        if (!empty($numericFilters)) {
            $pool = (new FieldManager)
                ->select()
                ->fields($numericFilters)
                ->execute()
                ->rowsIndexedByColumn('id');
            self::$_fieldPool += $pool;
        }

        foreach ($this->dsParamFILTERS as $field_id => $filter) {
            if ((is_array($filter) && empty($filter)) || trim($filter) == '') {
                continue;
            }

            if (!is_array($filter)) {
                $filter_type = Datasource::determineFilterType($filter);
                $value = Datasource::splitFilter($filter_type, $filter);
            } else {
                $filter_type = Datasource::FILTER_OR;
                $value = $filter;
            }

            if (!in_array($field_id, self::$_system_parameters) && $field_id != 'id' && !(self::$_fieldPool[$field_id] instanceof Field)) {
                throw new Exception(
                    __(
                        'Error creating field object with id %1$d, for filtering in data source %2$s. Check this field exists.',
                        array($field_id, '<code>' . $this->dsParamROOTELEMENT . '</code>')
                    )
                );
            }

            // Support system:id as well as the old 'id'. #1691
            if ($field_id === 'system:id' || $field_id === 'id') {
                if ($field_id === 'id' && Symphony::Log()) {
                    Symphony::Log()->pushDeprecateWarningToLog('id', 'system:id', array(
                        'message-format' => __('The `%s` data source filter is deprecated.')
                    ));
                }
                $op = $filter_type === Datasource::FILTER_AND ? 'and' : 'or';
                $entryQuery->filter('system:id', $value, $op);
            // Dates
            } elseif ($field_id === 'system:creation-date' || $field_id === 'system:modification-date' || $field_id === 'system:date') {
                if ($field_id === 'system:date' && Symphony::Log()) {
                    Symphony::Log()->pushDeprecateWarningToLog('system:date', 'system:creation-date` or `system:modification-date', array(
                        'message-format' => __('The `%s` data source filter is deprecated.')
                    ));
                    $field_id = 'system:creation-date';
                }
                $op = $filter_type === Datasource::FILTER_AND ? 'and' : 'or';
                $entryQuery->filter($field_id, $value, $op);
            // Field with EQFA
            } elseif (self::$_fieldPool[$field_id]->getEntryQueryFieldAdapter()) {
                $op = $filter_type === Datasource::FILTER_AND ? 'and' : 'or';
                $entryQuery->filter(self::$_fieldPool[$field_id], $value, $op);
            // Compat layer with the old API
            } else {
                $where = '';
                $joins = '';
                if (!self::$_fieldPool[$field_id]->buildDSRetrievalSQL($value, $joins, $where, ($filter_type == Datasource::FILTER_AND ? true : false))) {
                    $this->_force_empty_result = true;
                    return;
                }

                if ($joins) {
                    $joins = $entryQuery->replaceTablePrefix($joins);
                    $entryQuery->unsafe()->unsafeAppendSQLPart('join', $joins);
                }
                if ($where) {
                    $where = $entryQuery->replaceTablePrefix($where);
                    $wherePrefix = $entryQuery->containsSQLParts('where') ? '' : 'WHERE 1 = 1';
                    $entryQuery->unsafe()->unsafeAppendSQLPart('where', "$wherePrefix $where");
                }

                if (self::$_fieldPool[$field_id]->requiresSQLGrouping()) {
                    $entryQuery->distinct();
                }
            }
        }
    }

    public function execute(array &$param_pool = null)
    {
        $result = new XMLElement($this->dsParamROOTELEMENT);

        try {
            $result = $this->generate($param_pool);
        } catch (FrontendPageNotFoundException $e) {
            // Work around. This ensures the 404 page is displayed and
            // is not picked up by the default catch() statement below
            FrontendPageNotFoundExceptionRenderer::render($e);
        } catch (Exception $e) {
            $result->appendChild(new XMLElement('error',
                General::wrapInCDATA($e->getMessage() . ' on ' . $e->getLine() . ' of file ' . $e->getFile())
            ));
            return $result;
        }

        if ($this->_force_empty_result) {
            $result = $this->emptyXMLSet();
        }

        if ($this->_negate_result) {
            $result = $this->negateXMLSet();
        }

        return $result;
    }

    /**
     * Creates the XML representation of this data source.
     *
     * @param array $param_pool
     * @return XMLElement
     */
    public function generate(array &$param_pool = null)
    {
        $result = new XMLElement($this->dsParamROOTELEMENT);
        $this->_param_pool = $param_pool;
        $requiresPagination = (!isset($this->dsParamPAGINATERESULTS) ||
            $this->dsParamPAGINATERESULTS === 'yes')
            && isset($this->dsParamLIMIT) && General::intval($this->dsParamLIMIT) >= 0;

        $section = (new SectionManager)
            ->select()
            ->section($this->getSource())
            ->execute()
            ->next();

        if (!$section) {
            $about = $this->about();
            throw new Exception(
                __(
                    'The Section, %s, associated with the Data source, %s, could not be found.',
                    [$this->getSource(), '<code>' . $about['name'] . '</code>']
                )
            );
        }

        $sectioninfo = new XMLElement('section', General::sanitize($section->get('name')), array(
            'id' => $section->get('id'),
            'handle' => $section->get('handle')
        ));

        if ($this->_force_empty_result == true) {
            if ($this->dsParamREDIRECTONREQUIRED === 'yes') {
                throw new FrontendPageNotFoundException;
            }

            $this->_force_empty_result = false; //this is so the section info element doesn't disappear.
            $error = new XMLElement('error', __("Data source not executed, required parameter is missing."), array(
                'required-param' => $this->dsParamREQUIREDPARAM
            ));
            $result->appendChild($error);
            $result->prependChild($sectioninfo);

            return $result;
        }

        if ($this->_negate_result == true) {
            if ($this->dsParamREDIRECTONFORBIDDEN === 'yes') {
                throw new FrontendPageNotFoundException;
            }

            $this->_negate_result = false; //this is so the section info element doesn't disappear.
            $result = $this->negateXMLSet();
            $result->prependChild($sectioninfo);

            return $result;
        }

        if (is_array($this->dsParamINCLUDEDELEMENTS)) {
            $include_pagination_element = in_array('system:pagination', $this->dsParamINCLUDEDELEMENTS);
        } else {
            $this->dsParamINCLUDEDELEMENTS = array();
        }

        if (isset($this->dsParamPARAMOUTPUT) && !is_array($this->dsParamPARAMOUTPUT)) {
            $this->dsParamPARAMOUTPUT = array($this->dsParamPARAMOUTPUT);
        }

        $this->_can_process_system_parameters = $this->canProcessSystemParameters();

        // combine `INCLUDEDELEMENTS`, `PARAMOUTPUT` and `GROUP` into an
        // array of field handles to optimise the `EntryManager` queries
        $datasource_schema = $this->dsParamINCLUDEDELEMENTS;

        if (is_array($this->dsParamPARAMOUTPUT)) {
            $datasource_schema = array_merge($datasource_schema, $this->dsParamPARAMOUTPUT);
        }

        if ($this->dsParamGROUP) {
            $datasource_schema[] = FieldManager::fetchHandleFromID($this->dsParamGROUP);
        }

        // Create our query object
        $entriesQuery = (new EntryManager)
            ->select($datasource_schema)
            ->section($this->getSource());

        // Process Filters
        $this->processFilters($entriesQuery);

        // Process Sorting
        $entriesQuery->sort((string)$this->dsParamSORT, $this->dsParamORDER);

        // Configure pagination in the query
        if ($requiresPagination) {
            $entriesQuery->paginate($this->dsParamSTARTPAGE, $this->dsParamLIMIT);
        }

        // Execute
        $pagination = $entriesQuery->execute()->pagination();
        $entries = $pagination->rows();

        /**
         * Immediately after building entries allow modification of the Data Source entries array
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

        $entries_per_page = $requiresPagination
            ? $pagination->pageSize()
            : $pagination->totalEntries();

        if (empty($entries)) {
            if ($this->dsParamREDIRECTONEMPTY === 'yes') {
                throw new FrontendPageNotFoundException;
            }

            $this->_force_empty_result = false;
            $result = $this->emptyXMLSet();
            $result->prependChild($sectioninfo);

            if ($include_pagination_element) {
                $pagination_element = General::buildPaginationElement(0, 0, $entries_per_page);

                if ($pagination_element instanceof XMLElement && $result instanceof XMLElement) {
                    $result->prependChild($pagination_element);
                }
            }
        } else {
            if (!$this->_param_output_only) {
                $result->appendChild($sectioninfo);

                if ($include_pagination_element) {
                    $pagination_element = General::buildPaginationElement(
                        $pagination->totalEntries(),
                        $pagination->totalPages(),
                        $entries_per_page,
                        $pagination->currentPage()
                    );

                    if ($pagination_element instanceof XMLElement && $result instanceof XMLElement) {
                        $result->prependChild($pagination_element);
                    }
                }
            }

            if (!isset($this->dsParamASSOCIATEDENTRYCOUNTS) || $this->dsParamASSOCIATEDENTRYCOUNTS === 'yes') {
                $this->_associated_sections = $section->fetchChildAssociations();
            }

            // If the datasource require's GROUPING
            if (isset($this->dsParamGROUP)) {
                if (!isset(self::$_fieldPool[$this->dsParamGROUP])) {
                    self::$_fieldPool[$this->dsParamGROUP] = (new FieldManager)
                        ->select()
                        ->field($this->dsParamGROUP)
                        ->execute()
                        ->next();
                }
                if (self::$_fieldPool[$this->dsParamGROUP] == null) {
                    throw new SymphonyException(vsprintf("The field used for grouping '%s' cannot be found.", $this->dsParamGROUP));
                }

                $groups = self::$_fieldPool[$this->dsParamGROUP]->groupRecords($entries);

                foreach ($groups as $element => $group) {
                    foreach ($group as $g) {
                        $result->appendChild(
                            $this->processRecordGroup($element, $g)
                        );
                    }
                }
            } else {
                if (isset($entries[0])) {
                    $data = $entries[0]->getData();
                    if (!empty($data)) {
                        $pool = (new FieldManager)
                            ->select()
                            ->fields(array_keys($data))
                            ->execute()
                            ->rowsIndexedByColumn('id');
                        self::$_fieldPool += $pool;
                    }
                }

                foreach ($entries as $entry) {
                    $xEntry = $this->processEntry($entry);

                    if ($xEntry instanceof XMLElement) {
                        $result->appendChild($xEntry);
                    }
                }
            }
        }

        $param_pool = $this->_param_pool;

        return $result;
    }
}
