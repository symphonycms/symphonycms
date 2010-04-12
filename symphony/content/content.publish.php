<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.entrymanager.php');
	//require_once(TOOLKIT . '/class.sectionmanager.php');

	Class contentPublish extends AdministrationPage{

		private $_errors = array();

		public function __switchboard($type='view'){

			$function = "__{$type}" . ucfirst($this->_context['page']);

			// If there is no view function, throw an error
			if (!is_callable(array($this, $function))){

				if ($type == 'view'){
					throw new AdministrationPageNotFoundException;
				}

				return false;
			}
			$this->$function();
		}

		public function view(){
			$this->__switchboard();
		}

		public function action(){
			$this->__switchboard('action');
		}

		public function __viewIndex(){


			/*if(!$section_id = SectionManager::instance()->fetchIDFromHandle($this->_context['section_handle']))
				Administration::instance()->customError(E_USER_ERROR, __('Unknown Section'), __('The Section you are looking for, <code>%s</code>, could not be found.', array($this->_context['section_handle'])), false, true);

			$section = SectionManager::instance()->fetch($section_id);*/

			$section = Section::load(sprintf('%s/%s.xml', SECTIONS, $this->_context['section_handle']));

			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), $section->name)));
			$this->Form->setAttribute("class", $section->handle);

		    $users = UserManager::fetch();

			$filter = $filter_value = $where = $joins = NULL;
			$current_page = (isset($_REQUEST['pg']) && is_numeric($_REQUEST['pg']) ? max(1, intval($_REQUEST['pg'])) : 1);

			$this->appendSubheading(
				$section->name,
				Widget::Anchor(
					__('Create New'),
					sprintf('%snew/%s', Administration::instance()->getCurrentPageURL(), ($filter ? "?prepopulate[{$filter}]={$filter_value}" : NULL)),
					__('Create a new entry'),
					'create button'
				)
			);


			$aTableHead = array();


			foreach($section->fields as $column){
				if($column->get('show_column') != 'yes') continue;

				$label = $column->get('label');

				// TO DO: Fix the ordering links
				/*if($column->isSortable()) {

					if($column->get('id') == $section->get('entry_order')){
						$link = Administration::instance()->getCurrentPageURL() . '?pg='.$current_page.'&amp;sort='.$column->get('id').'&amp;order='. ($section->get('entry_order_direction') == 'desc' ? 'asc' : 'desc').($filter ? "&amp;filter=$field_handle:$filter_value" : '');
						$anchor = Widget::Anchor($label, $link, __('Sort by %1$s %2$s', array(($section->get('entry_order_direction') == 'desc' ? __('ascending') : __('descending')), strtolower($column->get('label')))), 'active');
					}

					else{
						$link = Administration::instance()->getCurrentPageURL() . '?pg='.$current_page.'&amp;sort='.$column->get('id').'&amp;order=asc'.($filter ? "&amp;filter=$field_handle:$filter_value" : '');
						$anchor = Widget::Anchor($label, $link, __('Sort by %1$s %2$s', array(__('ascending'), strtolower($column->get('label')))));
					}

					$aTableHead[] = array($anchor, 'col');
				}

				else */
				$aTableHead[] = array($label, 'col');
			}


			## Table Body
			$aTableBody = array();

			if(!is_array($entries['records']) || empty($entries['records'])){

				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}

			else{
			}

			$table = Widget::Table(
				Widget::TableHead($aTableHead),
				NULL,
				Widget::TableBody($aTableBody)
			);

			$this->Form->appendChild($table);


			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				array(NULL, false, __('With Selected...')),
				array('delete', false, __('Delete'))
			);

			// TO DO: Add toggable fields back
			/*
			$toggable_fields = $section->fetchToggleableFields();

			if (is_array($toggable_fields) && !empty($toggable_fields)) {
				$index = 2;

				foreach ($toggable_fields as $field) {
					$options[$index] = array('label' => __('Set %s', array($field->get('label'))), 'options' => array());

					foreach ($field->getToggleStates() as $value => $state) {
						$options[$index]['options'][] = array('toggle-' . $field->get('id') . '-' . $value, false, $state);
					}

					$index++;
				}
			}
			*/

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);

			// TO DO: Fix Filtering
			/*if(isset($_REQUEST['filter'])){

				list($field_handle, $filter_value) = explode(':', $_REQUEST['filter'], 2);

				$field_names = explode(',', $field_handle);

				foreach($field_names as $field_name) {

					$filter_value = rawurldecode($filter_value);

					$filter = Symphony::Database()->fetchVar('id', 0, "SELECT `f`.`id`
																			   FROM `tbl_fields` AS `f`, `tbl_sections` AS `s`
																			   WHERE `s`.`id` = `f`.`parent_section`
																			   AND f.`element_name` = '$field_name'
																			   AND `s`.`handle` = '".$section->handle."' LIMIT 1");
					$field = FieldManager::instance()->fetch($filter);

					if(is_object($field)){
						$field->buildDSRetrivalSQL(array($filter_value), $joins, $where, false);
						$filter_value = rawurlencode($filter_value);
					}

				}

				if ($where != null) {
					$where = str_replace('AND', 'OR', $where); // multiple fields need to be OR
					$where = trim($where);
					$where = ' AND (' . substr($where, 2, strlen($where)) . ')'; // replace leading OR with AND
				}

			}*/

			// TO DO: Fix Sorting
			/*if(isset($_REQUEST['sort']) && is_numeric($_REQUEST['sort'])){
				$sort = intval($_REQUEST['sort']);
				$order = ($_REQUEST['order'] ? strtolower($_REQUEST['order']) : 'asc');

				if($section->get('entry_order') != $sort || $section->get('entry_order_direction') != $order){
					SectionManager::instance()->edit($section->get('id'), array('entry_order' => $sort, 'entry_order_direction' => $order));
					redirect(Administration::instance()->getCurrentPageURL().($filter ? "?filter=$field_handle:$filter_value" : ''));
				}
			}

			elseif(isset($_REQUEST['unsort'])){
				SectionManager::instance()->edit($section->get('id'), array('entry_order' => NULL, 'entry_order_direction' => NULL));
				redirect(Administration::instance()->getCurrentPageURL());
			}*/

			/*
			$this->Form->setAttribute('action', Administration::instance()->getCurrentPageURL(). '?pg=' . $current_page.($filter ? "&amp;filter=$field_handle:$filter_value" : ''));

			## Remove the create button if there is a section link field, and no filtering set for it
			$section_links = $section->fetchFields('sectionlink');

			if(count($section_links) > 1 || (!$filter && $section_links) || (is_object($section_links[0]) && $filter != $section_links[0]->get('id'))){
				$this->appendSubheading($section->get('name'));
			}
			else{
				$this->appendSubheading($section->get('name'), Widget::Anchor(__('Create New'), Administration::instance()->getCurrentPageURL().'new/'.($filter ? '?prepopulate['.$filter.']=' . $filter_value : ''), __('Create a new entry'), 'create button'));
			}

			if(is_null(EntryManager::instance()->getFetchSorting()->field) && is_null(EntryManager::instance()->getFetchSorting()->direction)){
				EntryManager::instance()->setFetchSortingDirection('DESC');
			}

			$entries = EntryManager::instance()->fetchByPage($current_page, $section_id, Symphony::Configuration()->get('pagination_maximum_rows', 'symphony'), $where, $joins);

			$aTableHead = array();

			$visible_columns = $section->fetchVisibleColumns();

			if(is_array($visible_columns) && !empty($visible_columns)){
				foreach($visible_columns as $column){

					$label = $column->get('label');

					if($column->isSortable()) {

						if($column->get('id') == $section->get('entry_order')){
							$link = Administration::instance()->getCurrentPageURL() . '?pg='.$current_page.'&amp;sort='.$column->get('id').'&amp;order='. ($section->get('entry_order_direction') == 'desc' ? 'asc' : 'desc').($filter ? "&amp;filter=$field_handle:$filter_value" : '');
							$anchor = Widget::Anchor($label, $link, __('Sort by %1$s %2$s', array(($section->get('entry_order_direction') == 'desc' ? __('ascending') : __('descending')), strtolower($column->get('label')))), 'active');
						}

						else{
							$link = Administration::instance()->getCurrentPageURL() . '?pg='.$current_page.'&amp;sort='.$column->get('id').'&amp;order=asc'.($filter ? "&amp;filter=$field_handle:$filter_value" : '');
							$anchor = Widget::Anchor($label, $link, __('Sort by %1$s %2$s', array(__('ascending'), strtolower($column->get('label')))));
						}

						$aTableHead[] = array($anchor, 'col');
					}

					else $aTableHead[] = array($label, 'col');
				}
			}

			else $aTableHead[] = array(__('ID'), 'col');

			$child_sections = NULL;

			$associated_sections = $section->fetchAssociatedSections();
			if(is_array($associated_sections) && !empty($associated_sections)){
				$child_sections = array();
				foreach($associated_sections as $key => $as){
					$child_sections[$key] = SectionManager::instance()->fetch($as['child_section_id']);
					$aTableHead[] = array($child_sections[$key]->get('name'), 'col');
				}
			}

			## Table Body
			$aTableBody = array();

			if(!is_array($entries['records']) || empty($entries['records'])){

				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}

			else{

				$bOdd = true;


				$field_pool = array();
				if(is_array($visible_columns) && !empty($visible_columns)){
					foreach($visible_columns as $column){
						$field_pool[$column->get('id')] = $column;
					}
				}

				foreach($entries['records'] as $entry){

					$tableData = array();

					## Setup each cell
					if(!is_array($visible_columns) || empty($visible_columns)){
						$tableData[] = Widget::TableData(Widget::Anchor($entry->get('id'), Administration::instance()->getCurrentPageURL() . 'edit/' . $entry->get('id') . '/'));
					}

					else{

						$link = Widget::Anchor(
							'None',
							Administration::instance()->getCurrentPageURL() . 'edit/' . $entry->get('id') . '/',
							$entry->get('id'),
							'content'
						);

						foreach ($visible_columns as $position => $column) {
							$data = $entry->getData($column->get('id'));
							$field = $field_pool[$column->get('id')];

							$value = $field->prepareTableValue($data, ($position == 0 ? $link : null), $entry->get('id'));

							if (!is_object($value) && strlen(trim($value)) == 0) {
								$value = ($position == 0 ? $link->generate() : __('None'));
							}

							if ($value == 'None') {
								$tableData[] = Widget::TableData($value, 'inactive');

							} else {
								$tableData[] = Widget::TableData($value);
							}

							unset($field);
						}
					}

					if(is_array($child_sections) && !empty($child_sections)){
						foreach($child_sections as $key => $as){

							$field = FieldManager::instance()->fetch((int)$associated_sections[$key]['child_section_field_id']);

							$parent_section_field_id = (int)$associated_sections[$key]['parent_section_field_id'];

							if(!is_null($parent_section_field_id)){
								$search_value = $field->fetchAssociatedEntrySearchValue(
									$entry->getData($parent_section_field_id),
									$parent_section_field_id,
									$entry->get('id')
								);
							}

							else{
								$search_value = $entry->get('id');
							}

							$associated_entry_count = $field->fetchAssociatedEntryCount($search_value);

							$tableData[] = Widget::TableData(
								Widget::Anchor(
									sprintf('%d &rarr;', max(0, intval($associated_entry_count))),
									sprintf(
										'%s/symphony/publish/%s/?filter=%s:%s',
										URL,
										$as->get('handle'),
										$field->get('element_name'),
										rawurlencode($search_value)
									),
									$entry->get('id'),
									'content')
							);
						}
					}

					$tableData[count($tableData) - 1]->appendChild(Widget::Input('items['.$entry->get('id').']', NULL, 'checkbox'));

					## Add a row to the body array, assigning each cell to the row
					$aTableBody[] = Widget::TableRow($tableData, ($bOdd ? 'odd' : NULL));

					$bOdd = !$bOdd;

				}
			}

			$table = Widget::Table(
				Widget::TableHead($aTableHead),
				NULL,
				Widget::TableBody($aTableBody)
			);

			$this->Form->appendChild($table);


			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				array(NULL, false, __('With Selected...')),
				array('delete', false, __('Delete'))
			);

			$toggable_fields = $section->fetchToggleableFields();

			if (is_array($toggable_fields) && !empty($toggable_fields)) {
				$index = 2;

				foreach ($toggable_fields as $field) {
					$options[$index] = array('label' => __('Set %s', array($field->get('label'))), 'options' => array());

					foreach ($field->getToggleStates() as $value => $state) {
						$options[$index]['options'][] = array('toggle-' . $field->get('id') . '-' . $value, false, $state);
					}

					$index++;
				}
			}

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);

			if($entries['total-pages'] > 1){

				$ul = new XMLElement('ul');
				$ul->setAttribute('class', 'page');

				## First
				$li = new XMLElement('li');
				if($current_page > 1) $li->appendChild(Widget::Anchor(__('First'), Administration::instance()->getCurrentPageURL(). '?pg=1'.($filter ? "&amp;filter=$field_handle:$filter_value" : '')));
				else $li->setValue(__('First'));
				$ul->appendChild($li);

				## Previous
				$li = new XMLElement('li');
				if($current_page > 1) $li->appendChild(Widget::Anchor(__('&larr; Previous'), Administration::instance()->getCurrentPageURL(). '?pg=' . ($current_page - 1).($filter ? "&amp;filter=$field_handle:$filter_value" : '')));
				else $li->setValue(__('&larr; Previous'));
				$ul->appendChild($li);

				## Summary
				$li = new XMLElement('li', __('Page %1$s of %2$s', array($current_page, max($current_page, $entries['total-pages']))));
				$li->setAttribute('title', __('Viewing %1$s - %2$s of %3$s entries', array($entries['start'], min($entries['limit'], max(1, $entries['remaining-entries'])), $entries['total-entries'])));
				$ul->appendChild($li);

				## Next
				$li = new XMLElement('li');
				if($current_page < $entries['total-pages']) $li->appendChild(Widget::Anchor(__('Next &rarr;'), Administration::instance()->getCurrentPageURL(). '?pg=' . ($current_page + 1).($filter ? "&amp;filter=$field_handle:$filter_value" : '')));
				else $li->setValue(__('Next &rarr;'));
				$ul->appendChild($li);

				## Last
				$li = new XMLElement('li');
				if($current_page < $entries['total-pages']) $li->appendChild(Widget::Anchor(__('Last'), Administration::instance()->getCurrentPageURL(). '?pg=' . $entries['total-pages'].($filter ? "&amp;filter=$field_handle:$filter_value" : '')));
				else $li->setValue(__('Last'));
				$ul->appendChild($li);

				$this->Form->appendChild($ul);

			}
			*/
		}

		function __actionIndex(){
			$checked = @array_keys($_POST['items']);

			if(is_array($checked) && !empty($checked)){
				switch($_POST['with-selected']) {

	            	case 'delete':

						###
						# Delegate: Delete
						# Description: Prior to deletion of entries. Array of Entries is provided.
						#              The array can be manipulated
						ExtensionManager::instance()->notifyMembers('Delete', '/publish/', array('entry_id' => &$checked));

						$EntryManager = new EntryManager($this->_Parent);

						EntryManager::instance()->delete($checked);

					 	redirect($_SERVER['REQUEST_URI']);

					default:

						## TODO: Add delegate

						list($option, $field_id, $value) = explode('-', $_POST['with-selected'], 3);

						if($option == 'toggle'){

							$field = FieldManager::instance()->fetch($field_id);

							foreach($checked as $entry_id){
								$entry = EntryManager::instance()->fetch($entry_id);
								$entry[0]->setData($field_id, $field->toggleFieldData($entry[0]->getData($field_id), $value));
								$entry[0]->commit();
							}

							redirect($_SERVER['REQUEST_URI']);

						}

						break;
				}
			}
		}

		/* TODO: Remove once create/edit form becomes one and the same */
		private function __wrapFieldWithDiv(Field $field, Entry $entry=NULL){
			$div = new XMLElement('div', NULL, array(
					'class' => sprintf('field field-%s %s %s',
						$field->handle(),
						($field->get('required') == 'yes' ? 'required' : ''),
						$this->__calculateWidth($field->get('width'))
					)
				)
			);

			$field->displayPublishPanel(
				$div, (!is_null($entry) ? $entry->getData($field->get('id')) : NULL),
				(isset($this->_errors[$field->get('id')]) ? $this->_errors[$field->get('id')] : NULL),
				null,
				null,
				(!is_null($entry) && is_numeric($entry->get('id')) ? $entry->get('id') : NULL)
			);

			return $div;
		}

		public static function __calculateWidth($width) {
			switch($width) {
				case "3": return 'large';
				case "2": return 'medium';
				default: return 'small';
			}
		}

		public function __viewNew() {

			/*if(!$section_id = SectionManager::instance()->fetchIDFromHandle($this->_context['section_handle']))
				Administration::instance()->customError(E_USER_ERROR, __('Unknown Section'), __('The Section you are looking for, <code>%s</code>, could not be found.', array($this->_context['section_handle'])), false, true);

		    $section = SectionManager::instance()->fetch($section_id);*/

			$section = Section::load(sprintf('%s/%s.xml', SECTIONS, $this->_context['section_handle']));

			$this->setPageType('form');
			$this->Form->setAttribute('enctype', 'multipart/form-data');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), $section->name)));
			$this->appendSubheading(__('Untitled'));
			$this->Form->appendChild(Widget::Input('MAX_FILE_SIZE', Symphony::Configuration()->get('max_upload_size', 'admin'), 'hidden'));

			// Check that a layout and fields exist
			if(isset($section->fields)) {
				return $this->pageAlert(
					__(
						'It looks like you\'re trying to create an entry. Perhaps you want fields first? <a href="%s">Click here to create some.</a>',
						array(
							ADMIN_URL . '/blueprints/sections/edit/' . $section->handle . '/'
						)
					),
					Alert::ERROR
				);
			}

			// Load all the fields for this section
			$section_fields = array();
			foreach($section->fields as $index => $field) {
				$section_fields[$field->get('element_name')] = $field;
			}

			// Parse the layout
 			foreach($section->layout as $a_layout) {
				foreach($a_layout as $a_fieldset) {

					$fieldset = new XMLElement('fieldset');
					$fieldset->appendChild(
						new XMLElement('h3', $a_fieldset->label, array('class' => 'legend'))
					);

					// Got the fieldsets, now lets loop the rows
					foreach($a_fieldset->rows as $a_row) {
						$do_grouping = (count($a_row) > 1) ? true : false;

						if($do_grouping) $group = new XMLElement('div', NULL, array('class' => 'group'));

						foreach($a_row as $a_field) {

							$field = $section_fields[$a_field];

							$div = new XMLElement('div', NULL, array(
									'class' => trim(sprintf('field field-%s %s %s',
										$field->handle(),
										$this->__calculateWidth($field->get('width')),
										($field->get('required') == 'yes' ? 'required' : '')
									))
								)
							);

							$field->displayPublishPanel($div, null, (isset($this->_errors[$field->get('id')])
								? $this->_errors[$field->get('id')]
								: NULL)
							);

							($do_grouping) ? $group->appendChild($div) : $fieldset->appendChild($div);

						}

						($do_grouping) ? $fieldset->appendChild($group) : NULL;

					}

					$this->Form->appendChild($fieldset);
				}
			}

			// Check if there is a field to prepopulate
			if (isset($_REQUEST['prepopulate'])) {
				$field_handle = array_shift(array_keys($_REQUEST['prepopulate']));
				$value = stripslashes(rawurldecode(array_shift($_REQUEST['prepopulate'])));

				$this->Form->prependChild(Widget::Input(
					"prepopulate[{$field_handle}]",
					rawurlencode($value),
					'hidden'
				));

				/* Need FieldManager first.
				// The actual pre-populating should only happen if there is not existing fields post data
				if(!isset($_POST['fields']) && $field = FieldManager::instance()->fetch($field_id)) {
					$entry->setData(
						$field->get('id'),
						$field->processRawFieldData($value, $error, true)
					);
				}
				*/
			}

/*

			// If there is post data floating around, due to errors, create an entry object
			if (isset($_POST['fields'])) {
				$entry = EntryManager::instance()->create();
				$entry->set('section_id', $section_id);
				$entry->setDataFromPost($_POST['fields'], $error, true);
			}

			// Brand new entry, so need to create some various objects
			else {
				$entry = EntryManager::instance()->create();
				$entry->set('section_id', $section_id);
			}
*/
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', __('Create Entry'), 'submit', array('accesskey' => 's')));

			$this->Form->appendChild($div);
		}

		function __actionNew(){

			if(array_key_exists('save', $_POST['action']) || array_key_exists("done", $_POST['action'])) {


				$section_id = SectionManager::instance()->fetchIDFromHandle($this->_context['section_handle']);

			    if(!$section = SectionManager::instance()->fetch($section_id))
					Administration::instance()->customError(E_USER_ERROR, __('Unknown Section'), __('The Section you are looking for, <code>%s</code>, could not be found.', $this->_context['section_handle']), false, true);


				$entry =& EntryManager::instance()->create();
				$entry->set('section_id', $section_id);
				$entry->set('user_id', Administration::instance()->User->id);
				$entry->set('creation_date', DateTimeObj::get('Y-m-d H:i:s'));
				$entry->set('creation_date_gmt', DateTimeObj::getGMT('Y-m-d H:i:s'));

				$post = General::getPostData();
				$fields = $post['fields'];

				if(__ENTRY_FIELD_ERROR__ == $entry->checkPostData($fields, $this->_errors)):
					$this->pageAlert(__('Some errors were encountered while attempting to save.'), Alert::ERROR);

				elseif(__ENTRY_OK__ != $entry->setDataFromPost($fields, $error)):
					$this->pageAlert($error['message'], Alert::ERROR);

				else:

					###
					# Delegate: EntryPreCreate
					# Description: Just prior to creation of an Entry. Entry object and fields are provided
					ExtensionManager::instance()->notifyMembers('EntryPreCreate', '/publish/new/', array('section' => $section, 'fields' => &$fields, 'entry' => &$entry));

					if(!$entry->commit()){
						define_safe('__SYM_DB_INSERT_FAILED__', true);
						$this->pageAlert(NULL, Alert::ERROR);

					}

					else{

						###
						# Delegate: EntryPostCreate
						# Description: Creation of an Entry. New Entry object is provided.
						ExtensionManager::instance()->notifyMembers('EntryPostCreate', '/publish/new/', array('section' => $section, 'entry' => $entry, 'fields' => $fields));

						$prepopulate_field_id = $prepopulate_value = NULL;
						if(isset($_POST['prepopulate'])){
							$prepopulate_field_id = array_shift(array_keys($_POST['prepopulate']));
							$prepopulate_value = stripslashes(rawurldecode(array_shift($_POST['prepopulate'])));
						}

			  		   	redirect(sprintf(
							'%s/symphony/publish/%s/edit/%d/created%s/',
							URL,
							$this->_context['section_handle'],
							$entry->get('id'),
							(!is_null($prepopulate_field_id) ? ":{$prepopulate_field_id}:{$prepopulate_value}" : NULL)
						));

					}

				endif;
			}

		}

		function __viewEdit() {

			if(!$section_id = SectionManager::instance()->fetchIDFromHandle($this->_context['section_handle']))
				Administration::instance()->customError(E_USER_ERROR, __('Unknown Section'), __('The Section you are looking for, <code>%s</code>, could not be found.', array($this->_context['section_handle'])), false, true);

		    $section = SectionManager::instance()->fetch($section_id);

			$entry_id = intval($this->_context['entry_id']);

			EntryManager::instance()->setFetchSorting('id', 'DESC');

			if(!$existingEntry = EntryManager::instance()->fetch($entry_id)) Administration::instance()->customError(E_USER_ERROR, __('Unknown Entry'), __('The entry you are looking for could not be found.'), false, true);
			$existingEntry = $existingEntry[0];

			// If there is post data floating around, due to errors, create an entry object
			if (isset($_POST['fields'])) {
				$fields = $_POST['fields'];

				$entry =& EntryManager::instance()->create();
				$entry->set('section_id', $existingEntry->get('section_id'));
				$entry->set('id', $entry_id);

				$entry->setDataFromPost($fields, $error, true);
			}

			// Editing an entry, so need to create some various objects
			else {
				$entry = $existingEntry;

				if (!$section) {
					$section = SectionManager::instance()->fetch($entry->get('section_id'));
				}
			}

			###
			# Delegate: EntryPreRender
			# Description: Just prior to rendering of an Entry edit form. Entry object can be modified.
			ExtensionManager::instance()->notifyMembers('EntryPreRender', '/publish/edit/', array('section' => $section, 'entry' => &$entry, 'fields' => $fields));

			if(isset($this->_context['flag'])){

				$link = 'publish/'.$this->_context['section_handle'].'/new/';

				list($flag, $field_id, $value) = preg_split('/:/i', $this->_context['flag'], 3);

				if(is_numeric($field_id) && $value){
					$link .= "?prepopulate[$field_id]=$value";

					$this->Form->prependChild(Widget::Input(
						"prepopulate[{$field_id}]",
						rawurlencode($value),
						'hidden'
					));
				}

				switch($flag){

					case 'saved':

						$this->pageAlert(
							__(
								'Entry updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Entries</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									ADMIN_URL . "/$link",
									ADMIN_URL . '/publish/'.$this->_context['section_handle'].'/'
								)
							),
							Alert::SUCCESS);

						break;

					case 'created':
						$this->pageAlert(
							__(
								'Entry created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Entries</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									ADMIN_URL . "/$link",
									ADMIN_URL . '/publish/'.$this->_context['section_handle'].'/'
								)
							),
							Alert::SUCCESS);
						break;

				}
			}

			### Determine the page title
			$field_id = Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_fields` WHERE `parent_section` = '".$section->get('id')."' ORDER BY `sortorder` LIMIT 1");
			$field = FieldManager::instance()->fetch($field_id);

			$title = trim(strip_tags($field->prepareTableValue($existingEntry->getData($field->get('id')), NULL, $entry_id)));

			if (trim($title) == '') {
				$title = 'Untitled';
			}

			// Check if there is a field to prepopulate
			if (isset($_REQUEST['prepopulate'])) {
				$field_id = array_shift(array_keys($_REQUEST['prepopulate']));
				$value = stripslashes(rawurldecode(array_shift($_REQUEST['prepopulate'])));

				$this->Form->prependChild(Widget::Input(
					"prepopulate[{$field_id}]",
					rawurlencode($value),
					'hidden'
				));
			}

			$this->setPageType('form');
			$this->Form->setAttribute('enctype', 'multipart/form-data');
			$this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array(__('Symphony'), $section->get('name'), $title)));
			$this->appendSubheading($title);
			$this->Form->appendChild(Widget::Input('MAX_FILE_SIZE', Symphony::Configuration()->get('max_upload_size', 'admin'), 'hidden'));

			###

			$primary = new XMLElement('fieldset');
			$primary->setAttribute('class', 'primary');

			$sidebar_fields = $section->fetchFields(NULL, 'sidebar');
			$main_fields = $section->fetchFields(NULL, 'main');

			if((!is_array($main_fields) || empty($main_fields)) && (!is_array($sidebar_fields) || empty($sidebar_fields))){
				$primary->appendChild(new XMLElement('p', __('It looks like your trying to create an entry. Perhaps you want fields first? <a href="%s">Click here to create some.</a>', array(ADMIN_URL . '/blueprints/sections/edit/'. $section->get('id') . '/'))));
			}

			else{

				if(is_array($main_fields) && !empty($main_fields)){
					foreach($main_fields as $field){
						$primary->appendChild($this->__wrapFieldWithDiv($field, $entry));
					}

					$this->Form->appendChild($primary);
				}

				if(is_array($sidebar_fields) && !empty($sidebar_fields)){
					$sidebar = new XMLElement('fieldset');
					$sidebar->setAttribute('class', 'secondary');

					foreach($sidebar_fields as $field){
						$sidebar->appendChild($this->__wrapFieldWithDiv($field, $entry));
					}

					$this->Form->appendChild($sidebar);
				}

			}

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', array('accesskey' => 's')));

			$button = new XMLElement('button', __('Delete'));
			$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => __('Delete this entry'), 'type' => 'submit'));
			$div->appendChild($button);

			$this->Form->appendChild($div);

		}

		function __actionEdit(){

			$entry_id = intval($this->_context['entry_id']);

			if(@array_key_exists('save', $_POST['action']) || @array_key_exists("done", $_POST['action'])){


			    if(!$ret = EntryManager::instance()->fetch($entry_id)) Administration::instance()->customError(E_USER_ERROR, __('Unknown Entry'), __('The entry you are looking for could not be found.'), false, true);

				$entry = $ret[0];

				$section = SectionManager::instance()->fetch($entry->get('section_id'));

				$post = General::getPostData();
				$fields = $post['fields'];

				if(__ENTRY_FIELD_ERROR__ == $entry->checkPostData($fields, $this->_errors)):
					$this->pageAlert(__('Some errors were encountered while attempting to save.'), Alert::ERROR);

				elseif(__ENTRY_OK__ != $entry->setDataFromPost($fields, $error)):
					$this->pageAlert($error['message'], Alert::ERROR);

				else:


					###
					# Delegate: EntryPreEdit
					# Description: Just prior to editing of an Entry.
					ExtensionManager::instance()->notifyMembers('EntryPreEdit', '/publish/edit/', array('section' => $section, 'entry' => &$entry, 'fields' => $fields));

					if(!$entry->commit()){
						define_safe('__SYM_DB_INSERT_FAILED__', true);
						$this->pageAlert(NULL, Alert::ERROR);

					}

					else{

						###
						# Delegate: EntryPostEdit
						# Description: Editing an entry. Entry object is provided.
						ExtensionManager::instance()->notifyMembers('EntryPostEdit', '/publish/edit/', array('section' => $section, 'entry' => $entry, 'fields' => $fields));


						$prepopulate_field_id = $prepopulate_value = NULL;
						if(isset($_POST['prepopulate'])){
							$prepopulate_field_id = array_shift(array_keys($_POST['prepopulate']));
							$prepopulate_value = stripslashes(rawurldecode(array_shift($_POST['prepopulate'])));
						}

			  		    //redirect(ADMIN_URL . '/publish/' . $this->_context['section_handle'] . '/edit/' . $entry_id . '/saved/');

			  		   	redirect(sprintf(
							'%s/symphony/publish/%s/edit/%d/saved%s/',
							URL,
							$this->_context['section_handle'],
							$entry->get('id'),
							(!is_null($prepopulate_field_id) ? ":{$prepopulate_field_id}:{$prepopulate_value}" : NULL)
						));

					}

				endif;
			}

			elseif(@array_key_exists('delete', $_POST['action']) && is_numeric($entry_id)){

				###
				# Delegate: Delete
				# Description: Prior to deleting an entry. Entry ID is provided, as an array to remain compatible with other Delete delegate call
				ExtensionManager::instance()->notifyMembers('Delete', '/publish/', array('entry_id' => $entry_id));


				EntryManager::instance()->delete($entry_id);

				redirect(ADMIN_URL . '/publish/'.$this->_context['section_handle'].'/');
			}

		}

	}


?>