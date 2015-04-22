<?php
/**
 * @package toolkit
 */

/**
 * The Tag List field is really a different interface for the Select Box
 * field, offering a tag interface that can have static suggestions,
 * suggestions from another field or a dynamic list based on what an Author
 * has previously used for this field.
 */
class FieldTagList extends Field implements ExportableField, ImportableField
{
    public function __construct()
    {
        parent::__construct();
        $this->_name = __('Tag List');
        $this->_required = true;
        $this->_showassociation = true;

        $this->set('required', 'no');
    }

    /*-------------------------------------------------------------------------
        Definition:
    -------------------------------------------------------------------------*/

    public function canFilter()
    {
        return true;
    }

    public function canPrePopulate()
    {
        return true;
    }

    public function requiresSQLGrouping()
    {
        return true;
    }

    public function allowDatasourceParamOutput()
    {
        return true;
    }

    public function fetchSuggestionTypes()
    {
        return array('association', 'static');
    }

    /*-------------------------------------------------------------------------
        Setup:
    -------------------------------------------------------------------------*/

    public function createTable()
    {
        return Symphony::Database()->query(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
              `id` int(11) unsigned NOT null auto_increment,
              `entry_id` int(11) unsigned NOT null,
              `handle` varchar(255) default null,
              `value` varchar(255) default null,
              PRIMARY KEY  (`id`),
              KEY `entry_id` (`entry_id`),
              KEY `handle` (`handle`),
              KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
        );
    }

    /*-------------------------------------------------------------------------
        Utilities:
    -------------------------------------------------------------------------*/

    public function fetchAssociatedEntryCount($value)
    {
        if (function_exists('cleanValue') === false) {
            function cleanValue($val) {
                return Symphony::Database()->cleanValue($val);
            }
        }

        $value = array_map("cleanValue", explode(',', $value));
        $count = (int)Symphony::Database()->fetchVar('count', 0, sprintf("
            SELECT COUNT(DISTINCT handle) AS `count`
            FROM `tbl_entries_data_%d`
            WHERE `handle` IN ('%s')",
            $this->get('id'),
            implode("','", $value)
        ));

        return $count;
    }

    public function fetchAssociatedEntryIDs($value)
    {
        return Symphony::Database()->fetchCol('entry_id', sprintf("
            SELECT `entry_id`
            FROM `tbl_entries_data_%d`
            WHERE `value` = '%s'",
            $this->get('id'),
            Symphony::Database()->cleanValue($value)
        ));
    }

    public function fetchAssociatedEntrySearchValue($data, $field_id = null, $parent_entry_id = null)
    {
        if (!is_array($data)) {
            return $data;
        }

        if (!is_array($data['handle'])) {
            $data['handle'] = array($data['handle']);
            $data['value'] = array($data['value']);
        }

        return implode(',', $data['handle']);
    }

    /**
     * Find all the entries that reference this entry's tags.
     *
     * @param integer $entry_id
     * @param integer $parent_field_id
     * @return array
     */
    public function findRelatedEntries($entry_id, $parent_field_id) {
        // We have the entry_id of the entry that has the referenced tag values
        // Lets find out what those handles are so we can then referenced the
        // child section looking for them.
        $handles = Symphony::Database()->fetchCol('handle', sprintf("
            SELECT `handle`
            FROM `tbl_entries_data_%d`
            WHERE `entry_id` = %d
        ", $parent_field_id, $entry_id));

        $ids = Symphony::Database()->fetchCol('entry_id', sprintf("
            SELECT `entry_id`
            FROM `tbl_entries_data_%d`
            WHERE `handle` IN ('%s')
        ", $this->get('id'), implode("','", $handles)));

        return $ids;
    }

    /**
     * Find all the entries that contain the tags that have been referenced
     * from this field own entry.
     *
     * @param integer $field_id
     * @param integer $entry_id
     * @return array
     */
    public function findParentRelatedEntries($field_id, $entry_id) {
        // Get all the `handles` that have been referenced from the
        // child association.
        $handles = Symphony::Database()->fetchCol('handle', sprintf("
            SELECT `handle`
            FROM `tbl_entries_data_%d`
            WHERE `entry_id` = %d
        ", $field_id, $entry_id));

        // Now find the associated entry ids for those `handles` in
        // the parent section.
        $ids = Symphony::Database()->fetchCol('entry_id', sprintf("
            SELECT `entry_id`
            FROM `tbl_entries_data_%d`
            WHERE `handle` IN ('%s')
        ", $this->get('id'), implode("','", $handles)));

        return $ids;
    }

    public function set($field, $value)
    {
        if ($field == 'pre_populate_source' && !is_array($value)) {
            $value = preg_split('/\s*,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
        }

        $this->_settings[$field] = $value;
    }

    /**
     * @deprecated Will be removed in Symphony 3.0, use `getToggleStates()` instead
     */
    public function findAllTags()
    {
        $this->getToggleStates();
    }

    public function getToggleStates()
    {
        if (!is_array($this->get('pre_populate_source'))) {
            return;
        }

        $values = array();

        foreach ($this->get('pre_populate_source') as $item) {
            if($item === 'none') break;

            $result = Symphony::Database()->fetchCol('value', sprintf(
                "SELECT DISTINCT `value` FROM tbl_entries_data_%d ORDER BY `value` ASC",
                ($item == 'existing' ? $this->get('id') : $item)
            ));

            if (!is_array($result) || empty($result)) {
                continue;
            }

            $values = array_merge($values, $result);
        }

        return array_unique($values);
    }

    private static function __tagArrayToString(array $tags)
    {
        if (empty($tags)) {
            return null;
        }

        sort($tags);

        return implode(', ', $tags);
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    public function findDefaults(array &$settings)
    {
        if (!isset($settings['pre_populate_source'])) {
            $settings['pre_populate_source'] = array('existing');
        }

        if (!isset($settings['show_association'])) {
            $settings['show_association'] = 'no';
        }
    }

    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
    {
        parent::displaySettingsPanel($wrapper, $errors);

        // Suggestions
        $label = Widget::Label(__('Suggestion List'));

        $sections = SectionManager::fetch(null, 'ASC', 'name');
        $field_groups = array();

        if (is_array($sections) && !empty($sections)) {
            foreach ($sections as $section) {
                $field_groups[$section->get('id')] = array('fields' => $section->fetchFields(), 'section' => $section);
            }
        }

        $options = array(
            array('none', (in_array('none', $this->get('pre_populate_source'))), __('No Suggestions')),
            array('existing', (in_array('existing', $this->get('pre_populate_source'))), __('Existing Values')),
        );

        foreach ($field_groups as $group) {
            if (!is_array($group['fields'])) {
                continue;
            }

            $fields = array();

            foreach ($group['fields'] as $f) {
                if ($f->get('id') != $this->get('id') && $f->canPrePopulate()) {
                    $fields[] = array($f->get('id'), (in_array($f->get('id'), $this->get('pre_populate_source'))), $f->get('label'));
                }
            }

            if (is_array($fields) && !empty($fields)) {
                $options[] = array('label' => $group['section']->get('name'), 'options' => $fields);
            }
        }

        $label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][pre_populate_source][]', $options, array('multiple' => 'multiple')));
        $wrapper->appendChild($label);

        // Validation rule
        $this->buildValidationSelect($wrapper, $this->get('validator'), 'fields['.$this->get('sortorder').'][validator]', 'input', $errors);

        // Associations
        $fieldset = new XMLElement('fieldset');
        $this->appendAssociationInterfaceSelect($fieldset);
        $this->appendShowAssociationCheckbox($fieldset);
        $wrapper->appendChild($fieldset);

        // Requirements and table display
        $this->appendStatusFooter($wrapper);
    }

    public function commit()
    {
        if (!parent::commit()) {
            return false;
        }

        $id = $this->get('id');

        if ($id === false) {
            return false;
        }

        $fields = array();

        $fields['pre_populate_source'] = (is_null($this->get('pre_populate_source')) ? null : implode(',', $this->get('pre_populate_source')));
        $fields['validator'] = ($fields['validator'] == 'custom' ? null : $this->get('validator'));

        if (!FieldManager::saveSettings($id, $fields)) {
            return false;
        }

        SectionManager::removeSectionAssociation($id);

        foreach ($this->get('pre_populate_source') as $field_id) {
            if($field_id === 'none' || $field_id === 'existing') continue;

            if (!is_null($field_id) && is_numeric($field_id)) {
                SectionManager::createSectionAssociation(null, $id, (int) $field_id, $this->get('show_association') === 'yes' ? true : false, $this->get('association_ui'), $this->get('association_editor'));
            }
        }

        return true;
    }

    /*-------------------------------------------------------------------------
        Publish:
    -------------------------------------------------------------------------*/

    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null)
    {
        $value = null;

        if (isset($data['value'])) {
            $value = (is_array($data['value']) ? self::__tagArrayToString($data['value']) : $data['value']);
        }

        $label = Widget::Label($this->get('label'));

        if ($this->get('required') !== 'yes') {
            $label->appendChild(new XMLElement('i', __('Optional')));
        }

        $label->appendChild(
            Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (strlen($value) != 0 ? General::sanitize($value) : null))
        );

        if ($flagWithError != null) {
            $wrapper->appendChild(Widget::Error($label, $flagWithError));
        } else {
            $wrapper->appendChild($label);
        }

        if ($this->get('pre_populate_source') != null) {
            $existing_tags = $this->getToggleStates();

            if (is_array($existing_tags) && !empty($existing_tags)) {
                $taglist = new XMLElement('ul');
                $taglist->setAttribute('class', 'tags');
                $taglist->setAttribute('data-interactive', 'data-interactive');

                foreach ($existing_tags as $tag) {
                    $taglist->appendChild(
                        new XMLElement('li', General::sanitize($tag))
                    );
                }

                $wrapper->appendChild($taglist);
            }
        }
    }

    public function checkPostFieldData($data, &$message, $entry_id = null)
    {
        $message = null;

        if ($this->get('required') === 'yes' && strlen(trim($data)) == 0) {
            $message = __('‘%s’ is a required field.', array($this->get('label')));
            return self::__MISSING_FIELDS__;
        }

        if ($this->get('validator')) {
            $data = preg_split('/\,\s*/i', $data, -1, PREG_SPLIT_NO_EMPTY);
            $data = array_map('trim', $data);

            if (empty($data)) {
                return self::__OK__;
            }

            if (!General::validateString($data, $this->get('validator'))) {
                $message = __("'%s' contains invalid data. Please check the contents.", array($this->get('label')));
                return self::__INVALID_FIELDS__;
            }
        }

        return self::__OK__;
    }

    public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
    {
        $status = self::__OK__;
        $data = preg_split('/\,\s*/i', $data, -1, PREG_SPLIT_NO_EMPTY);
        $data = array_map('trim', $data);

        if (empty($data)) {
            return null;
        }

        // Do a case insensitive removal of duplicates
        $data = General::array_remove_duplicates($data, true);

        sort($data);

        $result = array();
        foreach ($data as $value) {
            $result['value'][] = $value;
            $result['handle'][] = Lang::createHandle($value);
        }

        return $result;
    }

    /*-------------------------------------------------------------------------
        Output:
    -------------------------------------------------------------------------*/

    public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null)
    {
        if (!is_array($data) || empty($data) || is_null($data['value'])) {
            return;
        }

        $list = new XMLElement($this->get('element_name'));

        if (!is_array($data['handle']) && !is_array($data['value'])) {
            $data = array(
                'handle'    => array($data['handle']),
                'value'     => array($data['value'])
            );
        }

        foreach ($data['value'] as $index => $value) {
            $list->appendChild(new XMLElement('item', General::sanitize($value), array(
                'handle' => $data['handle'][$index]
            )));
        }

        $wrapper->appendChild($list);
    }

    public function prepareTextValue($data, $entry_id = null)
    {
        if (!is_array($data) || empty($data)) {
            return '';
        }

        $value = '';

        if (isset($data['value'])) {
            $value = (is_array($data['value']) ? self::__tagArrayToString($data['value']) : $data['value']);
        }

        return General::sanitize($value);
    }

    public function getParameterPoolValue(array $data, $entry_id = null)
    {
        return $this->prepareExportValue($data, ExportableField::LIST_OF + ExportableField::HANDLE, $entry_id);
    }

    /*-------------------------------------------------------------------------
        Import:
    -------------------------------------------------------------------------*/

    public function getImportModes()
    {
        return array(
            'getValue' =>       ImportableField::STRING_VALUE,
            'getPostdata' =>    ImportableField::ARRAY_VALUE
        );
    }

    public function prepareImportValue($data, $mode, $entry_id = null)
    {
        $message = $status = null;
        $modes = (object)$this->getImportModes();

        if (is_array($data)) {
            $data = implode(', ', $data);
        }

        if ($mode === $modes->getValue) {
            return $data;
        } elseif ($mode === $modes->getPostdata) {
            return $this->processRawFieldData($data, $status, $message, true, $entry_id);
        }

        return null;
    }

    /*-------------------------------------------------------------------------
        Export:
    -------------------------------------------------------------------------*/

    /**
     * Return a list of supported export modes for use with `prepareExportValue`.
     *
     * @return array
     */
    public function getExportModes()
    {
        return array(
            'listHandle' =>         ExportableField::LIST_OF
                                    + ExportableField::HANDLE,
            'listValue' =>          ExportableField::LIST_OF
                                    + ExportableField::VALUE,
            'listHandleToValue' =>  ExportableField::LIST_OF
                                    + ExportableField::HANDLE
                                    + ExportableField::VALUE,
            'getPostdata' =>        ExportableField::POSTDATA
        );
    }

    /**
     * Give the field some data and ask it to return a value using one of many
     * possible modes.
     *
     * @param mixed $data
     * @param integer $mode
     * @param integer $entry_id
     * @return array|null
     */
    public function prepareExportValue($data, $mode, $entry_id = null)
    {
        $modes = (object)$this->getExportModes();

        if (isset($data['handle']) && is_array($data['handle']) === false) {
            $data['handle'] = array(
                $data['handle']
            );
        }

        if (isset($data['value']) && is_array($data['value']) === false) {
            $data['value'] = array(
                $data['value']
            );
        }

        // Handle => value pairs:
        if ($mode === $modes->listHandleToValue) {
            return isset($data['handle'], $data['value'])
                ? array_combine($data['handle'], $data['value'])
                : array();

            // Array of handles:
        } elseif ($mode === $modes->listHandle) {
            return isset($data['handle'])
                ? $data['handle']
                : array();

            // Array of values:
        } elseif ($mode === $modes->listValue) {
            return isset($data['value'])
                ? $data['value']
                : array();

            // Comma seperated values:
        } elseif ($mode === $modes->getPostdata) {
            return isset($data['value'])
                ? implode(', ', $data['value'])
                : null;
        }
    }

    /*-------------------------------------------------------------------------
        Filtering:
    -------------------------------------------------------------------------*/

    public function displayFilteringOptions(XMLElement &$wrapper)
    {
        if ($this->get('pre_populate_source') != null) {

            $existing_tags = $this->getToggleStates();

            if (is_array($existing_tags) && !empty($existing_tags)) {
                $taglist = new XMLElement('ul');
                $taglist->setAttribute('class', 'tags');
                $taglist->setAttribute('data-interactive', 'data-interactive');

                foreach ($existing_tags as $tag) {
                    $taglist->appendChild(
                        new XMLElement('li', General::sanitize($tag))
                    );
                }

                $wrapper->appendChild($taglist);
            }
        }
    }

    public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false)
    {
        $field_id = $this->get('id');

        if (self::isFilterRegex($data[0])) {
            $this->buildRegexSQL($data[0], array('value', 'handle'), $joins, $where);
        } elseif ($andOperation) {
            foreach ($data as $value) {
                $this->_key++;
                $value = $this->cleanValue($value);
                $joins .= "
                    LEFT JOIN
                        `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                        ON (e.id = t{$field_id}_{$this->_key}.entry_id)
                ";
                $where .= "
                    AND (
                        t{$field_id}_{$this->_key}.value = '{$value}'
                        OR t{$field_id}_{$this->_key}.handle = '{$value}'
                    )
                ";
            }
        } else {
            if (!is_array($data)) {
                $data = array($data);
            }

            foreach ($data as &$value) {
                $value = $this->cleanValue($value);
            }

            $this->_key++;
            $data = implode("', '", $data);
            $joins .= "
                LEFT JOIN
                    `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                    ON (e.id = t{$field_id}_{$this->_key}.entry_id)
            ";
            $where .= "
                AND (
                    t{$field_id}_{$this->_key}.value IN ('{$data}')
                    OR t{$field_id}_{$this->_key}.handle IN ('{$data}')
                )
            ";
        }

        return true;
    }
}
