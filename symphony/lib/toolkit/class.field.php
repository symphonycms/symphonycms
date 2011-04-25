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
	Class Field{
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
		 * the mustBeUnique function returns true, this will be returned
		 * @var integer
		 */
		const __DUPLICATE__ = 300;

		/**
		 * Fields can returned this is an error occurred when saving the
		 * field's settings that doesn't fit another Field constant
		 * @var integer
		 */
		const __ERROR_CUSTOM__ = 400;

		/**
		 * If the field name is not a valid QName, this error will be returned
		 * @var integer
		 */
		const __INVALID_QNAME__ = 500;

		/**
		 * Used by the FieldManager to return fields that can be toggled
		 * @var integer
		 */
		const __TOGGLEABLE_ONLY__ = 600;

		/**
		 * Used by the FieldManager to return fields that can't be toggled
		 * @var integer
		 */
		const __UNTOGGLEABLE_ONLY__ = 700;

		/**
		 * Used by the FieldManager to return fields that can be filtered
		 * @var integer
		 */
		const __FILTERABLE_ONLY__ = 800;

		/**
		 * Used by the FieldManager to return fields that can't be filtered
		 * @var integer
		 */
		const __UNFILTERABLE_ONLY__ = 900;

		/**
		 * Used by the FieldManager to just return all fields
		 * @var integer
		 */
		const __FIELD_ALL__ = 1000;

		/**
		 * Used to manage the joins when this field used in a datasource
		 * @var integer
		 */
		protected $_key = 0;

		/**
		 * An associative array of the settings for this Field instance
		 * @var array
		 * @deprecated This variable will be renamed to `$_settings` in the next major
		 *  release.
		 */
		protected $_fields = array();

		/**
		 * The class that initialised the Field, usually the FieldManager
		 */
		protected $_Parent;

		/**
		 * An instance of the Symphony class, either Frontend or Administration
		 *
		 * @deprecated This will be removed in the next major version of Symphony.
		 * The preferred way to access the Symphony instance is via `Symphony::Engine()`
		 * @var Symphony
		 */
		protected $_engine;

		/**
		 * A pointer to the `Symphony::Database()`.
		 *
		 * @deprecated This is deprecated and will be removed in the next major version
		 * of Symphony. The preferred way to access the Database object is via
		 * `Symphony::Database()`
		 * @var MySQL
		 */
        protected $Database;

		/**
		 * Whether this field is required inherently, defaults to false.
		 * @var boolean
		 */
		protected $_required = false;

		/**
		 * Whether this field can be viewed on the entries tabletable. Note
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
		 *
		 * @param mixed $parent
		 *  The class that created this Field object, usually the FieldManager,
		 *  passed by reference.
		 */
		public function __construct(&$parent){
			$this->_Parent = $parent;
			$this->_engine = Symphony::Engine();
			$this->_handle = (strtolower(get_class($this)) == 'field' ? 'field' : strtolower(substr(get_class($this), 5)));
			$this->Database = Symphony::Database();
		}

		/**
		 * Test whether this field can show the table column.
		 *
		 * @return boolean
		 *	true if this can, false otherwise.
		 */
		public function canShowTableColumn(){
			return $this->_showcolumn;
		}

		/**
		 * Test whether this field can be toggled using the With Selected menu
		 * on the Publish Index.
		 *
		 * @return boolean
		 *	true if it can be toggled, false otherwise.
		 */
		public function canToggle(){
			return false;
		}

		/**
		 * Accessor to the toggle states. This default implementation returns
		 * an empty array.
		 *
		 * @return array
		 *	the array of toggle states.
		 */
		public function getToggleStates(){
			return array();
		}

		/**
		 * Toggle the field data. This default implementation always returns
		 * the input data.
		 *
		 * @param array $data
		 *	 the data to toggle.
		 * @param string $newState
		 *	 the new value to set
		 * @return array
		 *	 the toggled data.
		 */
		public function toggleFieldData(Array $data, $newState){
			return $data;
		}

		/**
		 * Test whether this field can be filtered. This default implementation
		 * prohibits filtering. Filtering allows the xml output results to be limited
		 * according to an input parameter. Subclasses should override this if
		 * filtering is supported.
		 *
		 * @return boolean
		 *	true if this can be filtered, false otherwise.
		 */
		public function canFilter(){
			return false;
		}

		/**
		 * Test whether this field can be imported. This default implementation
		 * prohibits importing. Subclasses should override this is importing is
		 * supported.
		 *
		 * @deprecated This function will be removed in the next major release. It
		 *  is unused by Symphony.
		 * @return boolean
		 *	true if this can be imported, false otherwise.
		 */
		public function canImport(){
			return false;
		}

		/**
		 * Test whether this field can be prepopulated with data. This default
		 * implementation does not support pre-population and, thus, returns false.
		 *
		 * @return boolean
		 *	true if this can be pre-populated, false otherwise.
		 */
		public function canPrePopulate(){
			return false;
		}

		/**
		 * Test whether this field can be sorted. This default implementation
		 * returns false.
		 *
		 * @return boolean
		 *	true if this field is sortable, false otherwise.
		 */
		public function isSortable(){
			return false;
		}

		/**
		 * Test whether this field must be unique in a section, that is, only one of
		 * this field's type is allowed per section. This default implementation
		 * always returns false.
		 *
		 * @return boolean
		 *	true if the content of this field must be unique, false otherwise.
		 */
		public function mustBeUnique(){
			return false;
		}

		/**
		 * Test whether this field supports data-source output grouping. This
		 * default implementation prohibits grouping. Data-source grouping allows
		 * clients of this field to group the xml output according to this field.
		 * Subclasses should override this if grouping is supported.
		 *
		 * @return boolean
		 *	true if this field does support data-source grouping, false otherwise.
		 */
		public function allowDatasourceOutputGrouping(){
			return false;
		}

		/**
		 * Test whether this field requires grouping. This default implementation
		 * returns false.
		 *
		 * @return boolean
		 *	true if this field requires grouping, false otherwise.
		 */
		public function requiresSQLGrouping(){
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
		 *	true if this supports data-source parameter output, false otherwise.
		 */
		public function allowDatasourceParamOutput(){
			return false;
		}

		/**
		 * Accessor to the handle of this field object. The Symphony convention is
		 * for field subclass names to be prefixed with field. Handle removes this prefix
		 * so that the class handle can be used as the field type.
		 *
		 * @return string
		 *	The field classname minus the field prefix.
		 */
		public function handle(){
			return $this->_handle;
		}

		/**
		 * Accessor to the name of this field object. The name may contain characters
		 * that normally would be stripped in the handle while also allowing the field
		 * name to be localized. If a name is not set, it will return the handle of the
		 * the field
		 *
		 * @return string
		 *	The field name
		 */
		public function name(){
			return ($this->_name ? $this->_name : $this->_handle);
		}

		/**
		 * Clean the input value using html entity encode and the database specific
		 * clean methods.
		 *
		 * @param mixed $value
		 *	the value to clean.
		 * @return string
		 *	the cleaned value.
		 */
		public function cleanValue($value) {
			return html_entity_decode(Symphony::Database()->cleanValue($value));
		}

		/**
		 * Fields have settings that define how that field will act in a section, including
		 * if it's required, any validators, if it can be shown on the entries tableetc. This
		 * function will set a setting to a value.  This function will set a setting to a value
		 * overwriting any existing value for this setting
		 *
		 * @param string $setting
		 *	the setting key.
		 * @param mixed $value
		 *	the value of the setting.
		 */
		public function set($setting, $value){
			$this->_fields[$setting] = $value;
		}

		/**
		 * Add or overwrite the settings of this field by providing an associative array
		 * of the settings. This will do nothing if the input array is empty. If a setting is
		 * omitted from the input array, it will not be unset by this function
		 *
		 * @param array $array
		 *	the associative array of settings for this field
		 */
		public function setArray(Array $array = array()){
			if(empty($array)) return;

			foreach($array as $setting => $value) {
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
		 *	the data array to initialize if necessary.
		 */
		public function setFromPOST(Array $settings = array()) {
			$settings['location'] = (isset($settings['location']) ? $settings['location'] : 'main');
			$settings['required'] = (isset($settings['required']) && $settings['required'] == 'yes' ? 'yes' : 'no');
			$settings['show_column'] = (isset($settings['show_column']) && $settings['show_column'] == 'yes' ? 'yes' : 'no');

			$this->setArray($settings);
		}

		/**
		 * Accessor to the a setting by name. If no setting is provided all the
		 * settings of this Field instance are returned.
		 *
		 * @param string $setting (optional)
		 *	the name of the setting to access the value for. This is optional and
		 *	defaults to null in which case all settings are returned.
		 * @return null|mixed|array
		 *	the value of the setting if there is one, all settings if the input setting
		 * was omitted or null if the setting was supplied but there is no value
		 * for that setting.
		 */
		public function get($setting = null){
			if(is_null($setting)) return $this->_fields;

			if (!isset($this->_fields[$setting])) return null;

			return $this->_fields[$setting];
		}

		/**
		 * Unset the value of a setting by the key
		 *
		 * @param string $setting
		 *  the key of the setting to unset.
		 */
		public function remove($setting){
			unset($this->_fields[$setting]);
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
		public function tearDown(){
			return true;
		}

		/**
		 * Allows a field to set default settings.
		 *
		 * @param array $settings
		 *	the array of settings to populate with their defaults.
		 */
		public function findDefaults(Array &$settings){}

		/**
		 * Display the default settings panel, calls the `buildSummaryBlock`
		 * function after basic field settings are added to the wrapper.
		 *
		 * @see buildSummaryBlock()
		 * @param XMLElement $wrapper
		 *	the input XMLElement to which the display of this will be appended.
		 * @param mixed errors (optional)
		 *	the input error collection. this defaults to null.
		 */
		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null){
			$wrapper->appendChild(new XMLElement('h4', $this->name()));
			$wrapper->appendChild(Widget::Input('fields['.$this->get('sortorder').'][type]', $this->handle(), 'hidden'));
			if($this->get('id')) $wrapper->appendChild(Widget::Input('fields['.$this->get('sortorder').'][id]', $this->get('id'), 'hidden'));

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
		 *	an array to append html formatted error messages to. this defaults to null.
		 * @return XMLElement
		 *	the root xml element of the html display of this.
		 */
		public function buildSummaryBlock($errors = null){
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');

			$label = Widget::Label(__('Label'));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][label]', $this->get('label')));
			if(isset($errors['label'])) $div->appendChild(Widget::wrapFormElementWithError($label, $errors['label']));
			else $div->appendChild($label);

			$div->appendChild($this->buildLocationSelect($this->get('location'), 'fields['.$this->get('sortorder').'][location]'));

			return $div;
		}

		/**
		 * Build the location select widget. This widget allows users to select
		 * whether this field will appear in the main content column or in the sidebar
		 * when creating a new entry.
		 *
		 * @param string $selection (optional)
		 *	the currently selected location, if there is one. this defaults to null.
		 * @param string $name (optional)
		 *	the name of this field. this is optional and defaults to `fields[location]`.
		 * @param string $label_value (optional)
		 *	any predefined label for this widget. this is an optional argument that defaults
		 *	to null.
		 * @return XMLElement
		 *	An XMLElement representing a `<select>` field containing the options.
		 */
		public function buildLocationSelect($selected = null, $name = 'fields[location]', $label_value = null) {
			if (!$label_value) $label_value = __('Placement');

			$label = Widget::Label($label_value);
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
		 *	the currently selected text formatter name if there is one. this defaults
		 *	to null.
		 * @param string $name (optional)
		 *	the name of this field in the form. this is optional and defaults to
		 *	"fields[format]".
		 * @param string $label_value
		 *	the default label for the widget to construct. if null is passed in then
		 *	this defaults to the localization of "Formatting".
		 * @return XMLElement
		 *	An XMLElement representing a `<select>` field containing the options.
		 */
		public function buildFormatterSelect($selected = null, $name='fields[format]', $label_value){

			include_once(TOOLKIT . '/class.textformattermanager.php');

			$TFM = new TextformatterManager(Administration::instance());
			$formatters = $TFM->listAll();

			if(!$label_value) $label_value = __('Formatting');
			$label = Widget::Label($label_value);

			$options = array();

			$options[] = array('none', false, __('None'));

			if(!empty($formatters) && is_array($formatters)){
				foreach($formatters as $handle => $about) {
					$options[] = array($handle, ($selected == $handle), $about['name']);
				}
			}

			$label->appendChild(Widget::Select($name, $options));

			return $label;
		}

		/**
		 * Append a validator selector to a given XMLElement. Note that this 
		 * function differs from the other two similarly named build functions in 
		 * that it takes an XMLElement to append the Validator to as a parameter,
		 * and does not return anything.
		 *
		 * @param XMLElement $wrapper
		 *	the parent element to append the XMLElement of the Validation select to,
		 *  passed by reference.
		 * @param string $selection (optional)
		 *	the current validator selection if there is one. defaults to null if there
		 *	isn't.
		 * @param string $name (optional)
		 *	the form element name of this field. this defaults to "fields[validator]".
		 * @param string $type (optional)
		 *	the type of input for the validation to apply to. this defaults to 'input'
		 *	but also accepts 'upload'.
		 */
		public function buildValidationSelect(XMLElement &$wrapper, $selected = null, $name='fields[validator]', $type='input'){

			include(TOOLKIT . '/util.validators.php');
			$rules = ($type == 'upload' ? $upload : $validators);

			$label = Widget::Label(__('Validation Rule'));
			$label->appendChild(new XMLElement('i', __('Optional')));
			$label->appendChild(Widget::Input($name, $selected));
			$wrapper->appendChild($label);

			$ul = new XMLElement('ul', NULL, array('class' => 'tags singular'));
			foreach($rules as $name => $rule) $ul->appendChild(new XMLElement('li', $name, array('class' => $rule)));
			$wrapper->appendChild($ul);

		}

		/**
		 * Append and set a labelled html checkbox to the input xml element if this
		 * field is set as a required field.
		 *
		 * @param XMLElement $wrapper
		 *	the parent xml element to append the constructed html checkbox to if
		 *	necessary.
		 */
		public function appendRequiredCheckbox(XMLElement &$wrapper) {
			if (!$this->_required) return;

			$order = $this->get('sortorder');
			$name = "fields[{$order}][required]";

			$wrapper->appendChild(Widget::Input($name, 'no', 'hidden'));

			$label = Widget::Label();
			$input = Widget::Input($name, 'yes', 'checkbox');

			if ($this->get('required') == 'yes') $input->setAttribute('checked', 'checked');

			$label->setValue(__('%s Make this a required field', array($input->generate())));

			$wrapper->appendChild($label);
		}

		/**
		 * Append the show column html widget to the input parent xml element. This
		 * displays a column in the entries table or not.
		 *
		 * @param XMLElement $wrapper
		 *	the parent xml element to append the checkbox to.
		 */
		public function appendShowColumnCheckbox(XMLElement &$wrapper) {
			if (!$this->_showcolumn) return;

			$order = $this->get('sortorder');
			$name = "fields[{$order}][show_column]";

			$wrapper->appendChild(Widget::Input($name, 'no', 'hidden'));

			$label = Widget::Label();
			$label->setAttribute('class', 'meta');
			$input = Widget::Input($name, 'yes', 'checkbox');

			if ($this->get('show_column') == 'yes') $input->setAttribute('checked', 'checked');

			$label->setValue(__('%s Display field’s value in entries table', array($input->generate())));

			$wrapper->appendChild($label);
		}

		/**
		 * Append the show association html widget to the input parent xml element. This
		 * widget allows fields that provide linking to hide or show the column in the linked
		 * section, similar to how the Show Column functionality works, but for the linked
		 * section.
		 *
		 * @param XMLElement $wrapper
		 *	the parent xml element to append the checkbox to.
		 * @param string $help (optional)
		 *	a help message to show below the checkbox.
		 */
		public function appendShowAssociationCheckbox(XMLElement &$wrapper, $help = null) {
			if(!$this->_showassociation) return;

			$order = $this->get('sortorder');
			$name = "fields[{$order}][show_association]";

			$wrapper->appendChild(Widget::Input($name, 'no', 'hidden'));

			$label = Widget::Label();
			$label->setAttribute('class', 'meta');
			$input = Widget::Input($name, 'yes', 'checkbox');

			if ($this->get('show_association') == 'yes') $input->setAttribute('checked', 'checked');

			$label->setValue(__('%s Display relationship in linked sections', array($input->generate())));

			if ($help) $label->appendChild(new XMLElement('i', $help));

			$wrapper->appendChild($label);
		}

		/**
		 * Check the field's settings to ensure they are valid on the section
		 * editor
		 *
		 * @param array $errors
		 *	the array to populate with the errors found.
		 * @param boolean $checkForDuplicates (optional)
		 *	if set to true, duplicate Field name's in the same section will be flagged 
		 *  as errors. Defaults to true.
		 * @return integer
		 *	returns the status of the checking. if errors has been populated with
		 *	any errors `self::__ERROR__`, `self::__OK__` otherwise.
		 */
		public function checkFields(Array &$errors, $checkForDuplicates = true) {
			$parent_section = $this->get('parent_section');
			$element_name = $this->get('element_name');

			if ($this->get('label') == '') {
				$errors['label'] = __('This is a required field.');
			}

			if ($this->get('element_name') == '') {
				$errors['element_name'] = __('This is a required field.');
			}
			elseif (!preg_match('/^[A-z]([\w\d-_\.]+)?$/i', $this->get('element_name'))) {
				$errors['element_name'] = __('Invalid element name. Must be valid QName.');
			}
			elseif($checkForDuplicates) {
				$sql_id = ($this->get('id') ? " AND f.id != '".$this->get('id')."' " : '');
				$sql = "
					SELECT
						f.*
					FROM
						`tbl_fields` AS f
					WHERE
						f.element_name = '{$element_name}'
						{$sql_id}
						AND f.parent_section = '{$parent_section}'
					LIMIT 1
				";

				if (Symphony::Database()->fetchRow(0, $sql)) {
					$errors['element_name'] = __('A field with that element name already exists. Please choose another.');
				}
			}

			return (!empty($errors) ? self::__ERROR__ : self::__OK__);
		}

		/**
		 * Format this field value for display in the publish index tables. By default,
		 * Symphony will truncate the value to the configuration setting `cell_truncation_length`.
		 * This function will attempt to use PHP's `mbstring` functions if they are available.
		 *
		 * @param array $data
		 *	an associative array of data for this string. At minimum this requires a
		 *  key of 'value'.
		 * @param XMLElement $link (optional)
		 *	an xml link structure to append the content of this to provided it is not
		 *	null. it defaults to null.
		 * @return string
		 *	the formatted string summary of the values of this field instance.
		 */
		public function prepareTableValue($data, XMLElement $link = null) {
			$max_length = Symphony::Configuration()->get('cell_truncation_length', 'symphony');
			$max_length = ($max_length ? $max_length : 75);

			$value = strip_tags($data['value']);

			if(function_exists('mb_substr') && function_exists('mb_strlen')) {
				$value = (mb_strlen($value, 'utf-8') <= $max_length ? $value : mb_substr($value, 0, $max_length, 'utf-8') . '...');
			}
			else {
				$value = (strlen($value) <= $max_length ? $value : substr($value, 0, $max_length) . '...');
			}

			if (strlen($value) == 0) $value = __('None');

			if ($link) {
				$link->setValue($value);

				return $link->generate();
			}

			return $value;
		}

		/**
		 * Display the publish panel for this field. The display panel is the
		 * interface shown to Authors that allow them to input data into this 
		 * field for an Entry.
		 *
		 * @param XMLElement $wrapper
		 *	the xml element to append the html defined user interface to this
		 *	field.
		 * @param array $data (optional)
		 *	any existing data that has been supplied for this field instance.
		 *	this is encoded as an array of columns, each column maps to an
		 *	array of row indexes to the contents of that column. this defaults
		 *	to null.
		 * @param mixed $flagWithError (optional)
		 *	flag with error defaults to null.
		 * @param string $fieldnamePrefix (optional)
		 *	the string to be prepended to the display of the name of this field.
		 *	this defaults to null.
		 * @param string $fieldnameSuffix (optional)
		 *	the string to be appended to the display of the name of this field.
		 *	this defaults to null.
		 * @param integer $entry_id (optional)
		 *	the entry id of this field. this defaults to null.
		 */
		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){}

		/**
		 * Check the field data that has been posted from a form. This will set the
		 * input message to the error message or to null if there is none. Any existing
		 * message value will be overwritten.
		 *
		 * @param array $data
		 *	the input data to check.
		 * @param string $message
		 *	the place to set any generated error message. any previous value for
		 *	this variable will be overwritten.
		 * @param integer $entry_id (optional)
		 *	the optional id of this field entry instance. this defaults to null.
		 * @return integer
		 *	`self::__MISSING_FIELDS__` if there are any missing required fields,
		 *	`self::__OK__` otherwise.
		 */
		public function checkPostFieldData($data, &$message, $entry_id = null){
			$message = NULL;

			if ($this->get('required') == 'yes' && empty($data)){
				$message = __("'%s' is a required field.", array($this->get('label')));

				return self::__MISSING_FIELDS__;
			}

			return self::__OK__;
		}

		/**
		 * Process the raw field data.
		 *
		 * @param mixed $data
		 *	post data from the entry form
		 * @param integer $status
		 *	the status code resultant from processing the data.
		 * @param boolean $simulate (optional)
		 *	true if this will tell the CF's to simulate data creation, false
		 *	otherwise. this defaults to false. this is important if clients
		 *	will be deleting or adding data outside of the main entry object
		 *	commit function.
		 * @param mixed $entry_id (optional)
		 *	the current entry. defaults to null.
		 * @return array
		 *	the processed field data.
		 */
		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=null) {

			$status = self::__OK__;

			return array(
				'value' => $data,
			);
		}

		/**
		 * Display the default data-source filter panel.
		 *
		 * @param XMLElement $wrapper
		 *	the input XMLElement to which the display of this will be appended.
		 * @param mixed $data (optional)
		 *	the input data. this defaults to null.
		 * @param mixed errors (optional)
		 *	the input error collection. this defaults to null.
		 * @param string $fieldNamePrefix
		 *	the prefix to apply to the display of this.
		 * @param string $fieldNameSuffix
		 *	the suffix to apply to the display of this.
		 */
		public function displayDatasourceFilterPanel(XMLElement &$wrapper, $data = null, $errors = null, $fieldnamePrefix = null, $fieldnamePostfix = null){
			$wrapper->appendChild(new XMLElement('h4', $this->get('label') . ' <i>'.$this->Name().'</i>'));
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[filter]'.($fieldnamePrefix ? '['.$fieldnamePrefix.']' : '').'['.$this->get('id').']'.($fieldnamePostfix ? '['.$fieldnamePostfix.']' : ''), ($data ? General::sanitize($data) : null)));
			$wrapper->appendChild($label);
		}

		/**
		 * Default accessor for the includable elements of this field. This array
		 * will populate the Datasource included elements. Fields that have
		 * different modes will override this and add new items to the array.
		 * The Symphony convention is element_name : mode. Modes allow Fields to
		 * output different XML in datasources.
		 *
		 * @return array
		 *	the array of includable elements from this field.
		 */
		public function fetchIncludableElements(){
			return array($this->get('element_name'));
		}

		/**
		 * Test whether the input string is a regular expression.
		 *
		 * @param string $string
		 *	the string to test.
		 * @return boolean
		 *	true if the string is prefixed with 'regexp:', false otherwise.
		 */
		protected static function isFilterRegex($string){
			if(preg_match('/^regexp:/i', $string) || preg_match('/^not-?regexp:/i', $string)) return true;
		}

		/**
		 * Construct the SQL statement fragments to use to retrieve the data of this
		 * field when utilized as a data source.
		 *
		 * @param array $data
		 *	the supplied form data to use to construct the query from
		 * @param string $joins
		 *	the join sql statement fragment to append the additional join sql to.
		 * @param string $where
		 *	the where condition sql statement fragment to which the additional
		 *	where conditions will be appended.
		 * @param boolean $andOperation (optional)
		 *	true if the values of the input data should be appended as part of
		 *	the where condition. this defaults to false.
		 * @return boolean
		 *	true if the construction of the sql was successful, false otherwise.
		 */
		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');

			if (self::isFilterRegex($data[0])) {
				$this->_key++;

				if (preg_match('/^regexp:/i', $data[0])) {
					$pattern = preg_replace('/regexp:/i', null, $this->cleanValue($data[0]));
					$regex = 'REGEXP';
				} else {
					$pattern = preg_replace('/not-?regexp:/i', null, $this->cleanValue($data[0]));
					$regex = 'NOT REGEXP';
				}

				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND t{$field_id}_{$this->_key}.value {$regex} '{$pattern}'
				";

			}
			else if ($andOperation) {
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

			}
			else {
				if (!is_array($data)) $data = array($data);

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
		 *	the join element of the query to append the custom join sql to.
		 * @param string $where
		 *	the where condition of the query to append to the existing where clause.
		 * @param string $sort
		 *	the existing sort component of the sql query to append the custom
		 *	sort sql code to.
		 * @param string $order (optional)
		 *	an optional sorting direction. this defaults to ascending. if this
		 *	is declared either 'random' or 'rand' then a random sort is applied.
		 */
		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
			$joins .= "LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
			$sort = 'ORDER BY ' . (in_array(strtolower($order), array('random', 'rand')) ? 'RAND()' : "`ed`.`value` $order");
		}

		/**
		 * Default implementation of record grouping. This default implementation
		 * will throw an Exception. Thus, clients must overload this method
		 * for grouping to be successful.
		 *
		 * @param array $records
		 *	the records to group.
		 */
		public function groupRecords($records){
			throw new Exception(
				__('Data source output grouping is not supported by the <code>%s</code> field', array($this->get('label')))
			);
		}

		/**
		 * Function to format this field if it chosen in a data-source to be
		 * output as a parameter in the XML
		 *
		 * @param array $data
		 *	 The data for this field from it's `tbl_entry_data_{id}` table
		 * @return string
		 *	 The formatted value to be used as the parameter
		 */
		public function getParameterPoolValue(Array $data){
			return $this->prepareTableValue($data);
		}

		/**
		 * Append the formatted xml output of this field as utilized as a data source.
		 *
		 * @param XMLElement $wrapper
		 *	the xml element to append the xml representation of this to.
		 * @param array $data
		 *	the current set of values for this field. the values are structured as
		 *	for displayPublishPanel.
		 * @param boolean $encode (optional)
		 *	flag as to whether this should be html encoded prior to output. this
		 *	defaults to false.
		 * @param string $mode
		 *	 A field can provide ways to output this field's data. For instance a mode
		 *  could be 'items' or 'full' and then the function would display the data
		 *  in a different way depending on what was selected in the datasource
		 *  included elements.
		 * @param integer $entry_id (optional)
		 *	the identifier of this field entry instance. defaults to null.
		 */
		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
			$wrapper->appendChild(new XMLElement($this->get('element_name'), ($encode ? General::sanitize($this->prepareTableValue($data)) : $this->prepareTableValue($data))));
		}

		/**
		 * The default method for constructing the example form markup containing this
		 * field when utilized as part of an event. This displays in the event documentation
		 * and serves as a basic guide for how markup should be constructed on the
		 * Frontend to save this field
		 *
		 * @return XMLElement
		 *	a label widget containing the formatted field element name of this.
		 */
		public function getExampleFormMarkup(){
			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Input('fields['.$this->get('element_name').']'));

			return $label;
		}

		/**
		 * Commit the settings of this field from the section editor to
		 * create an instance of this field in a section.
		 *
		 * @return boolean
		 *	true if the commit was successful, false otherwise.
		 */
		public function commit(){
			$fields = array();

			$fields['element_name'] = Lang::createHandle($this->get('label'));
			if(is_numeric($fields['element_name']{0})) $fields['element_name'] = 'field-' . $fields['element_name'];

			$fields['label'] = $this->get('label');
			$fields['parent_section'] = $this->get('parent_section');
			$fields['location'] = $this->get('location');
			$fields['required'] = $this->get('required');
			$fields['type'] = $this->_handle;
			$fields['show_column'] = $this->get('show_column');
			$fields['sortorder'] = (string)$this->get('sortorder');

			if($id = $this->get('id')){
				return FieldManager::edit($id, $fields);
			}
			else if($id = FieldManager::add($fields)){
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
		 * @return boolean
		 */
		public function createTable(){
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `value` varchar(255) default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `value` (`value`)
				) ENGINE=MyISAM;"
			);
		}

		/**
		 * Remove the entry data of this field from the database.
		 *
		 * @param integer $entry_id
		 *	the id of the entry to delete.
		 * @param array $data (optional)
		 *	The entry data provided for fields to do additional cleanup
		 *  This is an optional argument and defaults to null.
		 * @return boolean
		 *	true if the cleanup was successful, false otherwise.
		 */
		public function entryDataCleanup($entry_id, $data=NULL){
			Symphony::Database()->delete('tbl_entries_data_' . $this->get('id'), " `entry_id` = '$entry_id' ");

			return true;
		}

		/**
		 * Create an association between a section and a field.
		 *
		 * @param integer $parent_section_id
		 *  The linked section id.
		 * @param integer $child_field_id
		 *  The field ID of the field that is creating the association
		 * @param integer $parent_field_id (optional)
		 *  The field ID of the linked field in the linked section
		 * @param boolean $show_association (optional)
		 *	Whether of not the link should be shown on the entries tableof the
		 * linked section. This defaults to true.
		 * @return boolean
		 *	true if the association was successfully made, false otherwise.
		 */
		public function createSectionAssociation($parent_section_id = null, $child_field_id = null, $parent_field_id = null, $show_association = true){

			if(is_null($parent_section_id) && (is_null($parent_field_id) || !$parent_field_id)) return false;

			if(is_null($parent_section_id )) {
				$parent_section_id = Symphony::Database()->fetchVar('parent_section', 0,
					"SELECT `parent_section` FROM `tbl_fields` WHERE `id` = '$parent_field_id' LIMIT 1"
				);
			}

			$child_section_id = Symphony::Database()->fetchVar('parent_section', 0,
				"SELECT `parent_section` FROM `tbl_fields` WHERE `id` = '$child_field_id' LIMIT 1
			");

			$fields = array(
				'parent_section_id' => $parent_section_id,
				'parent_section_field_id' => $parent_field_id,
				'child_section_id' => $child_section_id,
				'child_section_field_id' => $child_field_id,
				'hide_association' => ($show_association ? 'no' : 'yes')
			);

			return Symphony::Database()->insert($fields, 'tbl_sections_association');
		}

		/**
		 * Permanently remove a section association for this field in the database.
		 *
		 * @param integer $child_field_id
		 *	the field ID of the linked section's linked field.
		 */
		public function removeSectionAssociation($child_field_id){
			Symphony::Database()->delete('tbl_sections_association', " `child_section_field_id` = '$child_field_id' ");
		}

		/**
		 * Accessor to the associated entry search value for this field
		 * instance. This default implementation simply returns the input
		 * data argument.
		 *
		 * @param array $data
		 *	the data from which to construct the associated search entry value.
		 * @param integer $field_id (optional)
		 *	an optional id of the associated field? this defaults to null.
		 * @param integer $parent_entry_id (optional)
		 *	an optional parent identifier of the associated field entry? this defaults
		 *	to null.
		 * @return array
		 *	the associated entry search value. this implementation returns the input
		 *	data argument.
		 */
		public function fetchAssociatedEntrySearchValue($data, $field_id = null, $parent_entry_id = null){
			return $data;
		}

		/**
		 * Fetch the count of the associate entries for the input value. This default
		 * implementation does nothing.
		 *
		 * @param mixed $value
		 *	the value to find the associated entry count for.
		 * @return void|integer
		 *	this default implementation returns void. overriding implementations should
		 *	return a number.
		 */
		public function fetchAssociatedEntryCount($value){}

		/**
		 * Accessor to the ids associated with this field instance.
		 *
		 * @param mixed $value
		 *	the value to find the associated entry ids for.
		 * @return void|array
		 *	this default implementation returns void. overriding implementations should
		 *	return an array of the associated entry ids.
		 */
		public function fetchAssociatedEntryIDs($value){}

		/**
		 * @deprecated This function name has a typo that has withstood many versions of
		 *  Symphony. The correct function is `$this->buildDSRetrievalSQL`.
		 */
		public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
			return $this->buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false);
		}
	}
