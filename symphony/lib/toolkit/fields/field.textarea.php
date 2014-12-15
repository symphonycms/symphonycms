<?php

/**
 * @package toolkit
 */

/**
 * A simple Textarea field that essentially maps to HTML's `<textarea/>`.
 */
class fieldTextarea extends Field implements ExportableField, ImportableField
{
    public function __construct()
    {
        parent::__construct();
        $this->_name = __('Textarea');
        $this->_required = true;

        // Set default
        $this->set('show_column', 'no');
        $this->set('required', 'no');
    }

    /*-------------------------------------------------------------------------
        Definition:
    -------------------------------------------------------------------------*/

    public function canFilter()
    {
        return true;
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
              `value` MEDIUMTEXT,
              `value_formatted` MEDIUMTEXT,
              PRIMARY KEY  (`id`),
              UNIQUE KEY `entry_id` (`entry_id`),
              FULLTEXT KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
        );
    }

/*-------------------------------------------------------------------------
    Utilities:
-------------------------------------------------------------------------*/

    protected function __applyFormatting($data, $validate = false, &$errors = null)
    {
        $result = '';

        if ($this->get('formatter')) {
            $formatter = TextformatterManager::create($this->get('formatter'));
            $result = $formatter->run($data);
        }

        if ($validate === true) {
            include_once(TOOLKIT . '/class.xsltprocess.php');

            if (!General::validateXML($result, $errors, false, new XsltProcess)) {
                $result = html_entity_decode($result, ENT_QUOTES, 'UTF-8');
                $result = $this->__replaceAmpersands($result);

                if (!General::validateXML($result, $errors, false, new XsltProcess)) {
                    return false;
                }
            }
        }

        return $result;
    }

    private function __replaceAmpersands($value)
    {
        return preg_replace('/&(?!(#[0-9]+|#x[0-9a-f]+|amp|lt|gt);)/i', '&amp;', trim($value));
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    public function findDefaults(array &$settings)
    {
        if (!isset($settings['size'])) {
            $settings['size'] = 15;
        }
    }

    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
    {
        parent::displaySettingsPanel($wrapper, $errors);

        // Textarea Size
        $label = Widget::Label(__('Number of default rows'));
        $label->setAttribute('class', 'column');
        $input = Widget::Input('fields['.$this->get('sortorder').'][size]', (string)$this->get('size'));
        $label->appendChild($input);

        $div = new XMLElement('div', null, array('class' => 'two columns'));
        $div->appendChild($label);
        $div->appendChild($this->buildFormatterSelect($this->get('formatter'), 'fields['.$this->get('sortorder').'][formatter]', __('Text Formatter')));
        $wrapper->appendChild($div);

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

        if ($this->get('formatter') != 'none') {
            $fields['formatter'] = $this->get('formatter');
        }

        $fields['size'] = $this->get('size');

        return FieldManager::saveSettings($id, $fields);
    }

    /*-------------------------------------------------------------------------
        Publish:
    -------------------------------------------------------------------------*/

    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null)
    {
        $label = Widget::Label($this->get('label'));

        if ($this->get('required') !== 'yes') {
            $label->appendChild(new XMLElement('i', __('Optional')));
        }

        $value = isset($data['value']) ? $data['value'] : null;
        $textarea = Widget::Textarea('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (int)$this->get('size'), 50, (strlen($value) != 0 ? General::sanitize($value) : null));

        if ($this->get('formatter') != 'none') {
            $textarea->setAttribute('class', $this->get('formatter'));
        }

        /**
         * Allows developers modify the textarea before it is rendered in the publish forms
         *
         * @delegate ModifyTextareaFieldPublishWidget
         * @param string $context
         * '/backend/'
         * @param Field $field
         * @param Widget $label
         * @param Widget $textarea
         */
        Symphony::ExtensionManager()->notifyMembers('ModifyTextareaFieldPublishWidget', '/backend/', array(
            'field' => &$this,
            'label' => &$label,
            'textarea' => &$textarea
        ));

        $label->appendChild($textarea);

        if ($flagWithError != null) {
            $wrapper->appendChild(Widget::Error($label, $flagWithError));
        } else {
            $wrapper->appendChild($label);
        }
    }

    public function checkPostFieldData($data, &$message, $entry_id = null)
    {
        $message = null;

        if ($this->get('required') === 'yes' && strlen(trim($data)) == 0) {
            $message = __('‘%s’ is a required field.', array($this->get('label')));
            return self::__MISSING_FIELDS__;
        }

        if ($this->__applyFormatting($data, true, $errors) === false) {
            $message = __('‘%s’ contains invalid XML.', array($this->get('label'))) . ' ' . __('The following error was returned:') . ' <code>' . $errors[0]['message'] . '</code>';
            return self::__INVALID_FIELDS__;
        }

        return self::__OK__;
    }

    public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
    {
        $status = self::__OK__;

        if (strlen(trim($data)) == 0) {
            return array();
        }

        $result = array(
            'value' => $data
        );

        $result['value_formatted'] = $this->__applyFormatting($data, true, $errors);

        if ($result['value_formatted'] === false) {
            // Run the formatter again, but this time do not validate. We will sanitize the output
            $result['value_formatted'] = General::sanitize($this->__applyFormatting($data));
        }

        return $result;
    }

    /*-------------------------------------------------------------------------
        Output:
    -------------------------------------------------------------------------*/

    public function fetchIncludableElements()
    {
        if ($this->get('formatter')) {
            return array(
                $this->get('element_name') . ': formatted',
                $this->get('element_name') . ': unformatted'
            );
        }

        return array(
            $this->get('element_name')
        );
    }

    public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null)
    {
        $attributes = array();

        if (!is_null($mode)) {
            $attributes['mode'] = $mode;
        }

        if ($mode == 'formatted') {
            if ($this->get('formatter') && isset($data['value_formatted'])) {
                $value = $data['value_formatted'];
            } else {
                $value = $this->__replaceAmpersands($data['value']);
            }

            $wrapper->appendChild(
                new XMLElement(
                    $this->get('element_name'),
                    ($encode ? General::sanitize($value) : $value),
                    $attributes
                )
            );
        } elseif ($mode == null || $mode == 'unformatted') {
            $value = !empty($data['value'])
                ? sprintf('<![CDATA[%s]]>', str_replace(']]>', ']]]]><![CDATA[>', $data['value']))
                : $data['value'];

            $wrapper->appendChild(
                new XMLElement($this->get('element_name'), $value, $attributes)
            );
        }
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
            'getHandle' =>      ExportableField::HANDLE,
            'getFormatted' =>   ExportableField::FORMATTED,
            'getUnformatted' => ExportableField::UNFORMATTED,
            'getPostdata' =>    ExportableField::POSTDATA
        );
    }

    /**
     * Give the field some data and ask it to return a value using one of many
     * possible modes.
     *
     * @param mixed $data
     * @param integer $mode
     * @param integer $entry_id
     * @return string|null
     */
    public function prepareExportValue($data, $mode, $entry_id = null)
    {
        $modes = (object)$this->getExportModes();

        // Export handles:
        if ($mode === $modes->getHandle) {
            if (isset($data['handle'])) {
                return $data['handle'];
            } elseif (isset($data['value'])) {
                return Lang::createHandle($data['value']);
            }

            // Export unformatted:
        } elseif ($mode === $modes->getUnformatted || $mode === $modes->getPostdata) {
            return isset($data['value'])
                ? $data['value']
                : null;

            // Export formatted:
        } elseif ($mode === $modes->getFormatted) {
            if (isset($data['value_formatted'])) {
                return $data['value_formatted'];
            } elseif (isset($data['value'])) {
                return General::sanitize($data['value']);
            }
        }

        return null;
    }

    /*-------------------------------------------------------------------------
        Filtering:
    -------------------------------------------------------------------------*/

    public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false)
    {
        $field_id = $this->get('id');

        if (self::isFilterRegex($data[0])) {
            $this->buildRegexSQL($data[0], array('value'), $joins, $where);
        } else {
            if (is_array($data)) {
                $data = $data[0];
            }

            $this->_key++;
            $data = $this->cleanValue($data);
            $joins .= "
                LEFT JOIN
                    `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                    ON (e.id = t{$field_id}_{$this->_key}.entry_id)
            ";
            $where .= "
                AND MATCH (t{$field_id}_{$this->_key}.value) AGAINST ('{$data}' IN BOOLEAN MODE)
            ";
        }

        return true;
    }

    /*-------------------------------------------------------------------------
        Events:
    -------------------------------------------------------------------------*/

    public function getExampleFormMarkup()
    {
        $label = Widget::Label($this->get('label'));
        $label->appendChild(Widget::Textarea('fields['.$this->get('element_name').']', (int)$this->get('size'), 50));

        return $label;
    }
}
