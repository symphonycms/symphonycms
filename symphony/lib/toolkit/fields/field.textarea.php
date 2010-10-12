<?php

	Class fieldTextarea extends Field {
		function __construct(&$parent){
			
			parent::__construct($parent);
			$this->_name = __('Textarea');		
			$this->_required = true;
			
			// Set default
			$this->set('show_column', 'no');
			$this->set('required', 'yes');
		}
		
		function canFilter(){
			return true;
		}
		
		public function canImport(){
			return true;
		}
		
		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			$label = Widget::Label($this->get('label'));
			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));
			
			$textarea = Widget::Textarea('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, $this->get('size'), '50', (strlen($data['value']) != 0 ? General::sanitize($data['value']) : NULL));
			
			if($this->get('formatter') != 'none') $textarea->setAttribute('class', $this->get('formatter'));
			
			###
			# Delegate: ModifyTextareaFieldPublishWidget
			# Description: Allows developers modify the textarea before it is rendered in the publish forms
			$this->_engine->ExtensionManager->notifyMembers('ModifyTextareaFieldPublishWidget', '/backend/', array('field' => &$this, 'label' => &$label, 'textarea' => &$textarea));
			
			$label->appendChild($textarea);
			
			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);
		}

		function commit(){
			
			if(!parent::commit()) return false;
			
			$id = $this->get('id');

			if($id === false) return false;
			
			$fields = array();
			
			$fields['field_id'] = $id;
			if($this->get('formatter') != 'none') $fields['formatter'] = $this->get('formatter');
			$fields['size'] = $this->get('size');
			
			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");		
			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
					
		}
		
		public function buildDSRetrivalSQL($data, &$joins, &$where) {
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
				
			} else {
				if (is_array($data)) $data = $data[0];
				
				$data = $this->cleanValue($data);
				$this->_key++;
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
		
		function checkPostFieldData($data, &$message, $entry_id=NULL){
			
			$message = NULL;
			
			if($this->get('required') == 'yes' && strlen($data) == 0){
				$message = __("'%s' is a required field.", array($this->get('label')));
				return self::__MISSING_FIELDS__;
			}	

			if($this->__applyFormatting($data, true, $errors) === false){
				$message = __('"%1$s" contains invalid XML. The following error was returned: <code>%2$s</code>', array($this->get('label'), $errors[0]['message']));
				return self::__INVALID_FIELDS__;
			}
			
			return self::__OK__;
							
		}
		
		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null) {
			$status = self::__OK__;
			
			$result = array(
				'value' => $data
			);
			
			$result['value_formatted'] = $this->__applyFormatting($data, true, $errors);
			if($result['value_formatted'] === false){
				//run the formatter again, but this time do not validate. We will sanitize the output
				$result['value_formatted'] = General::sanitize($this->__applyFormatting($data));	
			}
			
			return $result;
		}

		protected function __applyFormatting($data, $validate=false, &$errors=NULL){
	
			if($this->get('formatter')){

				$tfm = new TextformatterManager($this->_engine);
				
				$formatter = $tfm->create($this->get('formatter'));

				$result = $formatter->run($data);
				
			}	

			if($validate === true){

				include_once(TOOLKIT . '/class.xsltprocess.php');

				if(!General::validateXML($result, $errors, false, new XsltProcess)){
					$result = html_entity_decode($result, ENT_QUOTES, 'UTF-8');
					$result = $this->__replaceAmpersands($result);

					if(!General::validateXML($result, $errors, false, new XsltProcess)){

						$result = $formatter->run(General::sanitize($data));
					
						if(!General::validateXML($result, $errors, false, new XsltProcess)){
							return false;
						}
					}
				}
			}

			return $result;		
		}
		
		private function __replaceAmpersands($value) {
			return preg_replace('/&(?!(#[0-9]+|#x[0-9a-f]+|amp|lt|gt);)/i', '&amp;', trim($value));
		}
		
		public function appendFormattedElement(&$wrapper, $data, $encode = false, $mode = null) {
			
			if ($mode == null || $mode == 'formatted') {
				
				if ($this->get('formatter') && isset($data['value_formatted'])) {
					$value = $data['value_formatted'];
				}

				else {
					$value = $data['value'];
				}

				$value = $this->__replaceAmpersands($value);
				
				if ($mode == 'formatted') $attributes['mode'] = $mode;
				
				$wrapper->appendChild(
					new XMLElement(
						$this->get('element_name'),
						($encode ? General::sanitize($value) : $value),
						$attributes
					)
				);
				
			}
				
			elseif ($mode == 'unformatted') {

				$wrapper->appendChild(
					new XMLElement(
						$this->get('element_name'),
						sprintf('<![CDATA[%s]]>', $data['value']),
						array(
							'mode' => $mode
						)
					)
				);
				
			}

		}

		function checkFields(&$required, $checkForDuplicates=true, $checkForParentSection=true){
			$required = array();		
			if($this->get('size') == '' || !is_numeric($this->get('size'))) $required[] = 'size';
			return parent::checkFields($required, $checkForDuplicates, $checkForParentSection);
			
		}

		function findDefaults(&$fields){
			if(!isset($fields['size'])) $fields['size'] = 15;				
		}
		
		public function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			$group = new XMLElement('div', NULL, array('class' => 'group'));
			
			$div = new XMLElement('div');
			
			## Textarea Size
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][size]', $this->get('size'));
			$input->setAttribute('size', '3');
			$label->setValue(__('Make textarea %s rows tall', array($input->generate())));
			$div->appendChild($label);
			
			$this->appendRequiredCheckbox($div);
			$group->appendChild($div);
			
			$group->appendChild($this->buildFormatterSelect($this->get('formatter'), 'fields['.$this->get('sortorder').'][formatter]', __('Text Formatter')));
			
			$wrapper->appendChild($group);
			
			$this->appendShowColumnCheckbox($wrapper);						
		}
		
		function createTable(){
			
			return Symphony::Database()->query(
			
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `value` text,
				  `value_formatted` text,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  FULLTEXT KEY `value` (`value`)
				) ENGINE=MyISAM;"
			
			);
		}		

		public function getExampleFormMarkup(){
			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Textarea('fields['.$this->get('element_name').']', $this->get('size'), 50));
			
			return $label;
		}
		
		public function fetchIncludableElements() {
			
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

	}

