<?php
	
	Class Field{
		protected $_key = 0;
		protected $_fields;
		protected $_Parent;
		protected $_engine;
		protected $_required;
		protected $_showcolumn;
		protected $Database;

		const __OK__ = 100;
		const __ERROR__ = 150;
		const __MISSING_FIELDS__ = 200;
		const __INVALID_FIELDS__ = 220;
		const __DUPLICATE__ = 300;
		const __ERROR_CUSTOM__ = 400;
		const __INVALID_QNAME__ = 500;

		const __TOGGLEABLE_ONLY__ = 600;
		const __UNTOGGLEABLE_ONLY__ = 700;
		const __FILTERABLE_ONLY__ = 800;
		const __UNFILTERABLE_ONLY__ = 900;	
		const __FIELD_ALL__ = 1000;
				
		function __construct(&$parent){
			$this->_Parent = $parent;
			
			$this->_fields = array();
			$this->_required = false;
			$this->_showcolumn = true;
			
			$this->_handle = (strtolower(get_class($this)) == 'field' ? 'field' : strtolower(substr(get_class($this), 5)));

			if(class_exists('Administration')) $this->_engine = Administration::instance();
			elseif(class_exists('Frontend')) $this->_engine = Frontend::instance();
			else trigger_error(__('No suitable engine object found'), E_USER_ERROR);
			
			$this->creationDate = DateTimeObj::getGMT('c');
			
			$this->Database = Symphony::Database();

		}
		
		public function canShowTableColumn(){
			return $this->_showcolumn;
		}
		
		public function canToggle(){
			return false;
		}
		
		public function canFilter(){
			return false;
		}
		
		public function canImport(){
			return false;
		}
		
		public function allowDatasourceOutputGrouping(){
			return false;
		}
			
		public function allowDatasourceParamOutput(){
			return false;
		}	
			
		public function mustBeUnique(){
			return false;
		}
			
		// Abstract function
		public function getToggleStates(){
			return array();
		}
		
		public function toggleFieldData($data, $newState){
			return $data;
		}
		
		public function handle(){
			return $this->_handle;
		}
		
		public function name(){
			return ($this->_name ? $this->_name : $this->_handle);
		}		
		
		public function set($field, $value){
			$this->_fields[$field] = $value;
		}
		
		public function entryDataCleanup($entry_id, $data=NULL){
			$this->Database->delete('tbl_entries_data_' . $this->get('id'), " `entry_id` = '$entry_id' ");
			
			return true;
		}
		
		public function setFromPOST($data) {
			$data['location'] = (isset($data['location']) ? $data['location'] : 'main');
			$data['required'] = (isset($data['required']) && @$data['required'] == 'yes' ? 'yes' : 'no');
			$data['show_column'] = (isset($data['show_column']) && @$data['show_column'] == 'yes' ? 'yes' : 'no');
			$this->setArray($data);
		}
		
		public function setArray($array){
			if(empty($array) || !is_array($array)) return;
			foreach($array as $field => $value) $this->set($field, $value);
		}

		public function get($field=NULL){
			if(!$field) return $this->_fields;
			
			if (!isset($this->_fields[$field])) return null;
			
			return $this->_fields[$field];
		}
		
		public function remove($field){
			unset($this->_fields[$field]);
		}
		
		public function removeSectionAssociation($child_field_id){
			$this->Database->query("DELETE FROM `tbl_sections_association` WHERE `child_section_field_id` = '$child_field_id'");
		}
		
		public function createSectionAssociation($parent_section_id, $child_field_id, $parent_field_id=NULL, $cascading_deletion=false){

			if($parent_section_id == NULL && !$parent_field_id) return false;
			
			if($parent_section_id == NULL) $parent_section_id = $this->Database->fetchVar('parent_section', 0, "SELECT `parent_section` FROM `tbl_fields` WHERE `id` = '$parent_field_id' LIMIT 1");
			
			$child_section_id = $this->Database->fetchVar('parent_section', 0, "SELECT `parent_section` FROM `tbl_fields` WHERE `id` = '$child_field_id' LIMIT 1");
			
			$fields = array('parent_section_id' => $parent_section_id, 
							'parent_section_field_id' => $parent_field_id, 
							'child_section_id' => $child_section_id, 
							'child_section_field_id' => $child_field_id,
							'cascading_deletion' => ($cascading_deletion ? 'yes' : 'no'));

			if(!$this->Database->insert($fields, 'tbl_sections_association')) return false;
				
			return true;		
		}
		
		public function flush(){
			$this->_fields = array();
		}
		
		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL, $entry_id = null){
		}
		
		public function canPrePopulate(){
			return false;
		}
		
		public function appendFormattedElement(&$wrapper, $data, $encode=false, $mode=NULL, $entry_id=NULL) {
			$wrapper->appendChild(new XMLElement($this->get('element_name'), ($encode ? General::sanitize($this->prepareTableValue($data)) : $this->prepareTableValue($data))));
		}
		
		public function getParameterPoolValue($data){
			return $this->prepareTableValue($data);
		}
		
		public function cleanValue($value) {
			return html_entity_decode($this->Database->cleanValue($value));
		}
		
		public function checkFields(&$errors, $checkForDuplicates = true) {
			$parent_section = $this->get('parent_section');
			$element_name = $this->get('element_name');
			
			//echo $this->get('id'), ': ', $this->get('required'), '<br />';
			
			if (!is_array($errors)) $errors = array();
			
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
				
				if ($this->Database->fetchRow(0, $sql)) {
					$errors['element_name'] = __('A field with that element name already exists. Please choose another.');
				}
			}
			
			return (is_array($errors) && !empty($errors) ? self::__ERROR__ : self::__OK__);
		}
		
		public function findDefaults(&$fields){
		}
		
		public function isSortable(){
			return false;
		}
		
		public function requiresSQLGrouping(){
			return false;
		}
		
		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
			$joins .= "LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
			$sort = 'ORDER BY ' . (in_array(strtolower($order), array('random', 'rand')) ? 'RAND()' : "`ed`.`value` $order");
		}
		
		protected static function isFilterRegex($string){
			if(preg_match('/^regexp:/i', $string)) return true;				
		}
		
		public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');
			
			if (self::isFilterRegex($data[0])) {
				$this->_key++;
				$pattern = str_replace('regexp:', '', $this->cleanValue($data[0]));
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND t{$field_id}_{$this->_key}.value REGEXP '{$pattern}'
				";
				
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
				
			} else {
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

		public function checkPostFieldData($data, &$message, $entry_id=NULL){
			$message = NULL;
			
			if ($this->get('required') == 'yes' && empty($data)){
				$message = __("'%s' is a required field.", array($this->get('label')));
				
				return self::__MISSING_FIELDS__;
			}
			
			return self::__OK__;
		}
		
		/*
		
			$data - post data from the entry form
			$status - refence variable. Will hold the status code
			$simulate (optional) - this will tell CF's to simulate data creation. This is important if they
								   will be deleting or adding data outside of the main entry object commit function
			$entry_id (optionsl) - Useful for identifying the current entry
		
		*/
		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=null) {	
			
			$status = self::__OK__;
			
			return array(
				'value' => $data,
			);
		}
		
		public function prepareTableValue($data, XMLElement $link=NULL) {
			$max_length = Symphony::Configuration()->get('cell_truncation_length', 'symphony');
			$max_length = ($max_length ? $max_length : 75);
			
			$value = strip_tags($data['value']);
			$value = (strlen($value) <= $max_length ? $value : substr($value, 0, $max_length) . '...');
			
			if (strlen($value) == 0) $value = __('None');
			
			if ($link) {
				$link->setValue($value);
				
				return $link->generate();
			}
			
			return $value;
		}
		
		public function getExampleFormMarkup(){
			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Input('fields['.$this->get('element_name').']'));
			
			return $label;
		}
		
		public function fetchIncludableElements(){
			return array($this->get('element_name'));
		}
		
		public function fetchAssociatedEntrySearchValue($data, $field_id=NULL, $parent_entry_id=NULL){
			return $data;
		}
				
		public function fetchAssociatedEntryCount($value){
		}
		
		function fetchAssociatedEntryIDs($value){
		}
				
		public function displayDatasourceFilterPanel(&$wrapper, $data=NULL, $errors=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			$wrapper->appendChild(new XMLElement('h4', $this->get('label') . ' <i>'.$this->Name().'</i>'));
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[filter]'.($fieldnamePrefix ? '['.$fieldnamePrefix.']' : '').'['.$this->get('id').']'.($fieldnamePostfix ? '['.$fieldnamePostfix.']' : ''), ($data ? General::sanitize($data) : NULL)));		
			$wrapper->appendChild($label);	
		}
		
		public function displayImportPanel(&$wrapper, $data = null, $errors = null, $fieldnamePrefix = null, $fieldnamePostfix = null) {
			$this->displayDatasourceFilterPanel($wrapper, $data, $errors, $fieldnamePrefix, $fieldnamePostfix);	
		}
		
		public function displaySettingsPanel(&$wrapper, $errors=NULL){		
			$wrapper->appendChild(new XMLElement('h4', ucwords($this->name())));
			$wrapper->appendChild(Widget::Input('fields['.$this->get('sortorder').'][type]', $this->handle(), 'hidden'));
			if($this->get('id')) $wrapper->appendChild(Widget::Input('fields['.$this->get('sortorder').'][id]', $this->get('id'), 'hidden'));
			
			$wrapper->appendChild($this->buildSummaryBlock($errors));			

		}
		
		public function buildSummaryBlock($errors=NULL){

			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Label'));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][label]', $this->get('label')));
			if(isset($errors['label'])) $div->appendChild(Widget::wrapFormElementWithError($label, $errors['label']));
			else $div->appendChild($label);		
			
			$div->appendChild($this->buildLocationSelect($this->get('location'), 'fields['.$this->get('sortorder').'][location]'));

			return $div;
			
		}
		
		public function appendRequiredCheckbox(&$wrapper) {
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
		
		
		public function appendShowColumnCheckbox(&$wrapper) {
			if (!$this->_showcolumn) return;
			
			$order = $this->get('sortorder');
			$name = "fields[{$order}][show_column]";
			
			$wrapper->appendChild(Widget::Input($name, 'no', 'hidden'));
			
			$label = Widget::Label();
			$label->setAttribute('class', 'meta');
			$input = Widget::Input($name, 'yes', 'checkbox');
			
			if ($this->get('show_column') == 'yes') $input->setAttribute('checked', 'checked');
			
			$label->setValue(__('%s Show column', array($input->generate())));
			
			$wrapper->appendChild($label);
		}

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

		public function buildFormatterSelect($selected=NULL, $name='fields[format]', $label_value){
			
			include_once(TOOLKIT . '/class.textformattermanager.php');
			
			$TFM = new TextformatterManager($this->_engine);
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

		public function buildValidationSelect(&$wrapper, $selected=NULL, $name='fields[validator]', $type='input'){

			include(TOOLKIT . '/util.validators.php');
			$rules = ($type == 'upload' ? $upload : $validators);

			$label = Widget::Label(__('Validation Rule <i>Optional</i>'));
			$label->appendChild(Widget::Input($name, $selected));
			$wrapper->appendChild($label);
			
			$ul = new XMLElement('ul', NULL, array('class' => 'tags singular'));
			foreach($rules as $name => $rule) $ul->appendChild(new XMLElement('li', $name, array('class' => $rule)));
			$wrapper->appendChild($ul);
			
		}
		
		public function groupRecords($records){			
			trigger_error(__('Data source output grouping is not supported by the <code>%s</code> field', array($this->get('label'))), E_USER_ERROR);		
		}
		
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
				return $this->_Parent->edit($id, $fields);				
			}
			
			elseif($id = $this->_Parent->add($fields)){
				$this->set('id', $id);
				$this->createTable();
				return true;
			}
			
			return false;
			
		}
		
		public function createTable(){
			
			return $this->Database->query(
			
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

	}

