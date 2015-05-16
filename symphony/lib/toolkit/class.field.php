<?php
/**
 * @package toolkit
 */
/**
 * The Field class represents a Symphony Field object. Fields are the building
 * blocks for Sections. All fields instances are unique and can only be used once
 * in a Symphony install. Fields have their own field table which records where
 * instances of this field type have been used in other sections and their settings.
 * They also spinoff other `tbl_entry_data_{id}` tables that actually store data for
 * entries particular to this field.
 */
class Field
{
    /**
     * The desired result when creating a field in the section editor
     * @var integer
     */
    const __OK__ = 100;

    /**
     * If an error occurring when saving a section because of this field,
     * this will be returned
     * @var integer
     */
    const __ERROR__ = 150;

    /**
     * When saving a section, if a value that is required is missing,
     * this will be returned
     * @var integer
     */
    const __MISSING_FIELDS__ = 200;

    /**
     * If a value for a setting is invalid, this will be returned
     * @var integer
     */
    const __INVALID_FIELDS__ = 220;

    /**
     * If there already is an instance of this field in this section and
     * `mustBeUnique()` returns true, this will be returned
     * @var integer
     * @see mustBeUnique()
     */
    const __DUPLICATE__ = 300;

    /**
     * Fields can returned this is an error occurred when saving the
     * field's settings that doesn't fit another `Field` constant
     * @var integer
     */
    const __ERROR_CUSTOM__ = 400;

    /**
     * If the field name is not a valid QName, this error will be returned
     * @var integer
     */
    const __INVALID_QNAME__ = 500;

    /**
     * Used by the `FieldManager` to return fields that can be toggled
     * @var integer
     */
    const __TOGGLEABLE_ONLY__ = 600;

    /**
     * Used by the `FieldManager` to return fields that can't be toggled
     * @var integer
     */
    const __UNTOGGLEABLE_ONLY__ = 700;

    /**
     * Used by the `FieldManager` to return fields that can be filtered
     * @var integer
     */
    const __FILTERABLE_ONLY__ = 800;

    /**
     * Used by the `FieldManager` to return fields that can't be filtered
     * @var integer
     */
    const __UNFILTERABLE_ONLY__ = 900;

    /**
     * Used by the `FieldManager` to just return all fields
     * @var integer
     */
    const __FIELD_ALL__ = 1000;

    /**
     * Used to manage the joins when this field used in a datasource
     * @var integer
     */
    protected $_key = 0;

    /**
     * An associative array of the settings for this `Field` instance
     * @var array
     */
    protected $_settings = array();

    /**
     * Whether this field is required inherently, defaults to false.
     * @var boolean
     */
    protected $_required = false;

    /**
     * Whether this field can be viewed on the entries table. Note
     * that this is not the same variable as the one set when saving
     * a field in the section editor, rather just the if the field has
     * the ability to be shown. Defaults to true.
     * @var boolean
     */
    protected $_showcolumn = true;

    /**
     * Whether this field has an association that should be shown on
     * the Publish Index. This does not mean that it will be, but just
     * that this field has the ability too. Defaults to false.
     * @var boolean
     */
    protected $_showassociation = false;

    /**
     * Construct a new instance of this field.
     */
    public function __construct()
    {
        $this->_handle = (strtolower(get_class($this)) == 'field' ? 'field' : strtolower(substr(get_class($this), 5)));
    }

    /**
     * Test whether this field can show the table column.
     *
     * @return boolean
     *  true if this can, false otherwise.
     */
    public function canShowTableColumn()
    {
        return $this->_showcolumn;
    }

    /**
     * Test whether this field can show the association column in
     * the Publish Index.
     *
     * @since Symphony 2.6.0
     * @return boolean
     *  true if this can, false otherwise.
     */
    public function canShowAssociationColumn()
    {
        return $this->_showassociation;
    }

    /**
     * Test whether this field can be toggled using the With Selected menu
     * on the Publish Index.
     *
     * @return boolean
     *  true if it can be toggled, false otherwise.
     */
    public function canToggle()
    {
        return false;
    }

    /**
     * Accessor to the toggle states. This default implementation returns
     * an empty array.
     *
     * @return array
     *  the array of toggle states.
     */
    public function getToggleStates()
    {
        return array();
    }

    /**
     * Toggle the field data. This default implementation always returns
     * the input data.
     *
     * @param array $data
     *   the data to toggle.
     * @param string $newState
     *   the new value to set
     * @param integer $entry_id (optional)
     *   an optional entry ID for more intelligent processing. defaults to null
     * @return array
     *   the toggled data.
     */
    public function toggleFieldData(array $data, $newState, $entry_id = null)
    {
        return $data;
    }

    /**
     * Test whether this field can be filtered. This default implementation
     * prohibits filtering. Filtering allows the XML output results to be limited
     * according to an input parameter. Subclasses should override this if
     * filtering is supported.
     *
     * @return boolean
     *  true if this can be filtered, false otherwise.
     */
    public function canFilter()
    {
        return false;
    }

    /**
     * Test whether this field can be filtered in the publish index. This default
     * implementation prohibts filtering. Publish Filtering allows the index view
     * to filter results. Subclasses should override this if
     * filtering is supported.
     *
     * @return boolean
     *  true if this can be publish-filtered, false otherwise.
     */
    public function canPublishFilter()
    {
        return $this->canFilter();
    }

    /**
     * Test whether this field can be prepopulated with data. This default
     * implementation does not support pre-population and, thus, returns false.
     *
     * @return boolean
     *  true if this can be pre-populated, false otherwise.
     */
    public function canPrePopulate()
    {
        return false;
    }

    /**
     * Test whether this field can be sorted. This default implementation
     * returns false.
     *
     * @return boolean
     *  true if this field is sortable, false otherwise.
     */
    public function isSortable()
    {
        return false;
    }

    /**
     * Test whether this field must be unique in a section, that is, only one of
     * this field's type is allowed per section. This default implementation
     * always returns false.
     *
     * @return boolean
     *  true if the content of this field must be unique, false otherwise.
     */
    public function mustBeUnique()
    {
        return false;
    }

    /**
     * Test whether this field supports data-source output grouping. This
     * default implementation prohibits grouping. Data-source grouping allows
     * clients of this field to group the XML output according to this field.
     * Subclasses should override this if grouping is supported.
     *
     * @return boolean
     *  true if this field does support data-source grouping, false otherwise.
     */
    public function allowDatasourceOutputGrouping()
    {
        return false;
    }

    /**
     * Test whether this field requires grouping. If this function returns true
     * SQL statements generated in the `EntryManager` will include the `DISTINCT` keyword
     * to only return a single row for an entry regardless of how many 'matches' it
     * might have. This default implementation returns false.
     *
     * @return boolean
     *  true if this field requires grouping, false otherwise.
     */
    public function requiresSQLGrouping()
    {
        return false;
    }

    /**
     * Test whether this field supports data-source parameter output. This
     * default implementation prohibits parameter output. Data-source
     * parameter output allows this field to be provided as a parameter
     * to other data-sources or XSLT. Subclasses should override this if
     * parameter output is supported.
     *
     * @return boolean
     *  true if this supports data-source parameter output, false otherwise.
     */
    public function allowDatasourceParamOutput()
    {
        return false;
    }

    /**
     * Accessor to the handle of this field object. The Symphony convention is
     * for field subclass names to be prefixed with field. Handle removes this prefix
     * so that the class handle can be used as the field type.
     *
     * @return string
     *  The field classname minus the field prefix.
     */
    public function handle()
    {
        return $this->_handle;
    }

    /**
     * Accessor to the name of this field object. The name may contain characters
     * that normally would be stripped in the handle while also allowing the field
     * name to be localized. If a name is not set, it will return the handle of the
     * the field
     *
     * @return string
     *  The field name
     */
    public function name()
    {
        return ($this->_name ? $this->_name : $this->_handle);
    }

    /**
     * Clean the input value using html entity encode and the database specific
     * clean methods.
     *
     * @param mixed $value
     *  the value to clean.
     * @return string
     *  the cleaned value.
     */
    public function cleanValue($value)
    {
        return html_entity_decode(Symphony::Database()->cleanValue($value));
    }

    /**
     * Fields have settings that define how that field will act in a section, including
     * if it's required, any validators, if it can be shown on the entries table etc. This
     * function will set a setting to a value.  This function will set a setting to a value
     * overwriting any existing value for this setting
     *
     * @param string $setting
     *  the setting key.
     * @param mixed $value
     *  the value of the setting.
     */
    public function set($setting, $value)
    {
        $this->_settings[$setting] = $value;
    }

    /**
     * Add or overwrite the settings of this field by providing an associative array
     * of the settings. This will do nothing if the input array is empty. If a setting is
     * omitted from the input array, it will not be unset by this function
     *
     * @param array $array
     *  the associative array of settings for this field
     */
    public function setArray(array $array = array())
    {
        if (empty($array)) {
            return;
        }

        foreach ($array as $setting => $value) {
            $this->set($setting, $value);
        }
    }

    /**
     * Fill the input data array with default values for known keys provided
     * these settings are not already set. The input array is then used to set
     * the values of the corresponding settings for this field. This function
     * is called when a section is saved.
     *
     * @param array $settings
     *  the data array to initialize if necessary.
     */
    public function setFromPOST(array $settings = array())
    {
        $settings['location'] = (isset($settings['location']) ? $settings['location'] : 'main');
        $settings['required'] = (isset($settings['required']) && $settings['required'] === 'yes' ? 'yes' : 'no');
        $settings['show_column'] = (isset($settings['show_column']) && $settings['show_column'] === 'yes' ? 'yes' : 'no');

        $this->setArray($settings);
    }

    /**
     * Accessor to the a setting by name. If no setting is provided all the
     * settings of this `Field` instance are returned.
     *
     * @param string $setting (optional)
     *  the name of the setting to access the value for. This is optional and
     *  defaults to null in which case all settings are returned.
     * @return null|mixed|array
     *  the value of the setting if there is one, all settings if the input setting
     *  was omitted or null if the setting was supplied but there is no value
     *  for that setting.
     */
    public function get($setting = null)
    {
        if (is_null($setting)) {
            return $this->_settings;
        }

        if (!isset($this->_settings[$setting])) {
            return null;
        }

        return $this->_settings[$setting];
    }

    /**
     * Unset the value of a setting by the key
     *
     * @param string $setting
     *  the key of the setting to unset.
     */
    public function remove($setting)
    {
        unset($this->_settings[$setting]);
    }

    /**
     * Just prior to the field being deleted, this function allows
     * Fields to cleanup any additional things before it is removed
     * from the section. This may be useful to remove data from any
     * custom field tables or the configuration.
     *
     * @since Symphony 2.2.1
     * @return boolean
     */
    public function tearDown()
    {
        return true;
    }

    /**
     * Allows a field to set default settings.
     *
     * @param array $settings
     *  the array of settings to populate with their defaults.
     */
    public function findDefaults(array &$settings)
    {

    }

    /**
     * Display the default settings panel, calls the `buildSummaryBlock`
     * function after basic field settings are added to the wrapper.
     *
     * @see buildSummaryBlock()
     * @param XMLElement $wrapper
     *    the input XMLElement to which the display of this will be appended.
     * @param mixed $errors
     *  the input error collection. this defaults to null.
     * @throws InvalidArgumentException
     */
    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
    {
        // Create header
        $location = ($this->get('location') ? $this->get('location') : 'main');
        $header = new XMLElement('header', null, array('class' => 'frame-header ' . $location, 'data-name' => $this->name()));
        $label = (($this->get('label')) ? $this->get('label') : __('New Field'));
        $header->appendChild(new XMLElement('h4', '<strong>' . $label . '</strong> <span class="type">' . $this->name() . '</span>'));
        $wrapper->appendChild($header);

        // Create content
        $wrapper->appendChild(Widget::Input('fields['.$this->get('sortorder').'][type]', $this->handle(), 'hidden'));

        if ($this->get('id')) {
            $wrapper->appendChild(Widget::Input('fields['.$this->get('sortorder').'][id]', $this->get('id'), 'hidden'));
        }

        $wrapper->appendChild($this->buildSummaryBlock($errors));
    }

    /**
     * Construct the html block to display a summary of this field, which is the field
     * Label and it's location within the section. Any error messages generated are
     * appended to the optional input error array. This function calls
     * `buildLocationSelect` once it is completed
     *
     * @see buildLocationSelect()
     * @param array $errors (optional)
     *    an array to append html formatted error messages to. this defaults to null.
     * @throws InvalidArgumentException
     * @return XMLElement
     *    the root XML element of the html display of this.
     */
    public function buildSummaryBlock($errors = null)
    {
        $div = new XMLElement('div');

        // Publish label
        $label = Widget::Label(__('Label'));
        $label->appendChild(
            Widget::Input('fields['.$this->get('sortorder').'][label]', $this->get('label'))
        );
        if (isset($errors['label'])) {
            $div->appendChild(Widget::Error($label, $errors['label']));
        } else {
            $div->appendChild($label);
        }

        // Handle + placement
        $group = new XMLElement('div');
        $group->setAttribute('class', 'two columns');

        $label = Widget::Label(__('Handle'));
        $label->setAttribute('class', 'column');

        $label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][element_name]', $this->get('element_name')));

        if (isset($errors['element_name'])) {
            $group->appendChild(Widget::Error($label, $errors['element_name']));
        } else {
            $group->appendChild($label);
        }

        // Location
        $group->appendChild($this->buildLocationSelect($this->get('location'), 'fields['.$this->get('sortorder').'][location]'));

        $div->appendChild($group);

        return $div;
    }

    /**
     * Build the location select widget. This widget allows users to select
     * whether this field will appear in the main content column or in the sidebar
     * when creating a new entry.
     *
     * @param string|null $selected (optional)
     *    the currently selected location, if there is one. this defaults to null.
     * @param string $name (optional)
     *    the name of this field. this is optional and defaults to `fields[location]`.
     * @param string $label_value (optional)
     *    any predefined label for this widget. this is an optional argument that defaults
     *    to null.
     * @throws InvalidArgumentException
     * @return XMLElement
     *    An XMLElement representing a `<select>` field containing the options.
     */
    public function buildLocationSelect($selected = null, $name = 'fields[location]', $label_value = null)
    {
        if (!$label_value) {
            $label_value = __('Placement');
        }

        $label = Widget::Label($label_value);
        $label->setAttribute('class', 'column');

        $options = array(
            array('main', $selected == 'main', __('Main content')),
            array('sidebar', $selected == 'sidebar', __('Sidebar'))
        );
        $label->appendChild(Widget::Select($name, $options));

        return $label;
    }

    /**
     * Construct the html widget for selecting a text formatter for this field.
     *
     * @param string $selected (optional)
     *    the currently selected text formatter name if there is one. this defaults
     *    to null.
     * @param string $name (optional)
     *    the name of this field in the form. this is optional and defaults to
     *    "fields[format]".
     * @param string $label_value
     *    the default label for the widget to construct. if null is passed in then
     *    this defaults to the localization of "Formatting".
     * @throws InvalidArgumentException
     * @return XMLElement
     *    An XMLElement representing a `<select>` field containing the options.
     */
    public function buildFormatterSelect($selected = null, $name = 'fields[format]', $label_value)
    {
        $formatters = TextformatterManager::listAll();

        if (!$label_value) {
            $label_value = __('Formatting');
        }

        $label = Widget::Label($label_value);
        $label->setAttribute('class', 'column');

        $options = array();

        $options[] = array('none', false, __('None'));

        if (!empty($formatters) && is_array($formatters)) {
            foreach ($formatters as $handle => $about) {
                $options[] = array($handle, ($selected == $handle), $about['name']);
            }
        }

        $label->appendChild(Widget::Select($name, $options));

        return $label;
    }

    /**
     * Append a validator selector to a given `XMLElement`. Note that this
     * function differs from the other two similarly named build functions in
     * that it takes an `XMLElement` to append the Validator to as a parameter,
     * and does not return anything.
     *
     * @param XMLElement $wrapper
     *    the parent element to append the XMLElement of the Validation select to,
     *  passed by reference.
     * @param string $selected (optional)
     *    the current validator selection if there is one. defaults to null if there
     *    isn't.
     * @param string $name (optional)
     *    the form element name of this field. this defaults to "fields[validator]".
     * @param string $type (optional)
     *    the type of input for the validation to apply to. this defaults to 'input'
     *    but also accepts 'upload'.
     * @param array $errors (optional)
     *    an associative array of errors
     * @throws InvalidArgumentException
     */
    public function buildValidationSelect(XMLElement &$wrapper, $selected = null, $name = 'fields[validator]', $type = 'input', array $errors = null)
    {
        include TOOLKIT . '/util.validators.php';

        $rules = ($type == 'upload' ? $upload : $validators);

        $label = Widget::Label(__('Validation Rule'));
        $label->setAttribute('class', 'column');
        $label->appendChild(new XMLElement('i', __('Optional')));
        $label->appendChild(Widget::Input($name, $selected));

        $ul = new XMLElement('ul', null, array('class' => 'tags singular', 'data-interactive' => 'data-interactive'));
        foreach ($rules as $name => $rule) {
            $ul->appendChild(new XMLElement('li', $name, array('class' => $rule)));
        }

        if (isset($errors['validator'])) {
            $div = new XMLElement('div');
            $div->appendChild($label);
            $div->appendChild($ul);

            $wrapper->appendChild(Widget::Error($div, $errors['validator']));
        } else {
            $wrapper->appendChild($label);
            $wrapper->appendChild($ul);
        }
    }

    /**
     * Append the html widget for selecting an association interface and editor
     * for this field.
     *
     * @param XMLElement $wrapper
     *    the parent XML element to append the association interface selection to,
     *    if either interfaces or editors are provided to the system.
     * @since Symphony 2.5.0
     */
    public function appendAssociationInterfaceSelect(XMLElement &$wrapper)
    {
        $wrapper->setAttribute('data-condition', 'associative');

        $interfaces = Symphony::ExtensionManager()->getProvidersOf(iProvider::ASSOCIATION_UI);
        $editors = Symphony::ExtensionManager()->getProvidersOf(iProvider::ASSOCIATION_EDITOR);

        if (!empty($interfaces) || !empty($editors)) {
            $association_context = $this->getAssociationContext();

            $group = new XMLElement('div');
            if (!empty($interfaces) && !empty($editors)) {
                $group->setAttribute('class', 'two columns');
            }

            // Create interface select
            if (!empty($interfaces)) {
                $label = Widget::Label(__('Association Interface'), null, 'column');
                $label->appendChild(new XMLElement('i', __('Optional')));

                $options = array(
                    array(null, false, __('None'))
                );
                foreach ($interfaces as $id => $name) {
                    $options[] = array($id, ($association_context['interface'] === $id), $name);
                }

                $select = Widget::Select('fields[' . $this->get('sortorder') . '][association_ui]', $options);
                $label->appendChild($select);
                $group->appendChild($label);
            }

            // Create editor select
            if (!empty($editors)) {
                $label = Widget::Label(__('Association Editor'), null, 'column');
                $label->appendChild(new XMLElement('i', __('Optional')));

                $options = array(
                    array(null, false, __('None'))
                );
                foreach ($editors as $id => $name) {
                    $options[] = array($id, ($association_context['editor'] === $id), $name);
                }

                $select = Widget::Select('fields[' . $this->get('sortorder') . '][association_editor]', $options);
                $label->appendChild($select);
                $group->appendChild($label);
            }

            $wrapper->appendChild($group);
        }
    }

    /**
     * Get association data of the current field from the page context.
     *
     * @since Symphony 2.5.0
     * @return array
     */
    public function getAssociationContext() {
        $context = Symphony::Engine()->Page->getContext();
        $associations = $context['associations']['parent'];
        $field_association = array();
        $count = 0;

        if (!empty($associations)) {
            $associationsCount = count($associations);
            for ($i = 0; $i < $associationsCount; $i++) {
                if ($associations[$i]['child_section_field_id'] == $this->get('id')) {
                    if ($count === 0) {
                        $field_association = $associations[$i];
                        $count++;
                    } else {
                        $field_association['parent_section_id'] .= '|' . $associations[$i]['parent_section_id'];
                        $field_association['parent_section_field_id'] .= '|' . $associations[$i]['parent_section_field_id'];
                    }
                }
            }
        }

        return $field_association;
    }

    /**
     * Set association data for the current field.
     *
     * @since Symphony 2.5.0
     * @param XMLElement $wrapper
     */
    public function setAssociationContext(XMLElement &$wrapper) {
        $association_context = $this->getAssociationContext();

        if (!empty($association_context)) {
            $wrapper->setAttributeArray(array(
                'data-parent-section-id' => $association_context['parent_section_id'],
                'data-parent-section-field-id' => $association_context['parent_section_field_id'],
                'data-child-section-id' => $association_context['child_section_id'],
                'data-child-section-field-id' => $association_context['child_section_field_id'],
                'data-interface' => $association_context['interface'],
                'data-editor' => $association_context['editor']
            ));
        }
    }

    /**
     * Append and set a labeled html checkbox to the input XML element if this
     * field is set as a required field.
     *
     * @param XMLElement $wrapper
     *    the parent XML element to append the constructed html checkbox to if
     *    necessary.
     * @throws InvalidArgumentException
     */
    public function appendRequiredCheckbox(XMLElement &$wrapper)
    {
        if (!$this->_required) {
            return;
        }

        $this->createCheckboxSetting($wrapper, 'required', __('Make this a required field'));
    }

    /**
     * Append the show column html widget to the input parent XML element. This
     * displays a column in the entries table or not.
     *
     * @param XMLElement $wrapper
     *    the parent XML element to append the checkbox to.
     * @throws InvalidArgumentException
     */
    public function appendShowColumnCheckbox(XMLElement &$wrapper)
    {
        if (!$this->_showcolumn) {
            return;
        }

        $this->createCheckboxSetting($wrapper, 'show_column', __('Display in entries table'));
    }

    /**
     * Append the show association html widget to the input parent XML element. This
     * widget allows fields that provide linking to hide or show the column in the linked
     * section, similar to how the Show Column functionality works, but for the linked
     * section.
     *
     * @param XMLElement $wrapper
     *    the parent XML element to append the checkbox to.
     * @param string $help (optional)
     *    a help message to show below the checkbox.
     * @throws InvalidArgumentException
     */
    public function appendShowAssociationCheckbox(XMLElement &$wrapper, $help = null)
    {
        if (!$this->_showassociation) {
            return;
        }

        $label = $this->createCheckboxSetting($wrapper, 'show_association', __('Display associations in entries table'), $help);
        $label->setAttribute('data-condition', 'associative');
    }

    /**
     * Given the setting name and the label, this helper method will add
     * the required markup for a checkbox to the given `$wrapper`.
     *
     * @since Symphony 2.5.2
     * @param XMLElement $wrapper
     *  Passed by reference, this will have the resulting markup appended to it
     * @param string $setting
     *  This will be used with $this->get() to get the existing value
     * @param string $label_description
     *  This will be localisable and displayed after the checkbox when
     *  generated.
     * @param string $help (optional)
     *    A help message to show below the checkbox.
     * @return XMLElement
     *  The Label and Checkbox that was just added to the `$wrapper`.
     */
    public function createCheckboxSetting(XMLElement &$wrapper, $setting, $label_description, $help = null)
    {
        $order = $this->get('sortorder');
        $name = "fields[$order][$setting]";

        $label = Widget::Checkbox($name, $this->get($setting), $label_description, $wrapper, $help);
        $label->addClass('column');

        return $label;
    }

    /**
     * Append the default status footer to the field settings panel.
     * Displays the required and show column checkboxes.
     *
     * @param XMLElement $wrapper
     *    the parent XML element to append the checkbox to.
     * @throws InvalidArgumentException
     */
    public function appendStatusFooter(XMLElement &$wrapper)
    {
        $fieldset = new XMLElement('fieldset');
        $div = new XMLElement('div', null, array('class' => 'two columns'));

        $this->appendRequiredCheckbox($div);
        $this->appendShowColumnCheckbox($div);

        $fieldset->appendChild($div);
        $wrapper->appendChild($fieldset);
    }

    /**
     * Check the field's settings to ensure they are valid on the section
     * editor
     *
     * @param array $errors
     *  the array to populate with the errors found.
     * @param boolean $checkForDuplicates (optional)
     *  if set to true, duplicate Field name's in the same section will be flagged
     *  as errors. Defaults to true.
     * @return integer
     *  returns the status of the checking. if errors has been populated with
     *  any errors `self::__ERROR__`, `self::__OK__` otherwise.
     */
    public function checkFields(array &$errors, $checkForDuplicates = true)
    {
        $parent_section = $this->get('parent_section');
        $label = $this->get('label');
        $element_name = $this->get('element_name');

        if (Lang::isUnicodeCompiled()) {
            $valid_name = preg_match('/^[\p{L}]([0-9\p{L}\.\-\_]+)?$/u', $element_name);
        } else {
            $valid_name = preg_match('/^[A-z]([\w\d-_\.]+)?$/i', $element_name);
        }

        if ($label === '') {
            $errors['label'] = __('This is a required field.');
        } elseif (strtolower($label) === 'id') {
            $errors['label'] = __('%s is a reserved name used by the system and is not allowed for a field handle. Try using %s instead.', array('<code>ID</code>', '<code>UID</code>'));
        }

        if ($element_name === '') {
            $errors['element_name'] = __('This is a required field.');
        } elseif ($element_name === 'id') {
            $errors['element_name'] = __('%s is a reserved name used by the system and is not allowed for a field handle. Try using %s instead.', array('<code>id</code>', '<code>uid</code>'));
        } elseif (!$valid_name) {
            $errors['element_name'] = __('Invalid element name. Must be valid %s.', array('<code>QName</code>'));
        } elseif ($checkForDuplicates) {
            if (FieldManager::fetchFieldIDFromElementName($element_name, $parent_section) !== $this->get('id')) {
                $errors['element_name'] = __('A field with that element name already exists. Please choose another.');
            }
        }

        // Check that if the validator is provided that it's a valid regular expression
        if (!is_null($this->get('validator')) && $this->get('validator') !== '') {
            if (@preg_match($this->get('validator'), 'teststring') === false) {
                $errors['validator'] = __('Validation rule is not a valid regular expression');
            }
        }

        return (!empty($errors) ? self::__ERROR__ : self::__OK__);
    }

    /**
     * Format this field value for display in the publish index tables.
     *
     * Since Symphony 2.5.0, this function will call `Field::prepareReadableValue`
     * in order to get the field's human readable value.
     *
     * @param array $data
     *  an associative array of data for this string. At minimum this requires a
     *  key of 'value'.
     * @param XMLElement $link (optional)
     *  an XML link structure to append the content of this to provided it is not
     *  null. it defaults to null.
     * @param integer $entry_id (optional)
     *  An option entry ID for more intelligent processing. defaults to null
     * @return string
     *  the formatted string summary of the values of this field instance.
     */
    public function prepareTableValue($data, XMLElement $link = null, $entry_id = null)
    {
        $value = $this->prepareReadableValue($data, $entry_id, true, __('None'));

        if ($link) {
            $link->setValue($value);

            return $link->generate();
        }

        return $value;
    }

    /**
     * Format this field value for display as readable  text value. By default, it
     * will call `Field::prepareTextValue` to get the raw text value of this field.
     *
     * If $truncate is set to true, Symphony will truncate the value to the
     * configuration setting `cell_truncation_length`.
     *
     * @since Symphony 2.5.0
     * @param array $data
     *  an associative array of data for this string. At minimum this requires a
     *  key of 'value'.
     * @param integer $entry_id (optional)
     *  An option entry ID for more intelligent processing. Defaults to null.
     * @param string $defaultValue (optional)
     *  The value to use when no plain text representation of the field's data
     *  can be made. Defaults to null.
     * @return string
     *  the readable text summary of the values of this field instance.
     */
    public function prepareReadableValue($data, $entry_id = null, $truncate = false, $defaultValue = null)
    {
        $value = $this->prepareTextValue($data, $entry_id);

        if ($truncate) {
            $max_length = Symphony::Configuration()->get('cell_truncation_length', 'symphony');
            $max_length = ($max_length ? $max_length : 75);

            $value = (General::strlen($value) <= $max_length ? $value : General::substr($value, 0, $max_length) . '…');
        }

        if (empty($value) && $defaultValue != null) {
            $value = $defaultValue;
        }

        return $value;
    }

    /**
     * Format this field value for complete display as text (string). By default,
     * it looks for the 'value' key in the $data array and strip tags from it.
     *
     * @since Symphony 2.5.0
     * @param array $data
     *  an associative array of data for this string. At minimum this requires a
     *  key of 'value'.
     * @param integer $entry_id (optional)
     *  An option entry ID for more intelligent processing. defaults to null
     * @return string
     *  the complete text representation of the values of this field instance.
     */
    public function prepareTextValue($data, $entry_id = null)
    {
        return strip_tags($data['value']);
    }

    /**
     * This is general purpose factory method that makes it easier to create the
     * markup needed in order to create an Associations Drawer XMLElement.
     *
     * @since Symphony 2.5.0
     *
     * @param string $value
     *   The value to display in the link
     * @param Entry $e
     *   The associated entry
     * @param array $parent_association
     *   An array containing information about the association
     * @param string $prepopulate
     *   A string containing prepopulate parameter to append to the association url
     *
     * @return XMLElement
     *   The XMLElement must be a li node, since it will be added an ul node.
     */
    public static function createAssociationsDrawerXMLElement($value, Entry $e, array $parent_association, $prepopulate = '')
    {
        $li = new XMLElement('li');
        $a = new XMLElement('a', $value);
        $a->setAttribute('href', SYMPHONY_URL . '/publish/' . $parent_association['handle'] . '/edit/' . $e->get('id') . '/' . $prepopulate);
        $li->appendChild($a);
        return $li;
    }

    /**
     * Format this field value for display in the Associations Drawer publish index.
     * By default, Symphony will use the return value of the `prepareReadableValue` function.
     *
     * @since Symphony 2.4
     * @since Symphony 2.5.0 The prepopulate parameter was added.
     *
     * @param Entry $e
     *   The associated entry
     * @param array $parent_association
     *   An array containing information about the association
     * @param string $prepopulate
     *   A string containing prepopulate parameter to append to the association url
     *
     * @return XMLElement
     *   The XMLElement must be a li node, since it will be added an ul node.
     */
    public function prepareAssociationsDrawerXMLElement(Entry $e, array $parent_association, $prepopulate = '')
    {
        $value = $this->prepareReadableValue($e->getData($this->get('id')), $e->get('id'));

        // fallback for compatibility since the default
        // `preparePlainTextValue` is not compatible with all fields
        // this should be removed in Symphony 3.0
        if (empty($value)) {
            $value = strip_tags($this->prepareTableValue($e->getData($this->get('id')), null, $e->get('id')));
        }

        // use our factory method to create the html
        $li = self::createAssociationsDrawerXMLElement($value, $e, $parent_association, $prepopulate);

        $li->setAttribute('class', 'field-' . $this->get('type'));

        return $li;
    }

    /**
     * Display the publish panel for this field. The display panel is the
     * interface shown to Authors that allow them to input data into this
     * field for an `Entry`.
     *
     * @param XMLElement $wrapper
     *  the XML element to append the html defined user interface to this
     *  field.
     * @param array $data (optional)
     *  any existing data that has been supplied for this field instance.
     *  this is encoded as an array of columns, each column maps to an
     *  array of row indexes to the contents of that column. this defaults
     *  to null.
     * @param mixed $flagWithError (optional)
     *  flag with error defaults to null.
     * @param string $fieldnamePrefix (optional)
     *  the string to be prepended to the display of the name of this field.
     *  this defaults to null.
     * @param string $fieldnamePostfix (optional)
     *  the string to be appended to the display of the name of this field.
     *  this defaults to null.
     * @param integer $entry_id (optional)
     *  the entry id of this field. this defaults to null.
     */
    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null)
    {

    }

    /**
     * Check the field data that has been posted from a form. This will set the
     * input message to the error message or to null if there is none. Any existing
     * message value will be overwritten.
     *
     * @param array $data
     *  the input data to check.
     * @param string $message
     *  the place to set any generated error message. any previous value for
     *  this variable will be overwritten.
     * @param integer $entry_id (optional)
     *  the optional id of this field entry instance. this defaults to null.
     * @return integer
     *  `self::__MISSING_FIELDS__` if there are any missing required fields,
     *  `self::__OK__` otherwise.
     */
    public function checkPostFieldData($data, &$message, $entry_id = null)
    {
        $message = null;

        $has_no_value = is_array($data) ? empty($data) : strlen(trim($data)) == 0;

        if ($this->get('required') === 'yes' && $has_no_value) {
            $message = __('‘%s’ is a required field.', array($this->get('label')));

            return self::__MISSING_FIELDS__;
        }

        return self::__OK__;
    }

    /**
     * Process the raw field data.
     *
     * @param mixed $data
     *  post data from the entry form
     * @param integer $status
     *  the status code resultant from processing the data.
     * @param string $message
     *  the place to set any generated error message. any previous value for
     *  this variable will be overwritten.
     * @param boolean $simulate (optional)
     *  true if this will tell the CF's to simulate data creation, false
     *  otherwise. this defaults to false. this is important if clients
     *  will be deleting or adding data outside of the main entry object
     *  commit function.
     * @param mixed $entry_id (optional)
     *  the current entry. defaults to null.
     * @return array
     *  the processed field data.
     */
    public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
    {
        $status = self::__OK__;

        return array(
            'value' => $data,
        );
    }

    /**
     * Returns the keywords that this field supports for filtering. Note
     * that no filter will do a simple 'straight' match on the value.
     *
     * @since Symphony 2.6.0
     * @return array
     */
    public function fetchFilterableOperators()
    {
        return array(
            array(
                'title' => 'is',
                'filter' => ' ',
                'help' => __('Find values that are an exact match for the given string.')
            ),
            array(
                'title' => 'contains',
                'filter' => 'regexp: ',
                'help' => __('Find values that match the given <a href="%s">MySQL regular expressions</a>.', array(
                    'http://dev.mysql.com/doc/mysql/en/Regexp.html'
                ))
            ),
            array(
                'title' => 'does not contain',
                'filter' => 'not-regexp: ',
                'help' => __('Find values that do not match the given <a href="%s">MySQL regular expressions</a>.', array(
                    'http://dev.mysql.com/doc/mysql/en/Regexp.html'
                ))
            ),
        );
    }

    /**
     * Returns the types of filter suggestion this field supports. 
     * The array may contain the following values:
     *
     * - `entry` for searching entries in the current section
     * - `association` for searching entries in associated sections
     * - `static` for searching static values
     * - `date` for searching in a calendar
     * - `parameters` for searching in parameters
     *
     * If the date type is set, only the calendar will be shown in the suggestion dropdown.
     *
     * @since Symphony 2.6.0
     * @return array
     */
    public function fetchSuggestionTypes()
    {
        return array('entry');
    }

    /**
     * Display the default data-source filter panel.
     *
     * @param XMLElement $wrapper
     *    the input XMLElement to which the display of this will be appended.
     * @param mixed $data (optional)
     *    the input data. this defaults to null.
     * @param null $errors
     *  the input error collection. this defaults to null.
     * @param string $fieldnamePrefix
     *  the prefix to apply to the display of this.
     * @param string $fieldnamePostfix
     *  the suffix to apply to the display of this.
     * @throws InvalidArgumentException
     */
    public function displayDatasourceFilterPanel(XMLElement &$wrapper, $data = null, $errors = null, $fieldnamePrefix = null, $fieldnamePostfix = null)
    {
        $wrapper->appendChild(new XMLElement('header', '<h4>' . $this->get('label') . '</h4> <span>' . $this->name() . '</span>', array(
            'data-name' => $this->get('label') . ' (' . $this->name() . ')'
        )));

        $label = Widget::Label(__('Value'));
        $input = Widget::Input('fields[filter]'.($fieldnamePrefix ? '['.$fieldnamePrefix.']' : '').'['.$this->get('id').']'.($fieldnamePostfix ? '['.$fieldnamePostfix.']' : ''), ($data ? General::sanitize($data) : null));
        $input->setAttribute('autocomplete', 'off');
        $input->setAttribute('data-search-types', 'parameters');
        $input->setAttribute('data-trigger', '{$');
        $label->appendChild($input);
        $wrapper->appendChild($label);

        $this->displayFilteringOptions($wrapper);
    }

    /**
     * Inserts tags at the bottom of the filter panel
     *
     * @since Symphony 2.6.0
     * @param XMLElement $wrapper
     */
    public function displayFilteringOptions(XMLElement &$wrapper)
    {
        // Add filter tags
        $filterTags = new XMLElement('ul');
        $filterTags->setAttribute('class', 'tags singular');
        $filterTags->setAttribute('data-interactive', 'data-interactive');

        $filters = $this->fetchFilterableOperators();
        foreach ($filters as $value) {
            $item = new XMLElement('li', $value['title']);
            $item->setAttribute('data-value', $value['filter']);

            if (isset($value['help'])) {
                $item->setAttribute('data-help', General::sanitize($value['help']));
            }

            $filterTags->appendChild($item);
        }
        $wrapper->appendChild($filterTags);

        $help = new XMLElement('p');
        $help->setAttribute('class', 'help');
        $first = array_shift($filters);
        $help->setValue($first['help']);
        $wrapper->appendChild($help);
    }

    /**
     * Default accessor for the includable elements of this field. This array
     * will populate the `Datasource` included elements. Fields that have
     * different modes will override this and add new items to the array.
     * The Symphony convention is element_name : mode. Modes allow Fields to
     * output different XML in datasources.
     *
     * @return array
     *  the array of includable elements from this field.
     */
    public function fetchIncludableElements()
    {
        return array($this->get('element_name'));
    }

    /**
     * Test whether the input string is a regular expression, by searching
     * for the prefix of `regexp:` or `not-regexp:` in the given `$string`.
     *
     * @param string $string
     *  The string to test.
     * @return boolean
     *  True if the string is prefixed with `regexp:` or `not-regexp:`, false otherwise.
     */
    protected static function isFilterRegex($string)
    {
        if (preg_match('/^regexp:/i', $string) || preg_match('/^not-?regexp:/i', $string)) {
            return true;
        }
    }

    /**
     * Builds a basic REGEXP statement given a `$filter`. This function supports
     * `regexp:` or `not-regexp:`. Users should keep in mind this function
     * uses MySQL patterns, not the usual PHP patterns, the syntax between these
     * flavours differs at times.
     *
     * @since Symphony 2.3
     * @link http://dev.mysql.com/doc/refman/5.5/en/regexp.html
     * @param string $filter
     *  The full filter, eg. `regexp: ^[a-d]`
     * @param array $columns
     *  The array of columns that need the given `$filter` applied to. The conditions
     *  will be added using `OR`.
     * @param string $joins
     *  A string containing any table joins for the current SQL fragment. By default
     *  Datasources will always join to the `tbl_entries` table, which has an alias of
     *  `e`. This parameter is passed by reference.
     * @param string $where
     *  A string containing the WHERE conditions for the current SQL fragment. This
     *  is passed by reference and is expected to be used to add additional conditions
     *  specific to this field
     */
    public function buildRegexSQL($filter, array $columns, &$joins, &$where)
    {
        $this->_key++;
        $field_id = $this->get('id');
        $filter = $this->cleanValue($filter);

        if (preg_match('/^regexp:/i', $filter)) {
            $pattern = preg_replace('/^regexp:\s*/i', null, $filter);
            $regex = 'REGEXP';
        } else {
            $pattern = preg_replace('/^not-?regexp:\s*/i', null, $filter);
            $regex = 'NOT REGEXP';
        }

        if (strlen($pattern) == 0) {
            return;
        }

        $joins .= "
            LEFT JOIN
                `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                ON (e.id = t{$field_id}_{$this->_key}.entry_id)
        ";

        $where .= "AND ( ";

        foreach ($columns as $key => $col) {
            $modifier = ($key === 0) ? '' : 'OR';

            $where .= "
                {$modifier} t{$field_id}_{$this->_key}.{$col} {$regex} '{$pattern}'
            ";
        }
        $where .= ")";
    }

    /**
     * Construct the SQL statement fragments to use to retrieve the data of this
     * field when utilized as a data source.
     *
     * @see toolkit.Datasource#__determineFilterType
     * @param array $data
     *  An array of the data that contains the values for the filter as specified
     *  in the datasource editor. The value that is entered in the datasource editor
     *  is made into an array by using + or , to separate the filter.
     * @param string $joins
     *  A string containing any table joins for the current SQL fragment. By default
     *  Datasources will always join to the `tbl_entries` table, which has an alias of
     *  `e`. This parameter is passed by reference.
     * @param string $where
     *  A string containing the WHERE conditions for the current SQL fragment. This
     *  is passed by reference and is expected to be used to add additional conditions
     *  specific to this field
     * @param boolean $andOperation (optional)
     *  This parameter defines whether the `$data` provided should be treated as
     *  AND or OR conditions. This parameter will be set to true if $data used a
     *  + to separate the values, otherwise it will be false. It is false by default.
     * @return boolean
     *  True if the construction of the SQL was successful, false otherwise.
     */
    public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false)
    {
        $field_id = $this->get('id');

        // REGEX filtering is a special case, and will only work on the first item
        // in the array. You cannot specify multiple filters when REGEX is involved.
        if (self::isFilterRegex($data[0])) {
            $this->buildRegexSQL($data[0], array('value'), $joins, $where);

            // AND operation, iterates over `$data` and uses a new JOIN for
            // every item.
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
                    AND t{$field_id}_{$this->_key}.value = '{$value}'
                ";
            }

            // Default logic, this will use a single JOIN statement and collapse
            // `$data` into a string to be used inconjuction with IN
        } else {
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
                AND t{$field_id}_{$this->_key}.value IN ('{$data}')
            ";
        }

        return true;
    }

    /**
     * Build the SQL command to append to the default query to enable
     * sorting of this field. By default this will sort the results by
     * the entry id in ascending order.
     *
     * @param string $joins
     *  the join element of the query to append the custom join sql to.
     * @param string $where
     *  the where condition of the query to append to the existing where clause.
     * @param string $sort
     *  the existing sort component of the sql query to append the custom
     *  sort sql code to.
     * @param string $order (optional)
     *  an optional sorting direction. this defaults to ascending. if this
     *  is declared either 'random' or 'rand' then a random sort is applied.
     */
    public function buildSortingSQL(&$joins, &$where, &$sort, $order = 'ASC')
    {
        if (in_array(strtolower($order), array('random', 'rand'))) {
            $sort = 'ORDER BY RAND()';
        } else {
            $joins .= "LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
            $sort = sprintf('ORDER BY `ed`.`value` %s', $order);
        }
    }

    /**
     * Default implementation of record grouping. This default implementation
     * will throw an `Exception`. Thus, clients must overload this method
     * for grouping to be successful.
     *
     * @throws Exception
     * @param array $records
     *  the records to group.
     */
    public function groupRecords($records)
    {
        throw new Exception(
            __('Data source output grouping is not supported by the %s field', array('<code>' . $this->get('label') . '</code>'))
        );
    }

    /**
     * Function to format this field if it chosen in a data-source to be
     * output as a parameter in the XML.
     *
     * Since Symphony 2.5.0, it will defaults to `prepareReadableValue` return value.
     *
     * @param array $data
     *  The data for this field from it's `tbl_entry_data_{id}` table
     * @param integer $entry_id
     *  The optional id of this field entry instance
     * @return string|array
     *  The formatted value to be used as the parameter. Note that this can be
     *  an array or a string. When returning multiple values use array, otherwise
     *  use string.
     */
    public function getParameterPoolValue(array $data, $entry_id = null)
    {
        return $this->prepareReadableValue($data, $entry_id);
    }

    /**
     * Append the formatted XML output of this field as utilized as a data source.
     *
     * Since Symphony 2.5.0, it will defaults to `prepareReadableValue` return value.
     *
     * @param XMLElement $wrapper
     *  the XML element to append the XML representation of this to.
     * @param array $data
     *  the current set of values for this field. the values are structured as
     *  for displayPublishPanel.
     * @param boolean $encode (optional)
     *  flag as to whether this should be html encoded prior to output. this
     *  defaults to false.
     * @param string $mode
     *   A field can provide ways to output this field's data. For instance a mode
     *  could be 'items' or 'full' and then the function would display the data
     *  in a different way depending on what was selected in the datasource
     *  included elements.
     * @param integer $entry_id (optional)
     *  the identifier of this field entry instance. defaults to null.
     */
    public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null)
    {
        $wrapper->appendChild(new XMLElement($this->get('element_name'), ($encode ?
                              General::sanitize($this->prepareReadableValue($data, $entry_id)) :
                              $this->prepareReadableValue($data, $entry_id))));
    }

    /**
     * The default method for constructing the example form markup containing this
     * field when utilized as part of an event. This displays in the event documentation
     * and serves as a basic guide for how markup should be constructed on the
     * `Frontend` to save this field
     *
     * @throws InvalidArgumentException
     * @return XMLElement
     *  a label widget containing the formatted field element name of this.
     */
    public function getExampleFormMarkup()
    {
        $label = Widget::Label($this->get('label'));
        $label->appendChild(Widget::Input('fields['.$this->get('element_name').']'));

        return $label;
    }

    /**
     * Commit the settings of this field from the section editor to
     * create an instance of this field in a section.
     *
     * @return boolean
     *  true if the commit was successful, false otherwise.
     */
    public function commit()
    {
        $fields = array();

        $fields['label'] = General::sanitize($this->get('label'));
        $fields['element_name'] = ($this->get('element_name') ? $this->get('element_name') : Lang::createHandle($this->get('label')));
        $fields['parent_section'] = $this->get('parent_section');
        $fields['location'] = $this->get('location');
        $fields['required'] = $this->get('required');
        $fields['type'] = $this->_handle;
        $fields['show_column'] = $this->get('show_column');
        $fields['sortorder'] = (string)$this->get('sortorder');

        if ($id = $this->get('id')) {
            return FieldManager::edit($id, $fields);
        } elseif ($id = FieldManager::add($fields)) {
            $this->set('id', $id);
            $this->createTable();
            return true;
        }

        return false;
    }

    /**
     * The default field table construction method. This constructs the bare
     * minimum set of columns for a valid field table. Subclasses are expected
     * to overload this method to create a table structure that contains
     * additional columns to store the specific data created by the field.
     *
     * @throws DatabaseException
     * @return boolean
     */
    public function createTable()
    {
        return Symphony::Database()->query(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
              `id` int(11) unsigned NOT null auto_increment,
              `entry_id` int(11) unsigned NOT null,
              `value` varchar(255) default null,
              PRIMARY KEY  (`id`),
              KEY `entry_id` (`entry_id`),
              KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
        );
    }

    /**
     * Remove the entry data of this field from the database.
     *
     * @param integer|array $entry_id
     *    the ID of the entry, or an array of entry ID's to delete.
     * @param array $data (optional)
     *    The entry data provided for fields to do additional cleanup
     *  This is an optional argument and defaults to null.
     * @throws DatabaseException
     * @return boolean
     *    Returns true after the cleanup has been completed
     */
    public function entryDataCleanup($entry_id, $data = null)
    {
        $where = is_array($entry_id)
            ? " `entry_id` IN (" . implode(',', $entry_id) . ") "
            : " `entry_id` = '$entry_id' ";

        Symphony::Database()->delete('tbl_entries_data_' . $this->get('id'), $where);

        return true;
    }

    /**
     * Accessor to the associated entry search value for this field
     * instance. This default implementation simply returns `$data`
     *
     * @param array $data
     *  the data from which to construct the associated search entry value, this is usually
     *  Entry with the `$parent_entry_id` value's data.
     * @param integer $field_id (optional)
     *  the ID of the field that is the parent in the relationship
     * @param integer $parent_entry_id (optional)
     *  the ID of the entry from the parent section in the relationship
     * @return array|string
     *  Defaults to returning `$data`, but overriding implementation should return
     *  a string
     */
    public function fetchAssociatedEntrySearchValue($data, $field_id = null, $parent_entry_id = null)
    {
        return $data;
    }

    /**
     * Fetch the count of the associated entries given a `$value`.
     *
     * @see toolkit.Field#fetchAssociatedEntrySearchValue()
     * @param mixed $value
     *  the value to find the associated entry count for, this usually comes from
     *  the `fetchAssociatedEntrySearchValue` function.
     * @return void|integer
     *  this default implementation returns void. overriding implementations should
     *  return an integer.
     */
    public function fetchAssociatedEntryCount($value)
    {

    }

    /**
     * Fetch the Entry ID's associated with this field instance given a `$value`,
     * where the `$value` can be anything. This function is unused by Symphony core
     * but should be implemented by Fields that maintain relationships.
     *
     * @param mixed $value
     *  the value to find the associated entry ID's for.
     * @return void|array
     *  this default implementation returns void. overriding implementations should
     *  return an array of the associated entry ids.
     * @deprecated Since Symphony 2.5.0 this method is not called anymore in the core. Please use
     *  `Field::findRelatedEntries` and `Field::findParentRelatedEntries` instead. This method
	 *  will be removed in Symphony 3.0
     */
    public function fetchAssociatedEntryIDs($value)
    {

    }

    /**
     * Find related entries from a linking field's data table. Default implementation uses
     * column names `entry_id` and `relation_id` as with the Select Box Link
     *
     * @since Symphony 2.5.0
     *
     * @param  integer $entry_id
     * @param  integer $parent_field_id
     * @return array
     */
    public function findRelatedEntries($entry_id, $parent_field_id) {
        try {
            $ids = Symphony::Database()->fetchCol('entry_id', sprintf("
                SELECT `entry_id`
                FROM `tbl_entries_data_%d`
                WHERE `relation_id` = %d
                AND `entry_id` IS NOT NULL
            ", $this->get('id'), $entry_id));
        }
        catch(Exception $e){
            return array();
        }

        return $ids;
    }

    /**
     * Find related entries for the current field. Default implementation uses
     * column names `entry_id` and `relation_id` as with the Select Box Link
     *
     * @since Symphony 2.5.0
     *
     * @param  integer $field_id
     * @param  integer $entry_id
     * @return array
     */
    public function findParentRelatedEntries($field_id, $entry_id) {
        try {
            $ids = Symphony::Database()->fetchCol('relation_id', sprintf("
                SELECT `relation_id`
                FROM `tbl_entries_data_%d`
                WHERE `entry_id` = %d
                AND `relation_id` IS NOT NULL
            ", $field_id, $entry_id));
        }
        catch(Exception $e){
            return array();
        }

        return $ids;
    }
}
