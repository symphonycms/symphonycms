<?php

/**
 * @package content
 */
/**
 * The Publish page is where the majority of an Authors time will
 * be spent in Symphony with adding, editing and removing entries
 * from Sections. This Page controls the entries table as well as
 * the Entry creation screens.
 */

class contentPublish extends AdministrationPage
{
    public $_errors = array();

    public function sort(&$sort, &$order, $params)
    {
        $section = $params['current-section'];

        // If `?unsort` is appended to the URL, then sorting is reverted
        // to 'none', aka. by 'entry-id'.
        if ($params['unsort']) {
            $section->setSortingField('id', false);
            $section->setSortingOrder('desc');

            redirect(Administration::instance()->getCurrentPageURL());
        }

        // By default, sorting information are retrieved from
        // the filesystem and stored inside the `Configuration` object
        if (is_null($sort) && is_null($order)) {
            $sort = $section->getSortingField();
            $order = $section->getSortingOrder();

            // Set the sorting in the `EntryManager` for subsequent use
            EntryManager::setFetchSorting($sort, $order);
        } else {
            // Ensure that this field is infact sortable, otherwise
            // fallback to IDs
            if (($field = FieldManager::fetch($sort)) instanceof Field && !$field->isSortable()) {
                $sort = $section->getDefaultSortingField();
            }

            // If the sort order or direction differs from what is saved,
            // update the config file and reload the page
            if ($sort != $section->getSortingField() || $order != $section->getSortingOrder()) {
                $section->setSortingField($sort, false);
                $section->setSortingOrder($order);

                if ($params['filters']) {
                    $params['filters'] = '?' . trim($params['filters'], '&amp;');
                }

                redirect(Administration::instance()->getCurrentPageURL() . $params['filters']);
            }

            // If the sort order or direction remains the same, reload the page
            if ($sort == $section->getSortingField() && $order == $section->getSortingOrder()) {
                if ($params['filters']) {
                    $params['filters'] = '?' . trim($params['filters'], '&amp;');
                }

                redirect(Administration::instance()->getCurrentPageURL() . $params['filters']);
            }
        }

    }

    /**
     * Append filtering interface
     */
    public function createFilteringInterface()
    {
        //Check if section has filtering enabled
        $context = $this->getContext();
        $handle = $context['section_handle'];
        $section_id = SectionManager::fetchIDFromHandle($handle);
        $section = SectionManager::fetch($section_id);
        $filter = $section->get('filter');
        $count = EntryManager::fetchCount($section_id);

        if ($filter !== 'no' && $count > 1) {
            $drawer = Widget::Drawer('filtering-' . $section_id, __('Filter Entries'), $this->createFilteringDrawer($section));
            $drawer->addClass('drawer-filtering');
            $this->insertDrawer($drawer);
        }
    }

    /**
     * Create filtering drawer
     */
    public function createFilteringDrawer($section)
    {
        $this->filteringForm = Widget::Form(null, 'get', 'filtering');
        $this->createFilteringDuplicator($section);

        return $this->filteringForm;
    }

    public function createFilteringDuplicator($section)
    {
        $div = new XMLElement('div');
        $div->setAttribute('class', 'frame filters-duplicator');
        $div->setAttribute('data-interactive', 'data-interactive');

        $ol = new XMLElement('ol');
        $ol->setAttribute('data-add', __('Add filter'));
        $ol->setAttribute('data-remove', __('Clear filter'));
        $ol->setAttribute('data-empty', __('No filters applied yet.'));

        $this->createFieldFilters($ol, $section);
        $this->createSystemDateFilters($ol);

        $div->appendChild($ol);
        $this->filteringForm->appendChild($div);
    }

    private function createFieldFilters(&$wrapper, $section)
    {
        $filters = $_GET['filter'];

        foreach ($section->fetchFilterableFields() as $field) {
            if (!$field->canPublishFilter()) {
                continue;
            }

            $filter = $filters[$field->get('element_name')];

            // Filter data
            $data = array();
            $data['type'] = $field->get('element_name');
            $data['name'] = $field->get('label');
            $data['filter'] = $filter;
            $data['instance'] = 'unique';
            $data['search'] = $field->fetchSuggestionTypes();
            $data['operators'] = $field->fetchFilterableOperators();
            $data['comparisons'] = $this->createFilterComparisons($data);
            $data['query'] = $this->getFilterQuery($data);
            $data['field-id'] = $field->get('id');

            // Add existing filter
            if (isset($filter)) {
                $this->createFilter($wrapper, $data);
            }

            // Add filter template
            $data['instance'] = 'unique template';
            $data['query'] = '';
            $this->createFilter($wrapper, $data);
        }
    }

    private function createSystemDateFilters(&$wrapper)
    {
        $filters = $_GET['filter'];
        $dateField = new FieldDate;

        $fields = array(
            array(
                'type' => 'system:creation-date',
                'label' => __('System Creation Date')
            ),
            array(
                'type' => 'system:modification-date',
                'label' => __('System Modification Date')
            )
        );

        foreach ($fields as $field) {
            $filter = $filters[$field['type']];

            // Filter data
            $data = array();
            $data['type'] = $field['type'];
            $data['name'] = $field['label'];
            $data['filter'] = $filter;
            $data['instance'] = 'unique';
            $data['search'] = $dateField->fetchSuggestionTypes();
            $data['operators'] = $dateField->fetchFilterableOperators();
            $data['comparisons'] = $this->createFilterComparisons($data);
            $data['query'] = $this->getFilterQuery($data);

            // Add existing filter
            if (isset($filter)) {
                $this->createFilter($wrapper, $data);
            }

            // Add filter template
            $data['instance'] = 'unique template';
            $data['query'] = '';
            $this->createFilter($wrapper, $data);
        }
    }

    private function createFilter(&$wrapper, $data)
    {
        $li = new XMLElement('li');
        $li->setAttribute('class', $data['instance']);
        $li->setAttribute('data-type', $data['type']);

        // Header
        $li->appendChild(new XMLElement('header', $data['name'], array(
            'data-name' => $data['name']
        )));

        // Settings
        $div = new XMLElement('div', null, array('class' => 'two columns'));

        // Comparisons
        $label = Widget::Label();
        $label->setAttribute('class', 'column secondary');

        $select = Widget::Select($data['type'] . '-comparison', $data['comparisons'], array(
            'class' => 'comparison'
        ));

        $label->appendChild($select);
        $div->appendChild($label);

        // Query
        $label = Widget::Label();
        $label->setAttribute('class', 'column primary');

        $input = Widget::Input($data['type'], $data['query'], 'text', array(
            'placeholder' => __('Type and hit enter to apply filter…'),
            'autocomplete' => 'off'
        ));
        $input->setAttribute('class', 'filter');
        $label->appendChild($input);

        $this->createFilterSuggestions($label, $data);

        $div->appendChild($label);
        $li->appendChild($div);
        $wrapper->appendChild($li);
    }

    private function createFilterComparisons($data)
    {
        // Default comparison
        $comparisons = array();

        // Custom field comparisons
        foreach ($data['operators'] as $operator) {
            $filter = trim($operator['filter']);

            $comparisons[] = array(
                $filter,
                (!empty($filter) && strpos($data['filter'], $filter) === 0),
                __($operator['title'])
            );
        }

        return $comparisons;
    }

    private function createFilterSuggestions(&$wrapper, $data)
    {
        $ul = new XMLElement('ul');
        $ul->setAttribute('class', 'suggestions');
        $ul->setAttribute('data-field-id', $data['field-id']);
        $ul->setAttribute('data-associated-ids', '0');
        $ul->setAttribute('data-search-types', implode($data['search'], ','));

        // Add default filter help
        $operator = array(
            'filter' => 'is',
            'help' => __('Find values that are an exact match for the given string.')
        );
        $this->createFilterHelp($ul, $operator);

        // Add custom filter help
        foreach ($data['operators'] as $operator) {
            $this->createFilterHelp($ul, $operator);
        }

        $wrapper->appendChild($ul);
    }

    private function createFilterHelp(&$wrapper, $operator) {
        if(empty($operator['help'])) {
            return;
        }

        $li = new XMLElement('li', __('Comparison mode') . ': ' . $operator['help'], array(
            'class' => 'help',
            'data-comparison' => trim($operator['filter'])
        ));

        $wrapper->appendChild($li);
    }

    private function getFilterQuery($data)
    {
        $query = $data['filter'];

        foreach ($data['operators'] as $operator) {
            $filter = trim($operator['filter']);

            if (!empty($filter) && strpos($data['filter'], $filter) === 0) {
                $query = substr($data['filter'], strlen($filter));
            }
        }

        return (string)$query;
    }

    public function build(array $context = array())
    {
        $section_id = SectionManager::fetchIDFromHandle($context['section_handle']);

        if($section_id) {
            $context['associations'] = array(
                'parent' => SectionManager::fetchParentAssociations($section_id),
                'child' => SectionManager::fetchChildAssociations($section_id)
            );
        }

        return parent::build($context);
    }

    public function action()
    {
        $this->__switchboard('action');
    }

    public function __switchboard($type = 'view')
    {
        $function = ($type == 'action' ? '__action' : '__view') . ucfirst($this->_context['page']);

        if (!method_exists($this, $function)) {
            // If there is no action function, just return without doing anything
            if ($type == 'action') {
                return;
            }

            Administration::instance()->errorPageNotFound();
        }

        // Is this request allowed by server?
        if ($this->isRequestValid() === false) {
            $this->pageAlert(__('This request exceeds the maximum allowed request size of %s specified by your host.', array(
                    ini_get('post_max_size')
                )),
                Alert::ERROR
            );
        }
        $this->$function();
    }

    public function view()
    {
        $this->__switchboard();
    }

    public function __viewIndex()
    {
        if (!$section_id = SectionManager::fetchIDFromHandle($this->_context['section_handle'])) {
            Administration::instance()->throwCustomError(
                __('The Section, %s, could not be found.', array('<code>' . $this->_context['section_handle'] . '</code>')),
                __('Unknown Section'),
                Page::HTTP_STATUS_NOT_FOUND
            );
        }
        else if (!is_writable(CONFIG)) {
            $this->pageAlert(__('The Symphony configuration file, %s, is not writable. The sort order cannot be modified.', array('<code>/manifest/config.php</code>')), Alert::NOTICE);
        }

        $section = SectionManager::fetch($section_id);

        $this->setPageType('table');
        $this->setTitle(__('%1$s &ndash; %2$s', array($section->get('name'), __('Symphony'))));

        $filters = array();
        $filter_querystring = $prepopulate_querystring = $where = $joins = null;
        $current_page = (isset($_REQUEST['pg']) && is_numeric($_REQUEST['pg']) ? max(1, intval($_REQUEST['pg'])) : 1);

        if (isset($_REQUEST['filter'])) {
            // legacy implementation, convert single filter to an array
            // split string in the form ?filter=handle:value
            if (!is_array($_REQUEST['filter'])) {
                list($field_handle, $filter_value) = explode(':', $_REQUEST['filter'], 2);
                $filters[$field_handle] = rawurldecode($filter_value);
            } else {
                $filters = $_REQUEST['filter'];
            }

            foreach ($filters as $handle => $value) {
                // Handle multiple values through filtering. RE: #2290
                if ((is_array($value) && empty($value)) || trim($value) == '') {
                    continue;
                }

                if (!is_array($value)) {
                    $filter_type = Datasource::determineFilterType($value);
                    $value = preg_split('/'.($filter_type == Datasource::FILTER_AND ? '\+' : '(?<!\\\\),').'\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
                    $value = array_map('trim', $value);
                    $value = array_map(array('Datasource', 'removeEscapedCommas'), $value);
                }

                // Handle date meta data #2003
                $handle = Symphony::Database()->cleanValue($handle);
                if (in_array($handle, array('system:creation-date', 'system:modification-date'))) {
                    $date_joins = '';
                    $date_where = '';
                    $date = new FieldDate();
                    $date->buildDSRetrievalSQL($value, $date_joins, $date_where, ($filter_type == Datasource::FILTER_AND ? true : false));

                    // Replace the date field where with the `creation_date` or `modification_date`.
                    $date_where = preg_replace('/`t\d+`.date/', ($field_id !== 'system:modification-date') ? '`e`.creation_date_gmt' : '`e`.modification_date_gmt', $date_where);
                    $where .= $date_where;

                } else {
                    // Handle normal fields
                    $field_id = FieldManager::fetchFieldIDFromElementName(
                        $handle,
                        $section->get('id')
                    );

                    $field = FieldManager::fetch($field_id);
                    if ($field instanceof Field) {
                        $field->buildDSRetrievalSQL($value, $joins, $where, ($filter_type == Datasource::FILTER_AND ? true : false));

                        $value = implode(',' , $value);
                        $encoded_value = rawurlencode($value);
                        $filter_querystring .= sprintf("filter[%s]=%s&amp;", $handle, $encoded_value);

                        // Some fields require that prepopulation be done via ID. RE: #2331
                        if (method_exists($field, 'fetchIDfromValue')) {
                            $encoded_value = $field->fetchIDfromValue($value);
                        }
                        $prepopulate_querystring .= sprintf("prepopulate[%d]=%s&amp;", $field_id, $encoded_value);
                    } else {
                        unset($filters[$handle]);
                    }
                }
            }

            $filter_querystring = preg_replace("/&amp;$/", '', $filter_querystring);
            $prepopulate_querystring = preg_replace("/&amp;$/", '', $prepopulate_querystring);
        }

        Sortable::initialize($this, $entries, $sort, $order, array(
            'current-section' => $section,
            'filters' => ($filter_querystring ? "&amp;" . $filter_querystring : ''),
            'unsort' => isset($_REQUEST['unsort'])
        ));

        $this->Form->setAttribute('action', Administration::instance()->getCurrentPageURL(). '?pg=' . $current_page.($filter_querystring ? "&amp;" . $filter_querystring : ''));

        // Build filtering interface
        $this->createFilteringInterface();

        $subheading_buttons = array(
            Widget::Anchor(__('Create New'), Administration::instance()->getCurrentPageURL().'new/'.($prepopulate_querystring ? '?' . $prepopulate_querystring : ''), __('Create a new entry'), 'create button', null, array('accesskey' => 'c'))
        );

        // Only show the Edit Section button if the Author is a developer. #938 ^BA
        if (Symphony::Author()->isDeveloper()) {
            array_unshift($subheading_buttons, Widget::Anchor(__('Edit Section'), SYMPHONY_URL . '/blueprints/sections/edit/' . $section_id . '/', __('Edit Section Configuration'), 'button'));
        }

        $this->appendSubheading($section->get('name'), $subheading_buttons);

        /**
         * Allows adjustments to be made to the SQL where and joins statements
         * before they are used to fetch the entries for the page
         *
         * @delegate AdjustPublishFiltering
         * @since Symphony 2.3.3
         * @param string $context
         * '/publish/'
         * @param integer $section_id
         * An array of the current columns, passed by reference
         * @param string $where
         * The current where statement, or null if not set
         * @param string $joins
         */
        Symphony::ExtensionManager()->notifyMembers('AdjustPublishFiltering', '/publish/', array('section-id' => $section_id, 'where' => &$where, 'joins' => &$joins));

        // Check that the filtered query fails that the filter is dropped and an
        // error is logged. #841 ^BA
        try {
            $entries = EntryManager::fetchByPage($current_page, $section_id, Symphony::Configuration()->get('pagination_maximum_rows', 'symphony'), $where, $joins, true);
        } catch (DatabaseException $ex) {
            $this->pageAlert(__('An error occurred while retrieving filtered entries. Showing all entries instead.'), Alert::ERROR);
            $filter_querystring = null;
            Symphony::Log()->pushToLog(sprintf(
                    '%s - %s%s%s',
                    $section->get('name') . ' Publish Index',
                    $ex->getMessage(),
                    ($ex->getFile() ? " in file " .  $ex->getFile() : null),
                    ($ex->getLine() ? " on line " . $ex->getLine() : null)
                ),
                E_NOTICE,
                true
            );
            $entries = EntryManager::fetchByPage($current_page, $section_id, Symphony::Configuration()->get('pagination_maximum_rows', 'symphony'));
        }

        // Flag filtering
        $filter_stats = null;
        if (isset($_REQUEST['filter'])) {
            $filter_stats = new XMLElement('p', '<span>– ' . __('%d of %d entries (filtered)', array($entries['total-entries'], EntryManager::fetchCount($section_id))) . '</span>', array('class' => 'inactive'));
        } else {
            $filter_stats = new XMLElement('p', '<span>– ' . __('%d entries', array($entries['total-entries'])) . '</span>', array('class' => 'inactive'));
        }
        $this->Breadcrumbs->appendChild($filter_stats);

        // Build table
        $visible_columns = $section->fetchVisibleColumns();
        $columns = array();

        if (is_array($visible_columns) && !empty($visible_columns)) {

            foreach ($visible_columns as $column) {
                $columns[] = array(
                    'label' => $column->get('label'),
                    'sortable' => $column->isSortable(),
                    'handle' => $column->get('id'),
                    'attrs' => array(
                        'id' => 'field-' . $column->get('id'),
                        'class' => 'field-' . $column->get('type')
                    )
                );
            }
        } else {
            $columns[] = array(
                'label' => __('ID'),
                'sortable' => true,
                'handle' => 'id'
            );
        }

        $aTableHead = Sortable::buildTableHeaders($columns, $sort, $order, ($filter_querystring) ? "&amp;" . $filter_querystring : '');

        $child_sections = array();
        $associated_sections = $section->fetchAssociatedSections(true);

        if (is_array($associated_sections) && !empty($associated_sections)) {
            foreach ($associated_sections as $key => $as) {
                $child_sections[$key] = SectionManager::fetch($as['child_section_id']);
                $aTableHead[] = array($child_sections[$key]->get('name'), 'col');
            }
        }

        /**
         * Allows the creation of custom table columns for each entry. Called
         * after all the Section Visible columns have been added as well
         * as the Section Associations
         *
         * @delegate AddCustomPublishColumn
         * @since Symphony 2.2
         * @param string $context
         * '/publish/'
         * @param array $tableHead
         * An array of the current columns, passed by reference
         * @param integer $section_id
         * The current Section ID
         */
        Symphony::ExtensionManager()->notifyMembers('AddCustomPublishColumn', '/publish/', array('tableHead' => &$aTableHead, 'section_id' => $section->get('id')));

        // Table Body
        $aTableBody = array();

        if (!is_array($entries['records']) || empty($entries['records'])) {

            $aTableBody = array(
                Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', null, count($aTableHead))), 'odd')
            );
        } else {

            $field_pool = array();

            if (is_array($visible_columns) && !empty($visible_columns)) {
                foreach ($visible_columns as $column) {
                    $field_pool[$column->get('id')] = $column;
                }
            }

            $link_column = array_reverse($visible_columns);
            $link_column = end($link_column);
            reset($visible_columns);

            foreach ($entries['records'] as $entry) {
                $tableData = array();

                // Setup each cell
                if (!is_array($visible_columns) || empty($visible_columns)) {
                    $tableData[] = Widget::TableData(Widget::Anchor($entry->get('id'), Administration::instance()->getCurrentPageURL() . 'edit/' . $entry->get('id') . '/'));
                } else {
                    $link = Widget::Anchor(
                        '',
                        Administration::instance()->getCurrentPageURL() . 'edit/' . $entry->get('id') . '/'.($filter_querystring ? '?' . $prepopulate_querystring : ''),
                        $entry->get('id'),
                        'content'
                    );

                    foreach ($visible_columns as $position => $column) {
                        $data = $entry->getData($column->get('id'));
                        $field = $field_pool[$column->get('id')];

                        $value = $field->prepareTableValue($data, ($column == $link_column) ? $link : null, $entry->get('id'));

                        if (!is_object($value) && (strlen(trim($value)) == 0 || $value == __('None'))) {
                            $value = ($position == 0 ? $link->generate() : __('None'));
                        }

                        if ($value == __('None')) {
                            $tableData[] = Widget::TableData($value, 'inactive field-' . $column->get('type') . ' field-' . $column->get('id'));
                        } else {
                            $tableData[] = Widget::TableData($value, 'field-' . $column->get('type') . ' field-' . $column->get('id'));
                        }

                        unset($field);
                    }
                }

                if (is_array($child_sections) && !empty($child_sections)) {
                    foreach ($child_sections as $key => $as) {
                        $field = FieldManager::fetch((int)$associated_sections[$key]['child_section_field_id']);
                        $parent_section_field_id = (int)$associated_sections[$key]['parent_section_field_id'];

                        if (!is_null($parent_section_field_id)) {
                            $search_value = $field->fetchAssociatedEntrySearchValue(
                                $entry->getData($parent_section_field_id),
                                $parent_section_field_id,
                                $entry->get('id')
                            );
                        } else {
                            $search_value = $entry->get('id');
                        }

                        if (!is_array($search_value)) {
                            $associated_entry_count = $field->fetchAssociatedEntryCount($search_value);

                            $tableData[] = Widget::TableData(
                                Widget::Anchor(
                                    sprintf('%d &rarr;', max(0, intval($associated_entry_count))),
                                    sprintf(
                                        '%s/publish/%s/?filter[%s]=%s',
                                        SYMPHONY_URL,
                                        $as->get('handle'),
                                        $field->get('element_name'),
                                        rawurlencode($search_value)
                                    ),
                                    $entry->get('id'),
                                    'content'
                                )
                            );
                        }
                    }
                }

                /**
                 * Allows Extensions to inject custom table data for each Entry
                 * into the Publish Index
                 *
                 * @delegate AddCustomPublishColumnData
                 * @since Symphony 2.2
                 * @param string $context
                 * '/publish/'
                 * @param array $tableData
                 *  An array of `Widget::TableData`, passed by reference
                 * @param integer $section_id
                 *  The current Section ID
                 * @param Entry $entry_id
                 *  The entry object, please note that this is by error and this will
                 *  be removed in Symphony 2.4. The entry object is available in
                 *  the 'entry' key as of Symphony 2.3.1.
                 * @param Entry $entry
                 *  The entry object for this row
                 */
                Symphony::ExtensionManager()->notifyMembers('AddCustomPublishColumnData', '/publish/', array(
                    'tableData' => &$tableData,
                    'section_id' => $section->get('id'),
                    'entry_id' => $entry,
                    'entry' => $entry
                ));

                $tableData[count($tableData) - 1]->appendChild(Widget::Label(__('Select Entry %d', array($entry->get('id'))), null, 'accessible', null, array(
                    'for' => 'entry-' . $entry->get('id')
                )));
                $tableData[count($tableData) - 1]->appendChild(Widget::Input('items['.$entry->get('id').']', null, 'checkbox', array(
                    'id' => 'entry-' . $entry->get('id')
                )));

                // Add a row to the body array, assigning each cell to the row
                $aTableBody[] = Widget::TableRow($tableData, null, 'id-' . $entry->get('id'));
            }
        }

        $table = Widget::Table(
            Widget::TableHead($aTableHead),
            null,
            Widget::TableBody($aTableBody),
            'selectable',
            null,
            array('role' => 'directory', 'aria-labelledby' => 'symphony-subheading', 'data-interactive' => 'data-interactive')
        );

        $this->Form->appendChild($table);

        $tableActions = new XMLElement('div');
        $tableActions->setAttribute('class', 'actions');

        $options = array(
            array(null, false, __('With Selected...')),
            array('delete', false, __('Delete'), 'confirm', null, array(
                'data-message' => __('Are you sure you want to delete the selected entries?')
            ))
        );

        $toggable_fields = $section->fetchToggleableFields();

        if (is_array($toggable_fields) && !empty($toggable_fields)) {
            $index = 2;

            foreach ($toggable_fields as $field) {
                $toggle_states = $field->getToggleStates();

                if (is_array($toggle_states)) {
                    $options[$index] = array('label' => __('Set %s', array($field->get('label'))), 'options' => array());

                    foreach ($toggle_states as $value => $state) {
                        $options[$index]['options'][] = array('toggle-' . $field->get('id') . '-' . $value, false, $state);
                    }
                }

                $index++;
            }
        }

        /**
         * Allows an extension to modify the existing options for this page's
         * With Selected menu. If the `$options` parameter is an empty array,
         * the 'With Selected' menu will not be rendered.
         *
         * @delegate AddCustomActions
         * @since Symphony 2.3.2
         * @param string $context
         * '/publish/'
         * @param array $options
         *  An array of arrays, where each child array represents an option
         *  in the With Selected menu. Options should follow the same format
         *  expected by `Widget::__SelectBuildOption`. Passed by reference.
         */
        Symphony::ExtensionManager()->notifyMembers('AddCustomActions', '/publish/', array(
            'options' => &$options
        ));

        if (!empty($options)) {
            $tableActions->appendChild(Widget::Apply($options));
            $this->Form->appendChild($tableActions);
        }

        if ($entries['total-pages'] > 1) {
            $ul = new XMLElement('ul');
            $ul->setAttribute('class', 'page');

            // First
            $li = new XMLElement('li');

            if ($current_page > 1) {
                $li->appendChild(Widget::Anchor(__('First'), Administration::instance()->getCurrentPageURL(). '?pg=1'.($filter_querystring ? "&amp;" . $filter_querystring : '')));
            } else {
                $li->setValue(__('First'));
            }

            $ul->appendChild($li);

            // Previous
            $li = new XMLElement('li');

            if ($current_page > 1) {
                $li->appendChild(Widget::Anchor(__('&larr; Previous'), Administration::instance()->getCurrentPageURL(). '?pg=' . ($current_page - 1).($filter_querystring ? "&amp;" . $filter_querystring : '')));
            } else {
                $li->setValue(__('&larr; Previous'));
            }

            $ul->appendChild($li);

            // Summary
            $li = new XMLElement('li');

            $li->setAttribute('title', __('Viewing %1$s - %2$s of %3$s entries', array(
                $entries['start'],
                ($current_page != $entries['total-pages']) ? $current_page * Symphony::Configuration()->get('pagination_maximum_rows', 'symphony') : $entries['total-entries'],
                $entries['total-entries']
            )));

            $pgform = Widget::Form(Administration::instance()->getCurrentPageURL(), 'get', 'paginationform');

            $pgmax = max($current_page, $entries['total-pages']);
            $pgform->appendChild(Widget::Input('pg', null, 'text', array(
                'data-active' => __('Go to page …'),
                'data-inactive' => __('Page %1$s of %2$s', array((string)$current_page, $pgmax)),
                'data-max' => $pgmax
            )));

            $li->appendChild($pgform);
            $ul->appendChild($li);

            // Next
            $li = new XMLElement('li');

            if ($current_page < $entries['total-pages']) {
                $li->appendChild(Widget::Anchor(__('Next &rarr;'), Administration::instance()->getCurrentPageURL(). '?pg=' . ($current_page + 1).($filter_querystring ? "&amp;" . $filter_querystring : '')));
            } else {
                $li->setValue(__('Next &rarr;'));
            }

            $ul->appendChild($li);

            // Last
            $li = new XMLElement('li');

            if ($current_page < $entries['total-pages']) {
                $li->appendChild(Widget::Anchor(__('Last'), Administration::instance()->getCurrentPageURL(). '?pg=' . $entries['total-pages'].($filter_querystring ? "&amp;" . $filter_querystring : '')));
            } else {
                $li->setValue(__('Last'));
            }

            $ul->appendChild($li);

            $this->Contents->appendChild($ul);
        }
    }

    public function __actionIndex()
    {
        $checked = (is_array($_POST['items'])) ? array_keys($_POST['items']) : null;

        if (is_array($checked) && !empty($checked)) {
            /**
             * Extensions can listen for any custom actions that were added
             * through `AddCustomPreferenceFieldsets` or `AddCustomActions`
             * delegates.
             *
             * @delegate CustomActions
             * @since Symphony 2.3.2
             * @param string $context
             *  '/publish/'
             * @param array $checked
             *  An array of the selected rows. The value is usually the ID of the
             *  the associated object.
             */
            Symphony::ExtensionManager()->notifyMembers('CustomActions', '/publish/', array(
                'checked' => $checked
            ));

            switch ($_POST['with-selected']) {
                case 'delete':
                    /**
                     * Prior to deletion of entries. An array of Entry ID's is provided which
                     * can be manipulated. This delegate was renamed from `Delete` to `EntryPreDelete`
                     * in Symphony 2.3.
                     *
                     * @delegate EntryPreDelete
                     * @param string $context
                     * '/publish/'
                     * @param array $entry_id
                     *  An array of Entry ID's passed by reference
                     */
                    Symphony::ExtensionManager()->notifyMembers('EntryPreDelete', '/publish/', array('entry_id' => &$checked));

                    EntryManager::delete($checked);

                    /**
                     * After the deletion of entries, this delegate provides an array of Entry ID's
                     * that were deleted.
                     *
                     * @since Symphony 2.3
                     * @delegate EntryPostDelete
                     * @param string $context
                     * '/publish/'
                     * @param array $entry_id
                     *  An array of Entry ID's that were deleted.
                     */
                    Symphony::ExtensionManager()->notifyMembers('EntryPostDelete', '/publish/', array('entry_id' => $checked));

                    redirect($_SERVER['REQUEST_URI']);
                default:
                    list($option, $field_id, $value) = explode('-', $_POST['with-selected'], 3);

                    if ($option == 'toggle') {
                        $field = FieldManager::fetch($field_id);
                        $fields = array($field->get('element_name') => $value);

                        $section = SectionManager::fetch($field->get('parent_section'));

                        foreach ($checked as $entry_id) {
                            $entry = EntryManager::fetch($entry_id);
                            $existing_data = $entry[0]->getData($field_id);
                            $entry[0]->setData($field_id, $field->toggleFieldData(is_array($existing_data) ? $existing_data : array(), $value, $entry_id));

                            /**
                             * Just prior to editing of an Entry
                             *
                             * @delegate EntryPreEdit
                             * @param string $context
                             * '/publish/edit/'
                             * @param Section $section
                             * @param Entry $entry
                             * @param array $fields
                             */
                            Symphony::ExtensionManager()->notifyMembers('EntryPreEdit', '/publish/edit/', array(
                                'section' => $section,
                                'entry' => &$entry[0],
                                'fields' => $fields
                            ));

                            $entry[0]->commit();

                            /**
                             * Editing an entry. Entry object is provided.
                             *
                             * @delegate EntryPostEdit
                             * @param string $context
                             * '/publish/edit/'
                             * @param Section $section
                             * @param Entry $entry
                             * @param array $fields
                             */
                            Symphony::ExtensionManager()->notifyMembers('EntryPostEdit', '/publish/edit/', array(
                                'section' => $section,
                                'entry' => $entry[0],
                                'fields' => $fields
                            ));
                        }

                        redirect($_SERVER['REQUEST_URI']);
                    }
                    break;
            }
        }
    }

    public function __viewNew()
    {
        if (!$section_id = SectionManager::fetchIDFromHandle($this->_context['section_handle'])) {
            Administration::instance()->throwCustomError(
                __('The Section, %s, could not be found.', array('<code>' . $this->_context['section_handle'] . '</code>')),
                __('Unknown Section'),
                Page::HTTP_STATUS_NOT_FOUND
            );
        }

        $section = SectionManager::fetch($section_id);

        $this->setPageType('form');
        $this->setTitle(__('%1$s &ndash; %2$s', array($section->get('name'), __('Symphony'))));

        // Ensure errored entries still maintain any prepopulated values [#2211]
        $this->Form->setAttribute('action', $this->Form->getAttribute('action') . $this->getPrepopulateString());
        $this->Form->setAttribute('enctype', 'multipart/form-data');

        $sidebar_fields = $section->fetchFields(null, 'sidebar');
        $main_fields = $section->fetchFields(null, 'main');

        if (!empty($sidebar_fields) && !empty($main_fields)) {
            $this->Form->setAttribute('class', 'two columns');
        } else {
            $this->Form->setAttribute('class', 'columns');
        }

        // Only show the Edit Section button if the Author is a developer. #938 ^BA
        if (Symphony::Author()->isDeveloper()) {
            $this->appendSubheading(__('Untitled'),
                Widget::Anchor(__('Edit Section'), SYMPHONY_URL . '/blueprints/sections/edit/' . $section_id . '/', __('Edit Section Configuration'), 'button')
            );
        } else {
            $this->appendSubheading(__('Untitled'));
        }

        // Build filtered breadcrumb [#1378}
        $this->insertBreadcrumbs(array(
            Widget::Anchor($section->get('name'), SYMPHONY_URL . '/publish/' . $this->_context['section_handle'] . '/' . $this->getFilterString()),
        ));

        $this->Form->appendChild(Widget::Input('MAX_FILE_SIZE', Symphony::Configuration()->get('max_upload_size', 'admin'), 'hidden'));

        // If there is post data floating around, due to errors, create an entry object
        if (isset($_POST['fields'])) {
            $entry = EntryManager::create();
            $entry->set('section_id', $section_id);
            $entry->setDataFromPost($_POST['fields'], $error, true);

            // Brand new entry, so need to create some various objects
        } else {
            $entry = EntryManager::create();
            $entry->set('section_id', $section_id);
        }

        // Check if there is a field to prepopulate
        if (isset($_REQUEST['prepopulate'])) {
            foreach ($_REQUEST['prepopulate'] as $field_id => $value) {
                $this->Form->prependChild(Widget::Input(
                    "prepopulate[{$field_id}]",
                    rawurlencode($value),
                    'hidden'
                ));

                // The actual pre-populating should only happen if there is not existing fields post data
                if (!isset($_POST['fields']) && $field = FieldManager::fetch($field_id)) {
                    $entry->setData(
                        $field->get('id'),
                        $field->processRawFieldData($value, $error, $message, true)
                    );
                }
            }
        }

        $primary = new XMLElement('fieldset');
        $primary->setAttribute('class', 'primary column');

        if ((!is_array($main_fields) || empty($main_fields)) && (!is_array($sidebar_fields) || empty($sidebar_fields))) {
            $message = __('Fields must be added to this section before an entry can be created.');

            if (Symphony::Author()->isDeveloper()) {
                $message .= ' <a href="' . SYMPHONY_URL . '/blueprints/sections/edit/' . $section->get('id') . '/" accesskey="c">'
                . __('Add fields')
                . '</a>';
            }

            $this->pageAlert($message, Alert::ERROR);
        } else {
            if (is_array($main_fields) && !empty($main_fields)) {
                foreach ($main_fields as $field) {
                    $primary->appendChild($this->__wrapFieldWithDiv($field, $entry));
                }

                $this->Form->appendChild($primary);
            }

            if (is_array($sidebar_fields) && !empty($sidebar_fields)) {
                $sidebar = new XMLElement('fieldset');
                $sidebar->setAttribute('class', 'secondary column');

                foreach ($sidebar_fields as $field) {
                    $sidebar->appendChild($this->__wrapFieldWithDiv($field, $entry));
                }

                $this->Form->appendChild($sidebar);
            }

            $div = new XMLElement('div');
            $div->setAttribute('class', 'actions');
            $div->appendChild(Widget::Input('action[save]', __('Create Entry'), 'submit', array('accesskey' => 's')));

            $this->Form->appendChild($div);

            // Create a Drawer for Associated Sections
            $this->prepareAssociationsDrawer($section);
        }
    }

    public function __actionNew()
    {
        if (array_key_exists('save', $_POST['action']) || array_key_exists("done", $_POST['action'])) {
            $section_id = SectionManager::fetchIDFromHandle($this->_context['section_handle']);

            if (!$section = SectionManager::fetch($section_id)) {
                Administration::instance()->throwCustomError(
                    __('The Section, %s, could not be found.', array('<code>' . $this->_context['section_handle'] . '</code>')),
                    __('Unknown Section'),
                    Page::HTTP_STATUS_NOT_FOUND
                );
            }

            $entry = EntryManager::create();
            $entry->set('author_id', Symphony::Author()->get('id'));
            $entry->set('section_id', $section_id);
            $entry->set('creation_date', DateTimeObj::get('c'));
            $entry->set('modification_date', DateTimeObj::get('c'));

            $fields = $_POST['fields'];

            // Combine FILES and POST arrays, indexed by their custom field handles
            if (isset($_FILES['fields'])) {
                $filedata = General::processFilePostData($_FILES['fields']);

                foreach ($filedata as $handle => $data) {
                    if (!isset($fields[$handle])) {
                        $fields[$handle] = $data;
                    } elseif (isset($data['error']) && $data['error'] == UPLOAD_ERR_NO_FILE) {
                        $fields[$handle] = null;
                    } else {
                        foreach ($data as $ii => $d) {
                            if (isset($d['error']) && $d['error'] == UPLOAD_ERR_NO_FILE) {
                                $fields[$handle][$ii] = null;
                            } elseif (is_array($d) && !empty($d)) {
                                foreach ($d as $key => $val) {
                                    $fields[$handle][$ii][$key] = $val;
                                }
                            }
                        }
                    }
                }
            }

            // Initial checks to see if the Entry is ok
            if (Entry::__ENTRY_FIELD_ERROR__ == $entry->checkPostData($fields, $this->_errors)) {
                $this->pageAlert(__('Some errors were encountered while attempting to save.'), Alert::ERROR);

                // Secondary checks, this will actually process the data and attempt to save
            } elseif (Entry::__ENTRY_OK__ != $entry->setDataFromPost($fields, $errors)) {
                foreach ($errors as $field_id => $message) {
                    $this->pageAlert($message, Alert::ERROR);
                }

                // Everything is awesome. Dance.
            } else {
                /**
                 * Just prior to creation of an Entry
                 *
                 * @delegate EntryPreCreate
                 * @param string $context
                 * '/publish/new/'
                 * @param Section $section
                 * @param Entry $entry
                 * @param array $fields
                 */
                Symphony::ExtensionManager()->notifyMembers('EntryPreCreate', '/publish/new/', array('section' => $section, 'entry' => &$entry, 'fields' => &$fields));

                // Check to see if the dancing was premature
                if (!$entry->commit()) {
                    define_safe('__SYM_DB_INSERT_FAILED__', true);
                    $this->pageAlert(null, Alert::ERROR);
                } else {
                    /**
                     * Creation of an Entry. New Entry object is provided.
                     *
                     * @delegate EntryPostCreate
                     * @param string $context
                     * '/publish/new/'
                     * @param Section $section
                     * @param Entry $entry
                     * @param array $fields
                     */
                    Symphony::ExtensionManager()->notifyMembers('EntryPostCreate', '/publish/new/', array('section' => $section, 'entry' => $entry, 'fields' => $fields));

                    $prepopulate_querystring = $this->getPrepopulateString();
                    redirect(sprintf(
                        '%s/publish/%s/edit/%d/created/%s',
                        SYMPHONY_URL,
                        $this->_context['section_handle'],
                        $entry->get('id'),
                        (!empty($prepopulate_querystring) ? $prepopulate_querystring : null)
                    ));
                }
            }
        }
    }

    public function __viewEdit()
    {
        if (!$section_id = SectionManager::fetchIDFromHandle($this->_context['section_handle'])) {
            Administration::instance()->throwCustomError(
                __('The Section, %s, could not be found.', array('<code>' . $this->_context['section_handle'] . '</code>')),
                __('Unknown Section'),
                Page::HTTP_STATUS_NOT_FOUND
            );
        }

        $section = SectionManager::fetch($section_id);
        $entry_id = intval($this->_context['entry_id']);
        $base = '/publish/'.$this->_context['section_handle'] . '/';
        $new_link = $base . 'new/';
        $filter_link = $base;

        EntryManager::setFetchSorting('id', 'DESC');

        if (!$existingEntry = EntryManager::fetch($entry_id)) {
            Administration::instance()->throwCustomError(
                __('Unknown Entry'),
                __('The Entry, %s, could not be found.', array($entry_id)),
                Page::HTTP_STATUS_NOT_FOUND
            );
        }
        $existingEntry = $existingEntry[0];

        // If there is post data floating around, due to errors, create an entry object
        if (isset($_POST['fields'])) {
            $fields = $_POST['fields'];

            $entry = EntryManager::create();
            $entry->set('id', $entry_id);
            $entry->set('author_id', $existingEntry->get('author_id'));
            $entry->set('section_id', $existingEntry->get('section_id'));
            $entry->set('creation_date', $existingEntry->get('creation_date'));
            $entry->set('modification_date', $existingEntry->get('modification_date'));
            $entry->setDataFromPost($fields, $errors, true);

            // Editing an entry, so need to create some various objects
        } else {
            $entry = $existingEntry;
            $fields = array();

            if (!$section) {
                $section = SectionManager::fetch($entry->get('section_id'));
            }
        }

        /**
         * Just prior to rendering of an Entry edit form.
         *
         * @delegate EntryPreRender
         * @param string $context
         * '/publish/edit/'
         * @param Section $section
         * @param Entry $entry
         * @param array $fields
         */
        Symphony::ExtensionManager()->notifyMembers('EntryPreRender', '/publish/edit/', array(
            'section' => $section,
            'entry' => &$entry,
            'fields' => $fields
        ));

        // Iterate over the `prepopulate` parameters to build a URL
        // to remember this state for Create New, View all Entries and
        // Breadcrumb links. If `prepopulate` doesn't exist, this will
        // just use the standard pages (ie. no filtering)
        if (isset($_REQUEST['prepopulate'])) {
            $new_link .= $this->getPrepopulateString();
            $filter_link .= $this->getFilterString();
        }

        if (isset($this->_context['flag'])) {
            // These flags are only relevant if there are no errors
            if (empty($this->_errors)) {
                $time = Widget::Time();

                switch ($this->_context['flag']) {
                    case 'saved':
                        $message = __('Entry updated at %s.', array($time->generate()));
                        break;
                    case 'created':
                        $message = __('Entry created at %s.', array($time->generate()));
                }

                $this->pageAlert(
                    $message
                    . ' <a href="' . SYMPHONY_URL . $new_link . '" accesskey="c">'
                    . __('Create another?')
                    . '</a> <a href="' . SYMPHONY_URL . $filter_link . '" accesskey="a">'
                    . __('View all Entries')
                    . '</a>',
                    Alert::SUCCESS
                );
            }
        }

        // Determine the page title
        $field_id = Symphony::Database()->fetchVar('id', 0, sprintf("
            SELECT `id`
            FROM `tbl_fields`
            WHERE `parent_section` = %d
            ORDER BY `sortorder` LIMIT 1",
            $section->get('id')
        ));
        if (!is_null($field_id)) {
            $field = FieldManager::fetch($field_id);
        }

        if ($field) {
            $title = $field->prepareReadableValue($existingEntry->getData($field->get('id')), $entry_id, true);
        } else {
            $title = '';
        }

        if (trim($title) == '') {
            $title = __('Untitled');
        }

        // Check if there is a field to prepopulate
        if (isset($_REQUEST['prepopulate'])) {
            foreach ($_REQUEST['prepopulate'] as $field_id => $value) {
                $this->Form->prependChild(Widget::Input(
                    "prepopulate[{$field_id}]",
                    rawurlencode($value),
                    'hidden'
                ));
            }
        }

        $this->setPageType('form');
        $this->Form->setAttribute('enctype', 'multipart/form-data');
        $this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array($title, $section->get('name'), __('Symphony'))));

        $sidebar_fields = $section->fetchFields(null, 'sidebar');
        $main_fields = $section->fetchFields(null, 'main');

        if (!empty($sidebar_fields) && !empty($main_fields)) {
            $this->Form->setAttribute('class', 'two columns');
        } else {
            $this->Form->setAttribute('class', 'columns');
        }

        // Only show the Edit Section button if the Author is a developer. #938 ^BA
        if (Symphony::Author()->isDeveloper()) {
            $this->appendSubheading($title, Widget::Anchor(__('Edit Section'), SYMPHONY_URL . '/blueprints/sections/edit/' . $section_id . '/', __('Edit Section Configuration'), 'button'));
        } else {
            $this->appendSubheading($title);
        }

        $this->insertBreadcrumbs(array(
            Widget::Anchor($section->get('name'), SYMPHONY_URL . (isset($filter_link) ? $filter_link : $base)),
        ));

        $this->Form->appendChild(Widget::Input('MAX_FILE_SIZE', Symphony::Configuration()->get('max_upload_size', 'admin'), 'hidden'));

        $primary = new XMLElement('fieldset');
        $primary->setAttribute('class', 'primary column');

        if ((!is_array($main_fields) || empty($main_fields)) && (!is_array($sidebar_fields) || empty($sidebar_fields))) {
            $message = __('Fields must be added to this section before an entry can be created.');

            if (Symphony::Author()->isDeveloper()) {
                $message .= ' <a href="' . SYMPHONY_URL . '/blueprints/sections/edit/' . $section->get('id') . '/" accesskey="c">'
                . __('Add fields')
                . '</a>';
            }

            $this->pageAlert($message, Alert::ERROR);
        } else {

            if (is_array($main_fields) && !empty($main_fields)) {
                foreach ($main_fields as $field) {
                    $primary->appendChild($this->__wrapFieldWithDiv($field, $entry));
                }

                $this->Form->appendChild($primary);
            }

            if (is_array($sidebar_fields) && !empty($sidebar_fields)) {
                $sidebar = new XMLElement('fieldset');
                $sidebar->setAttribute('class', 'secondary column');

                foreach ($sidebar_fields as $field) {
                    $sidebar->appendChild($this->__wrapFieldWithDiv($field, $entry));
                }

                $this->Form->appendChild($sidebar);
            }

            $div = new XMLElement('div');
            $div->setAttribute('class', 'actions');
            $div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', array('accesskey' => 's')));

            $button = new XMLElement('button', __('Delete'));
            $button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this entry'), 'type' => 'submit', 'accesskey' => 'd', 'data-message' => __('Are you sure you want to delete this entry?')));
            $div->appendChild($button);

            $this->Form->appendChild($div);

            // Create a Drawer for Associated Sections
            $this->prepareAssociationsDrawer($section);
        }
    }

    public function __actionEdit()
    {
        $entry_id = intval($this->_context['entry_id']);

        if (@array_key_exists('save', $_POST['action']) || @array_key_exists("done", $_POST['action'])) {
            if (!$ret = EntryManager::fetch($entry_id)) {
                Administration::instance()->throwCustomError(
                    __('The Entry, %s, could not be found.', array($entry_id)),
                    __('Unknown Entry'),
                    Page::HTTP_STATUS_NOT_FOUND
                );
            }

            $entry = $ret[0];

            $section = SectionManager::fetch($entry->get('section_id'));

            $post = General::getPostData();
            $fields = $post['fields'];

            // Initial checks to see if the Entry is ok
            if (__ENTRY_FIELD_ERROR__ == $entry->checkPostData($fields, $this->_errors)) {
                $this->pageAlert(__('Some errors were encountered while attempting to save.'), Alert::ERROR);

                // Secondary checks, this will actually process the data and attempt to save
            } elseif (__ENTRY_OK__ != $entry->setDataFromPost($fields, $errors)) {
                foreach ($errors as $field_id => $message) {
                    $this->pageAlert($message, Alert::ERROR);
                }

                // Everything is awesome. Dance.
            } else {
                /**
                 * Just prior to editing of an Entry.
                 *
                 * @delegate EntryPreEdit
                 * @param string $context
                 * '/publish/edit/'
                 * @param Section $section
                 * @param Entry $entry
                 * @param array $fields
                 */
                Symphony::ExtensionManager()->notifyMembers('EntryPreEdit', '/publish/edit/', array('section' => $section, 'entry' => &$entry, 'fields' => $fields));

                // Check to see if the dancing was premature
                if (!$entry->commit()) {
                    define_safe('__SYM_DB_INSERT_FAILED__', true);
                    $this->pageAlert(null, Alert::ERROR);
                } else {
                    /**
                     * Just after the editing of an Entry
                     *
                     * @delegate EntryPostEdit
                     * @param string $context
                     * '/publish/edit/'
                     * @param Section $section
                     * @param Entry $entry
                     * @param array $fields
                     */
                    Symphony::ExtensionManager()->notifyMembers('EntryPostEdit', '/publish/edit/', array('section' => $section, 'entry' => $entry, 'fields' => $fields));

                    redirect(sprintf(
                        '%s/publish/%s/edit/%d/saved/%s',
                        SYMPHONY_URL,
                        $this->_context['section_handle'],
                        $entry->get('id'),
                        $this->getPrepopulateString()
                    ));
                }
            }
        } elseif (@array_key_exists('delete', $_POST['action']) && is_numeric($entry_id)) {
            /**
             * Prior to deletion of entries. An array of Entry ID's is provided which
             * can be manipulated. This delegate was renamed from `Delete` to `EntryPreDelete`
             * in Symphony 2.3.
             *
             * @delegate EntryPreDelete
             * @param string $context
             * '/publish/'
             * @param array $entry_id
             *    An array of Entry ID's passed by reference
             */
            $checked = array($entry_id);
            Symphony::ExtensionManager()->notifyMembers('EntryPreDelete', '/publish/', array('entry_id' => &$checked));

            EntryManager::delete($checked);

            /**
             * After the deletion of entries, this delegate provides an array of Entry ID's
             * that were deleted.
             *
             * @since Symphony 2.3
             * @delegate EntryPostDelete
             * @param string $context
             * '/publish/'
             * @param array $entry_id
             *  An array of Entry ID's that were deleted.
             */
            Symphony::ExtensionManager()->notifyMembers('EntryPostDelete', '/publish/', array('entry_id' => $checked));

            redirect(SYMPHONY_URL . '/publish/'.$this->_context['section_handle'].'/');
        }
    }

    /**
     * Given a Field and Entry object, this function will wrap
     * the Field's displayPublishPanel result with a div that
     * contains some contextual information such as the Field ID,
     * the Field handle and whether it is required or not.
     *
     * @param Field $field
     * @param Entry $entry
     * @return XMLElement
     */
    private function __wrapFieldWithDiv(Field $field, Entry $entry)
    {
        $is_hidden = $this->isFieldHidden($field);
        $div = new XMLElement('div', null, array('id' => 'field-' . $field->get('id'), 'class' => 'field field-'.$field->handle().($field->get('required') == 'yes' ? ' required' : '').($is_hidden == true ? ' irrelevant' : '')));

        $field->setAssociationContext($div);

        $field->displayPublishPanel(
            $div, $entry->getData($field->get('id')),
            (isset($this->_errors[$field->get('id')]) ? $this->_errors[$field->get('id')] : null),
            null, null, (is_numeric($entry->get('id')) ? $entry->get('id') : null)
        );

        /**
         * Allows developers modify the field before it is rendered in the publish
         * form. Passes the `Field` object, `Entry` object, the `XMLElement` div and
         * any errors for the entire `Entry`. Only the `$div` element
         * will be altered before appending to the page, the rest are read only.
         *
         * @since Symphony 2.5.0
         * @delegate ModifyFieldPublishWidget
         * @param string $context
         * '/backend/'
         * @param Field $field
         * @param Entry $entry
         * @param array $errors
         * @param Widget $widget
         */
        Symphony::ExtensionManager()->notifyMembers('ModifyFieldPublishWidget', '/backend/', array(
            'field' => $field,
            'entry' => $entry,
            'errors' => $this->_errors,
            'widget' => &$div
        ));

        return $div;
    }

    /**
     * Check whether a field is a Select Box Link and is hidden
     *
     * @param  Field  $field
     * @return String
     */
    public function isFieldHidden(Field $field)
    {
        if ($field->get('hide_when_prepopulated') == 'yes') {
            if (isset($_REQUEST['prepopulate'])) {
                foreach ($_REQUEST['prepopulate'] as $field_id => $value) {
                    if ($field_id == $field->get('id')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Prepare a Drawer to visualize section associations
     *
     * @param  Section $section The current Section object
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function prepareAssociationsDrawer($section)
    {
        $entry_id = (!is_null($this->_context['entry_id'])) ? $this->_context['entry_id'] : null;
        $show_entries = Symphony::Configuration()->get('association_maximum_rows', 'symphony');

        if (is_null($entry_id) && !isset($_GET['prepopulate']) || is_null($show_entries) || $show_entries == 0) {
            return;
        }

        $parent_associations = SectionManager::fetchParentAssociations($section->get('id'), true);
        $child_associations = SectionManager::fetchChildAssociations($section->get('id'), true);
        $content = null;
        $drawer_position = 'vertical-right';

        /**
         * Prepare Associations Drawer from an Extension
         *
         * @since Symphony 2.3.3
         * @delegate PrepareAssociationsDrawer
         * @param string $context
         * '/publish/'
         * @param integer $entry_id
         *  The entry ID or null
         * @param array $parent_associations
         *  Array of Sections
         * @param array $child_associations
         *  Array of Sections
         * @param string $drawer_position
         *  The position of the Drawer, defaults to `vertical-right`. Available
         *  values of `vertical-left, `vertical-right` and `horizontal`
         */
        Symphony::ExtensionManager()->notifyMembers('PrepareAssociationsDrawer', '/publish/', array(
            'entry_id' => $entry_id,
            'parent_associations' => &$parent_associations,
            'child_associations' => &$child_associations,
            'content' => &$content,
            'drawer-position' => &$drawer_position
        ));

        // If there are no associations, return now.
        if (
            (is_null($parent_associations) || empty($parent_associations))
            &&
            (is_null($child_associations) || empty($child_associations))
        ) {
            return;
        }

        if (!($content instanceof XMLElement)) {
            $content = new XMLElement('div', null, array('class' => 'content'));
            $content->setSelfClosingTag(false);

            // Process Parent Associations
            if (!is_null($parent_associations) && !empty($parent_associations)) {
                foreach ($parent_associations as $as) {
                    if ($field = FieldManager::fetch($as['parent_section_field_id'])) {
                        if (isset($_GET['prepopulate'])) {
                            $prepopulate_field = key($_GET['prepopulate']);
                        }

                        // get associated entries if entry exists,
                        if ($entry_id) {
                            $entry_ids = $field->findParentRelatedEntries($as['child_section_field_id'], $entry_id);

                            // get prepopulated entry otherwise
                        } elseif (isset($_GET['prepopulate'])) {
                            $entry_ids = array(intval(current($_GET['prepopulate'])));
                        } else {
                            $entry_ids = array();
                        }

                        // Use $schema for perf reasons
                        $schema = array($field->get('element_name'));
                        $where = (!empty($entry_ids)) ? sprintf(' AND `e`.`id` IN (%s)', implode(', ', $entry_ids)) : null;
                        $entries = (!empty($entry_ids) || isset($_GET['prepopulate']) && $field->get('id') === $prepopulate_field)
                            ? EntryManager::fetchByPage(1, $as['parent_section_id'], $show_entries, $where, null, false, false, true, $schema)
                            : array();
                        $has_entries = !empty($entries) && $entries['total-entries'] != 0;

                        if ($has_entries) {
                            $element = new XMLElement('section', null, array('class' => 'association parent'));
                            $header = new XMLElement('header');
                            $header->appendChild(new XMLElement('p', __('Linked to %s in', array('<a class="association-section" href="' . SYMPHONY_URL . '/publish/' . $as['handle'] . '/">' . $as['name'] . '</a>'))));
                            $element->appendChild($header);

                            $ul = new XMLElement('ul', null, array(
                                'class' => 'association-links',
                                'data-section-id' => $as['child_section_id'],
                                'data-association-ids' => implode(', ', $entry_ids)
                            ));

                            foreach ($entries['records'] as $e) {
                                // let the field create the mark up
                                $li = $field->prepareAssociationsDrawerXMLElement($e, $as);
                                // add it to the unordered list
                                $ul->appendChild($li);
                            }

                            $element->appendChild($ul);
                            $content->appendChild($element);
                        }
                    }
                }
            }

            // Process Child Associations
            if (!is_null($child_associations) && !empty($child_associations)) {
                foreach ($child_associations as $as) {
                    // Get the related section
                    $child_section = SectionManager::fetch($as['child_section_id']);

                    if (!($child_section instanceof Section)) {
                        continue;
                    }

                    // Get the visible field instance (using the sorting field, this is more flexible than visibleColumns())
                    // Get the link field instance
                    $visible_field   = current($child_section->fetchVisibleColumns());
                    $relation_field  = FieldManager::fetch($as['child_section_field_id']);

                    // Get entries, using $schema for performance reasons.
                    $entry_ids = $relation_field->findRelatedEntries($entry_id, $as['parent_section_field_id']);
                    $schema = $visible_field ? array($visible_field->get('element_name')) : array();
                    $where = sprintf(' AND `e`.`id` IN (%s)', implode(', ', $entry_ids));

                    $entries = (!empty($entry_ids)) ? EntryManager::fetchByPage(1, $as['child_section_id'], $show_entries, $where, null, false, false, true, $schema) : array();
                    $has_entries = !empty($entries) && $entries['total-entries'] != 0;

                    // Build the HTML of the relationship
                    $element = new XMLElement('section', null, array('class' => 'association child'));
                    $header = new XMLElement('header');
                    $filter = '?filter[' . $relation_field->get('element_name') . ']=' . $entry_id;
                    $prepopulate = '?prepopulate[' . $as['child_section_field_id'] . ']=' . $entry_id;

                    // Create link with filter or prepopulate
                    $link = SYMPHONY_URL . '/publish/' . $as['handle'] . '/' . $filter;
                    $a = new XMLElement('a', $as['name'], array(
                        'class' => 'association-section',
                        'href' => $link
                    ));

                    // Create new entries
                    $create = new XMLElement('a', __('Create New'), array(
                        'class' => 'button association-new',
                        'href' => SYMPHONY_URL . '/publish/' . $as['handle'] . '/new/' . $prepopulate
                    ));

                    // Display existing entries
                    if ($has_entries) {
                        $header->appendChild(new XMLElement('p', __('Links in %s', array($a->generate()))));

                        $ul = new XMLElement('ul', null, array(
                            'class' => 'association-links',
                            'data-section-id' => $as['child_section_id'],
                            'data-association-ids' => implode(', ', $entry_ids)
                        ));

                        foreach ($entries['records'] as $key => $e) {
                            // let the first visible field create the mark up
                            if ($visible_field) {
                                $li = $visible_field->prepareAssociationsDrawerXMLElement($e, $as, $prepopulate);
                            }
                            // or use the system:id if no visible field exists.
                            else {
                                $li = Field::createAssociationsDrawerXMLElement($e->get('id'), $e, $as, $prepopulate);
                            }

                            // add it to the unordered list
                            $ul->appendChild($li);
                        }

                        $element->appendChild($ul);

                        // If we are only showing 'some' of the entries, then show this on the UI
                        if ($entries['total-entries'] > $show_entries) {
                            $pagination = new XMLElement('li', null, array(
                                'class' => 'association-more',
                                'data-current-page' => '1',
                                'data-total-pages' => ceil($entries['total-entries'] / $show_entries),
                                'data-total-entries' => $entries['total-entries']
                            ));
                            $counts = new XMLElement('a', __('Show more entries'), array(
                                'href' => $link
                            ));

                            $pagination->appendChild($counts);
                            $ul->appendChild($pagination);
                        }

                        // No entries
                    } else {
                        $element->setAttribute('class', 'association child empty');
                        $header->appendChild(new XMLElement('p', __('No links in %s', array($a->generate()))));
                    }

                    $header->appendChild($create);
                    $element->prependChild($header);
                    $content->appendChild($element);
                }
            }
        }

        $drawer = Widget::Drawer('section-associations', __('Show Associations'), $content);
        $this->insertDrawer($drawer, $drawer_position, 'prepend');
    }

    /**
     * If this entry is being prepopulated, this function will return the prepopulated
     * fields and values as a query string.
     *
     * @since Symphony 2.5.2
     * @return string
     */
    public function getPrepopulateString()
    {
        $prepopulate_querystring = '';

        if (isset($_REQUEST['prepopulate'])) {
            foreach ($_REQUEST['prepopulate'] as $field_id => $value) {
                $prepopulate_querystring .= sprintf("prepopulate[%s]=%s&", $field_id, rawurldecode($value));
            }
            $prepopulate_querystring = trim($prepopulate_querystring, '&');
        }

        // This is to prevent the value being interpreted as an additional GET
        // parameter. eg. prepopulate[cat]=Minx&June, would come through as:
        // $_GET['cat'] = Minx
        // $_GET['June'] = ''
        $prepopulate_querystring = preg_replace("/&amp;$/", '', $prepopulate_querystring);

        return $prepopulate_querystring ? '?' . $prepopulate_querystring : null;
    }

    /**
     * If the entry is being prepopulated, we may want to filter other views by this entry's
     * value. This function will create that filter query string.
     *
     * @since Symphony 2.5.2
     * @return string
     */
    public function getFilterString()
    {
        $filter_querystring = '';

        if (isset($_REQUEST['prepopulate'])) {
            foreach ($_REQUEST['prepopulate'] as $field_id => $value) {
                $handle = FieldManager::fetchHandleFromID($field_id);
                $filter_querystring .= sprintf("filter[%s]=%s&", $handle, rawurldecode($value));
            }
            $filter_querystring = trim($filter_querystring, '&');
        }

        // This is to prevent the value being interpreted as an additional GET
        // parameter. eg. filter[cat]=Minx&June, would come through as:
        // $_GET['cat'] = Minx
        // $_GET['June'] = ''
        $filter_querystring = preg_replace("/&amp;$/", '', $filter_querystring);

        return $filter_querystring ? '?' . $filter_querystring : null;
    }
}
