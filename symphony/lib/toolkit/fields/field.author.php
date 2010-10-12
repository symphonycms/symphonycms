<?php

	Class fieldAuthor extends Field {
		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = __('Author');
		}

		public function canToggle(){
			return ($this->get('allow_multiple_selection') == 'yes' ? false : true);
		}

		public function allowDatasourceOutputGrouping(){
			## Grouping follows the same rule as toggling.
			return $this->canToggle();
		}

		public function getToggleStates(){

		    $authors = AuthorManager::fetch();

			$states = array();
			foreach($authors as $a) $states[$a->get('id')] = $a->get('first_name') . ' ' . $a->get('lastname');

			return $states;
		}

		public function toggleFieldData($data, $newState){
			$data['author_id'] = $newState;
			return $data;
		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){

			$status = self::__OK__;

			if(!is_array($data) && !is_null($data)) return array('author_id' => $data);

			if(empty($data)) return NULL;

			$result = array();
			foreach($data as $id) $result['author_id'][] = $id;

			return $result;
		}

		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){

			$value = (isset($data['author_id']) ? $data['author_id'] : NULL);

			$callback = Administration::instance()->getPageCallback();

			if ($this->get('default_to_current_user') == 'yes' && empty($data) && empty($_POST)) {
				$value = array(Administration::instance()->Author->get('id'));
			}

			if (!is_array($value)) {
				$value = array($value);
			}

		    $authors = AuthorManager::fetch();

			$options = array();

			foreach($authors as $a){
				$options[] = array($a->get('id'), in_array($a->get('id'), $value), $a->get('first_name') . ' ' . $a->get('last_name'));
			}

			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$attr = array();

			if($this->get('allow_multiple_selection') == 'yes') $attr['multiple'] = 'multiple';

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options, $attr));
			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);
		}

		public function prepareTableValue($data, XMLElement $link=NULL){

			if(!is_array($data['author_id'])) $data['author_id'] = array($data['author_id']);

			if(empty($data['author_id'])) return NULL;

			$value = array();

			foreach($data['author_id'] as $author_id){
				$author = new Author;

				if($author->loadAuthor($author_id)){
					$value[] = $author->getFullName();
				}
			}

			return parent::prepareTableValue(array('value' => General::sanitize(ucwords(implode(', ', $value)))), $link);
		}

		public function isSortable(){
			return ($this->get('allow_multiple_selection') == 'yes' ? false : true);
		}

		public function canFilter(){
			return true;
		}

		public function canImport(){
			return true;
		}

		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
			$joins .= "
				LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`)
				JOIN `tbl_authors` AS `a` ON (ed.author_id = a.id)
			";
			$sort = 'ORDER BY ' . (in_array(strtolower($order), array('random', 'rand'))
					? 'RAND()'
					: "`a`.`first_name` " . $order . ", `a`.`last_name` " . $order);
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
					AND t{$field_id}_{$this->_key}.author_id REGEXP '{$pattern}'
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
						AND t{$field_id}_{$this->_key}.author_id = '{$value}'
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
					AND t{$field_id}_{$this->_key}.author_id IN ('{$data}')
				";
			}

			return true;
		}

		public function commit(){

			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			$fields['field_id'] = $id;
			$fields['allow_multiple_selection'] = ($this->get('allow_multiple_selection') ? $this->get('allow_multiple_selection') : 'no');
			$fields['default_to_current_user'] = ($this->get('default_to_current_user') ? $this->get('default_to_current_user') : 'no');

			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());

		}

		public function appendFormattedElement(&$wrapper, $data, $encode=false){
	        if(!is_array($data['author_id'])) $data['author_id'] = array($data['author_id']);

	        $list = new XMLElement($this->get('element_name'));
	        foreach($data['author_id'] as $author_id){
	            $author = new Author($author_id);
	            $list->appendChild(new XMLElement(
					'item',
					$author->getFullName(),
					array(
						'id' => (string)$author->get('id'),
						'username' => General::sanitize($author->get('username'))
					)
				));
	        }
	        $wrapper->appendChild($list);
	    }

		public function findDefaults(&$fields){
			if(!isset($fields['allow_multiple_selection'])) $fields['allow_multiple_selection'] = 'no';
		}

		public function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'related');

			## Allow multiple selection
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][allow_multiple_selection]', 'yes', 'checkbox');
			if($this->get('allow_multiple_selection') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Allow selection of multiple authors', array($input->generate())));
			$div->appendChild($label);

			## Default to current logged in user
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][default_to_current_user]', 'yes', 'checkbox');
			if($this->get('default_to_current_user') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Select current user by default', array($input->generate())));
			$div->appendChild($label);

			$wrapper->appendChild($div);

			$this->appendShowColumnCheckbox($wrapper);

		}

		public function createTable(){
			return Symphony::Database()->query(

				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') ."` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `author_id` int(11) unsigned NOT NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `author_id` (`author_id`)
				) ENGINE=MyISAM;"

			);
		}

		public function getExampleFormMarkup(){

		    $authors = AuthorManager::fetch();

			$options = array();

			foreach($authors as $a){
				$options[] = array($a->get('id'), NULL, $a->get('first_name') . ' ' . $a->get('lastname'));
			}

			$fieldname = 'fields['.$this->get('element_name').']';
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$attr = array();

			if($this->get('allow_multiple_selection') == 'yes') $attr['multiple'] = 'multiple';

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options, $attr));

			return $label;
		}


	}

