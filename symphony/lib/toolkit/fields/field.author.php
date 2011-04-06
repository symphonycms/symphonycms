<?php

	/**
	 * @package toolkit
	 */

	/**
	 * The Author field allows Symphony Authors to be selected in your entries.
	 * It is a read only field, new Authors cannot be added from the Frontend using
	 * events.
	 *
	 * The Author field allows filtering by Author ID or Username.
	 * Sorting is done based on the Author's first name and last name.
	 */
	Class fieldAuthor extends Field {
		public function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = __('Author');
			$this->_required = true;
		}

		public function canToggle(){
			return ($this->get('allow_multiple_selection') == 'yes' ? false : true);
		}

		public function isSortable(){
			return $this->canToggle();
		}

		public function canFilter(){
			return true;
		}

		public function canImport(){
			return true;
		}

		public function allowDatasourceOutputGrouping(){
			## Grouping follows the same rule as toggling.
			return $this->canToggle();
		}

		public function getToggleStates(){

			$authors = AuthorManager::fetch();

			$states = array();
			foreach($authors as $a) $states[$a->get('id')] = $a->getFullName();

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

			$value = isset($data['author_id']) ? $data['author_id'] : NULL;

			if ($this->get('default_to_current_user') == 'yes' && empty($data) && empty($_POST)) {
				$value = array(Administration::instance()->Author->get('id'));
			}

			if(!is_array($value)) $value = array($value);

			$options = array();

			if ($this->get('required') != 'yes') $options[] = array(NULL, false, NULL);

			$authors = AuthorManager::fetch();
			foreach($authors as $a){
				$options[] = array($a->get('id'), in_array($a->get('id'), $value), $a->getFullName());
			}

			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple') : NULL)));

			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);
		}

		public function prepareTableValue($data, XMLElement $link=NULL){

			if(!is_array($data['author_id'])) $data['author_id'] = array($data['author_id']);

			if(empty($data['author_id'])) return NULL;

			$value = array();

			foreach($data['author_id'] as $author_id){
				$author = AuthorManager::fetchByID($author_id);

				if(!is_null($author)) {
					$value[] = $author->getFullName();
				}
			}

			return parent::prepareTableValue(array('value' => General::sanitize(implode(', ', $value))), $link);
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
					JOIN
						`tbl_authors` AS t{$field_id}_{$this->_key}_authors
						ON (t{$field_id}_{$this->_key}.author_id = t{$field_id}_{$this->_key}_authors.id)
				";
				$where .= "
					AND (
						 t{$field_id}_{$this->_key}.author_id {$regex} '{$pattern}'
						 OR
						 t{$field_id}_{$this->_key}_authors.username {$regex} '{$pattern}'
						)
				";

			} elseif ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->cleanValue($value);

					if(fieldAuthor::__parseFilter($value) == "author_id") {
						$where .= "
							AND t{$field_id}_{$this->_key}.author_id = '{$value}'
						";
						$joins .= "
							LEFT JOIN
								`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
								ON (e.id = t{$field_id}_{$this->_key}.entry_id)
						";
					}
					else {
						$joins .= "
							LEFT JOIN
								`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
								ON (e.id = t{$field_id}_{$this->_key}.entry_id)
							JOIN
								`tbl_authors` AS t{$field_id}_{$this->_key}_authors
								ON (t{$field_id}_{$this->_key}.author_id = t{$field_id}_{$this->_key}_authors.id)
						";
						$where .= "
							AND t{$field_id}_{$this->_key}_authors.username = '{$value}'
						";
					}
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
					JOIN
						`tbl_authors` AS t{$field_id}_{$this->_key}_authors
						ON (t{$field_id}_{$this->_key}.author_id = t{$field_id}_{$this->_key}_authors.id)
				";
				$where .= "
					AND (
						t{$field_id}_{$this->_key}.author_id IN ('{$data}')
						OR
						t{$field_id}_{$this->_key}_authors.username IN ('{$data}')
						)
				";
			}

			return true;
		}

		/**
		 * Determines based on the input value whether we want to filter the Author
		 * field by ID or by the Author's Username
		 *
		 * @since Symphony 2.2
		 * @param string $value
		 * @return string
		 *  Either `author_id` or `username`
		 */
		private static function __parseFilter($value) {
			return is_numeric($value) ? 'author_id' : 'username';
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
				$author = AuthorManager::fetchByID($author_id);

				if(is_null($author)) continue;

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

			$div = new XMLElement('div', NULL, array('class' => 'compact'));

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

			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);
			$wrapper->appendChild($div);
		}

		public function createTable(){
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') ."` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `author_id` int(11) unsigned NULL,
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `entry_id` (`entry_id`),
				  KEY `author_id` (`author_id`)
				) ENGINE=MyISAM;"
			);
		}

		public function getExampleFormMarkup(){

			$authors = AuthorManager::fetch();

			$options = array();

			foreach($authors as $a){
				$options[] = array($a->get('id'), NULL, $a->getFullName());
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
