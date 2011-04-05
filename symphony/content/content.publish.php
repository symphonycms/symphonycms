<?php
	/**
	 * @package content
	 */

	/**
	 * The Publish page is where the majority of an Authors time will
	 * be spent in Symphony with adding, editing and removing entries
	 * from Sections. This Page controls the entries tableas well as
	 * the Entry creation screens.
	 */
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.entrymanager.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');

	Class contentPublish extends AdministrationPage{

		public $_errors;

		public function __construct(&$parent){
			parent::__construct($parent);
			$this->_errors = array();
		}

		public function action(){
			$this->__switchboard('action');
		}

		public function __switchboard($type='view'){

			$function = ($type == 'action' ? '__action' : '__view') . ucfirst($this->_context['page']);

			if(!method_exists($this, $function)) {
				## If there is no action function, just return without doing anything
				if($type == 'action') return;

				Administration::instance()->errorPageNotFound();
			}

			$this->$function();
		}

		public function view(){
			$this->__switchboard();
		}

		public function __viewIndex(){

			$sectionManager = new SectionManager($this->_Parent);

			if(!$section_id = $sectionManager->fetchIDFromHandle($this->_context['section_handle']))
				Administration::instance()->customError(__('Unknown Section'), __('The Section you are looking, <code>%s</code> for could not be found.', array($this->_context['section_handle'])));

			$section = $sectionManager->fetch($section_id);

			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), $section->get('name'))));
			$this->Form->setAttribute("class", $this->_context['section_handle']);

			$entryManager = new EntryManager($this->_Parent);

			$filter = $filter_value = $where = $joins = NULL;
			$current_page = (isset($_REQUEST['pg']) && is_numeric($_REQUEST['pg']) ? max(1, intval($_REQUEST['pg'])) : 1);

			if(isset($_REQUEST['filter'])){

				list($field_handle, $filter_value) = explode(':', $_REQUEST['filter'], 2);

				$field_names = explode(',', $field_handle);

				foreach($field_names as $field_name) {

					$filter_value = rawurldecode($filter_value);

					$filter = Symphony::Database()->fetchVar('id', 0, "SELECT `f`.`id`
										  FROM `tbl_fields` AS `f`, `tbl_sections` AS `s`
										  WHERE `s`.`id` = `f`.`parent_section`
										  AND f.`element_name` = '$field_name'
										  AND `s`.`handle` = '".$section->get('handle')."' LIMIT 1");
					$field =& $entryManager->fieldManager->fetch($filter);

					if($field instanceof Field) {
						// For deprecated reasons, call the old, typo'd function name until the switch to the
						// properly named buildDSRetrievalSQL function.
						$field->buildDSRetrivalSQL(array($filter_value), $joins, $where, false);
						$filter_value = rawurlencode($filter_value);
					}

				}

				if (!is_null($where)) {
					$where = str_replace('AND', 'OR', $where); // multiple fields need to be OR
					$where = trim($where);
					$where = ' AND (' . substr($where, 2, strlen($where)) . ')'; // replace leading OR with AND
				}

			}

			if(isset($_REQUEST['sort']) && is_numeric($_REQUEST['sort'])){
				$sort = intval($_REQUEST['sort']);
				$order = ($_REQUEST['order'] ? strtolower($_REQUEST['order']) : 'asc');

				if($section->get('entry_order') != $sort || $section->get('entry_order_direction') != $order){
					$sectionManager->edit($section->get('id'), array('entry_order' => $sort, 'entry_order_direction' => $order));
					redirect(Administration::instance()->getCurrentPageURL().($filter ? "?filter=$field_handle:$filter_value" : ''));
				}
			}

			elseif(isset($_REQUEST['unsort'])){
				$sectionManager->edit($section->get('id'), array('entry_order' => NULL, 'entry_order_direction' => NULL));
				redirect(Administration::instance()->getCurrentPageURL());
			}

			$this->Form->setAttribute('action', Administration::instance()->getCurrentPageURL(). '?pg=' . $current_page.($filter ? "&amp;filter=$field_handle:$filter_value" : ''));

			$this->appendSubheading($section->get('name'), Widget::Anchor(__('Create New'), Administration::instance()->getCurrentPageURL().'new/'.($filter ? '?prepopulate['.$filter.']=' . $filter_value : ''), __('Create a new entry'), 'create button', NULL, array('accesskey' => 'c')));

			if(is_null($entryManager->getFetchSorting()->field) && is_null($entryManager->getFetchSorting()->direction)){
				$entryManager->setFetchSortingDirection('DESC');
			}

			$entries = $entryManager->fetchByPage($current_page, $section_id, Symphony::Configuration()->get('pagination_maximum_rows', 'symphony'), $where, $joins);

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

						$aTableHead[] = array($anchor, 'col', array('id' => 'field-' . $column->get('id'), 'class' => 'field-' . $column->get('type')));
					}

					else $aTableHead[] = array($label, 'col', array('id' => 'field-' . $column->get('id'), 'class' => 'field-' . $column->get('type')));
				}
			}

			else $aTableHead[] = array(__('ID'), 'col');

			$child_sections = array();
			$associated_sections = $section->fetchAssociatedSections(true);
			if(is_array($associated_sections) && !empty($associated_sections)){
				foreach($associated_sections as $key => $as){
					$child_sections[$key] = $sectionManager->fetch($as['child_section_id']);
					$aTableHead[] = array($child_sections[$key]->get('name'), 'col');
				}
			}

			/**
			 * Allows the creation of custom entries tablecolumns. Called
			 * after all the Section Visible columns have been added  as well
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

			## Table Body
			$aTableBody = array();

			if(!is_array($entries['records']) || empty($entries['records'])){

				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}

			else{

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
								$tableData[] = Widget::TableData($value, 'inactive field-' . $column->get('type') . ' field-' . $column->get('id'));

							} else {
								$tableData[] = Widget::TableData($value, 'field-' . $column->get('type') . ' field-' . $column->get('id'));
							}

							unset($field);
						}
					}

					if(is_array($child_sections) && !empty($child_sections)){
						foreach($child_sections as $key => $as){

							$field = $entryManager->fieldManager->fetch((int)$associated_sections[$key]['child_section_field_id']);

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

							if(!is_array($search_value)) {
								$associated_entry_count = $field->fetchAssociatedEntryCount($search_value);

								$tableData[] = Widget::TableData(
									Widget::Anchor(
										sprintf('%d &rarr;', max(0, intval($associated_entry_count))),
										sprintf(
											'%s/publish/%s/?filter=%s:%s',
											SYMPHONY_URL,
											$as->get('handle'),
											$field->get('element_name'),
											rawurlencode($search_value)
										),
										$entry->get('id'),
										'content')
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
					 * @param integer $entry_id
					 *  The Entry ID for this row
					 */
					Symphony::ExtensionManager()->notifyMembers('AddCustomPublishColumnData', '/publish/', array('tableData' => &$tableData, 'section_id' => $section->get('id'), 'entry_id' => $entry));

					$tableData[count($tableData) - 1]->appendChild(Widget::Input('items['.$entry->get('id').']', NULL, 'checkbox'));

					## Add a row to the body array, assigning each cell to the row
					$aTableBody[] = Widget::TableRow($tableData, NULL, 'id-' . $entry->get('id'));
				}
			}

			$table = Widget::Table(
				Widget::TableHead($aTableHead),
				NULL,
				Widget::TableBody($aTableBody),
				'selectable'
			);

			$this->Form->appendChild($table);

			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				array(NULL, false, __('With Selected...')),
				array('delete', false, __('Delete'), 'confirm', null, array(
					'data-message' => __('Are you sure you want to delete the selected entries?')
				))
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
				$li->setAttribute('title', __('Viewing %1$s - %2$s of %3$s entries', array(
					$entries['start'],
					($current_page != $entries['total-pages']) ? $current_page * Symphony::Configuration()->get('pagination_maximum_rows', 'symphony') : $entries['total-entries'],
					$entries['total-entries']
				)));
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
		}

		public function __actionIndex(){
			$checked = (is_array($_POST['items'])) ? array_keys($_POST['items']) : null;

			if(is_array($checked) && !empty($checked)){
				switch($_POST['with-selected']) {

					case 'delete':

						/**
						 * Prior to deletion of entries. Array of Entry ID's is provided.
						 * The array can be manipulated
						 *
						 * @delegate Delete
						 * @param string $context
						 * '/publish/'
						 * @param array $checked
						 *  An array of Entry ID's passed by reference
						 */
						Symphony::ExtensionManager()->notifyMembers('Delete', '/publish/', array('entry_id' => &$checked));

						$entryManager = new EntryManager($this->_Parent);
						$entryManager->delete($checked);

					 	redirect($_SERVER['REQUEST_URI']);

					default:

						list($option, $field_id, $value) = explode('-', $_POST['with-selected'], 3);

						if($option == 'toggle'){

							$entryManager = new EntryManager($this->_Parent);
							$field = $entryManager->fieldManager->fetch($field_id);
							$fields = array($field->get('element_name') => $value);

							$sectionManager = new SectionManager($this->_Parent);
							$section = $sectionManager->fetch($field->get('parent_section'));

							foreach($checked as $entry_id){
								$entry = $entryManager->fetch($entry_id);

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
								Symphony::ExtensionManager()->notifyMembers('EntryPreEdit', '/publish/edit/', array('section' => $section, 'entry' => &$entry[0], 'fields' => $fields));

								$entry[0]->setData($field_id, $field->toggleFieldData($entry[0]->getData($field_id), $value));
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
								Symphony::ExtensionManager()->notifyMembers('EntryPostEdit', '/publish/edit/', array('section' => $section, 'entry' => $entry[0], 'fields' => $fields));
							}

							redirect($_SERVER['REQUEST_URI']);

						}

						break;
				}
			}
		}

		public function __viewNew() {
			$sectionManager = new SectionManager($this->_Parent);

			if(!$section_id = $sectionManager->fetchIDFromHandle($this->_context['section_handle']))
				Administration::instance()->customError(__('Unknown Section'), __('The Section you are looking, <code>%s</code> for could not be found.', array($this->_context['section_handle'])));

			$section = $sectionManager->fetch($section_id);

			$this->setPageType('form');
			$this->Form->setAttribute('enctype', 'multipart/form-data');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), $section->get('name'))));
			$this->appendSubheading(__('Untitled'));
			$this->Form->appendChild(Widget::Input('MAX_FILE_SIZE', Symphony::Configuration()->get('max_upload_size', 'admin'), 'hidden'));

			$entryManager = new EntryManager($this->_Parent);

			// If there is post data floating around, due to errors, create an entry object
			if (isset($_POST['fields'])) {
				$entry = $entryManager->create();
				$entry->set('section_id', $section_id);
				$entry->setDataFromPost($_POST['fields'], $error, true);
			}

			// Brand new entry, so need to create some various objects
			else {
				$entry = $entryManager->create();
				$entry->set('section_id', $section_id);
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

				// The actual pre-populating should only happen if there is not existing fields post data
				if(!isset($_POST['fields']) && $field = $entryManager->fieldManager->fetch($field_id)) {
					$entry->setData(
						$field->get('id'),
						$field->processRawFieldData($value, $error, true)
					);
				}
			}

			$primary = new XMLElement('fieldset');
			$primary->setAttribute('class', 'primary');

			$sidebar_fields = $section->fetchFields(NULL, 'sidebar');
			$main_fields = $section->fetchFields(NULL, 'main');

			if ((!is_array($main_fields) || empty($main_fields)) && (!is_array($sidebar_fields) || empty($sidebar_fields))) {
				$primary->appendChild(new XMLElement('p', __(
					'It looks like you\'re trying to create an entry. Perhaps you want fields first? <a href="%s">Click here to create some.</a>',
					array(
						SYMPHONY_URL . '/blueprints/sections/edit/' . $section->get('id') . '/'
					)
				)));
				$this->Form->appendChild($primary);
			}

			else {
				if (is_array($main_fields) && !empty($main_fields)) {
					foreach ($main_fields as $field) {
						$primary->appendChild($this->__wrapFieldWithDiv($field, $entry));
					}

					$this->Form->appendChild($primary);
				}

				if (is_array($sidebar_fields) && !empty($sidebar_fields)) {
					$sidebar = new XMLElement('fieldset');
					$sidebar->setAttribute('class', 'secondary');

					foreach ($sidebar_fields as $field) {
						$sidebar->appendChild($this->__wrapFieldWithDiv($field, $entry));
					}

					$this->Form->appendChild($sidebar);
				}
			}

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', __('Create Entry'), 'submit', array('accesskey' => 's')));

			$this->Form->appendChild($div);
		}

		public function __actionNew(){

			if(array_key_exists('save', $_POST['action']) || array_key_exists("done", $_POST['action'])) {

				$sectionManager = new SectionManager($this->_Parent);

				$section_id = $sectionManager->fetchIDFromHandle($this->_context['section_handle']);

				if(!$section = $sectionManager->fetch($section_id))
					Administration::instance()->customError(__('Unknown Section'), __('The Section you are looking, <code>%s</code> for could not be found.', $this->_context['section_handle']));

				$entryManager = new EntryManager($this->_Parent);

				$entry =& $entryManager->create();
				$entry->set('section_id', $section_id);
				$entry->set('author_id', Administration::instance()->Author->get('id'));
				$entry->set('creation_date', DateTimeObj::get('Y-m-d H:i:s'));
				$entry->set('creation_date_gmt', DateTimeObj::getGMT('Y-m-d H:i:s'));

				$fields = $_POST['fields'];

				## Combine FILES and POST arrays, indexed by their custom field handles
				if(isset($_FILES['fields'])){
					$filedata = General::processFilePostData($_FILES['fields']);

					foreach($filedata as $handle => $data){
						if(!isset($fields[$handle])) $fields[$handle] = $data;
						elseif(isset($data['error']) && $data['error'] == 4) $fields['handle'] = NULL;
						else{

							foreach($data as $ii => $d){
								if(isset($d['error']) && $d['error'] == 4) $fields[$handle][$ii] = NULL;
								elseif(is_array($d) && !empty($d)){

									foreach($d as $key => $val)
										$fields[$handle][$ii][$key] = $val;
								}
							}
						}
					}
				}

				if(__ENTRY_FIELD_ERROR__ == $entry->checkPostData($fields, $this->_errors)):
					$this->pageAlert(__('Some errors were encountered while attempting to save.'), Alert::ERROR);

				elseif(__ENTRY_OK__ != $entry->setDataFromPost($fields, $error)):
					$this->pageAlert($error['message'], Alert::ERROR);

				else:
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

					if(!$entry->commit()){
						define_safe('__SYM_DB_INSERT_FAILED__', true);
						$this->pageAlert(NULL, Alert::ERROR);

					}

					else{
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

						$prepopulate_field_id = $prepopulate_value = NULL;
						if(isset($_POST['prepopulate'])){
							$prepopulate_field_id = array_shift(array_keys($_POST['prepopulate']));
							$prepopulate_value = stripslashes(rawurldecode(array_shift($_POST['prepopulate'])));
						}

			  		   	redirect(sprintf(
							'%s/publish/%s/edit/%d/created%s/',
							SYMPHONY_URL,
							$this->_context['section_handle'],
							$entry->get('id'),
							(!is_null($prepopulate_field_id) ? ":{$prepopulate_field_id}:{$prepopulate_value}" : NULL)
						));

					}

				endif;
			}

		}

		public function __viewEdit() {
			$sectionManager = new SectionManager($this->_Parent);

			if(!$section_id = $sectionManager->fetchIDFromHandle($this->_context['section_handle']))
				Administration::instance()->customError(__('Unknown Section'), __('The Section you are looking for, <code>%s</code>, could not be found.', array($this->_context['section_handle'])));

			$section = $sectionManager->fetch($section_id);

			$entry_id = intval($this->_context['entry_id']);

			$entryManager = new EntryManager($this->_Parent);
			$entryManager->setFetchSorting('id', 'DESC');

			if(!$existingEntry = $entryManager->fetch($entry_id)) {
				Administration::instance()->customError(__('Unknown Entry'), __('The entry you are looking for could not be found.'));
			}
			$existingEntry = $existingEntry[0];

			// If there is post data floating around, due to errors, create an entry object
			if (isset($_POST['fields'])) {
				$fields = $_POST['fields'];

				$entry =& $entryManager->create();
				$entry->set('section_id', $existingEntry->get('section_id'));
				$entry->set('id', $entry_id);

				$entry->setDataFromPost($fields, $error, true);
			}

			// Editing an entry, so need to create some various objects
			else {
				$entry = $existingEntry;

				if (!$section) {
					$section = $sectionManager->fetch($entry->get('section_id'));
				}
			}

			/**
			 * Just prior to rendering of an Entry edit form.
			 *
			 * @delegate EntryPreRender
			 * @param string $context
			 * '/publish/new/'
			 * @param Section $section
			 * @param Entry $entry
			 * @param array $fields
			 */
			Symphony::ExtensionManager()->notifyMembers('EntryPreRender', '/publish/edit/', array('section' => $section, 'entry' => &$entry, 'fields' => $fields));

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
								'Entry updated at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Entries</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									SYMPHONY_URL . "/$link",
									SYMPHONY_URL . '/publish/'.$this->_context['section_handle'].'/'
								)
							),
							Alert::SUCCESS);

						break;

					case 'created':
						$this->pageAlert(
							__(
								'Entry created at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Entries</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									SYMPHONY_URL . "/$link",
									SYMPHONY_URL . '/publish/'.$this->_context['section_handle'].'/'
								)
							),
							Alert::SUCCESS);
						break;

				}
			}

			### Determine the page title
			$field_id = Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_fields` WHERE `parent_section` = '".$section->get('id')."' ORDER BY `sortorder` LIMIT 1");
			$field = $entryManager->fieldManager->fetch($field_id);

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
				$primary->appendChild(new XMLElement('p', __('It looks like you\'re trying to create an entry. Perhaps you want fields first? <a href="%s">Click here to create some.</a>', array(SYMPHONY_URL . '/blueprints/sections/edit/'. $section->get('id') . '/'))));
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
			$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this entry'), 'type' => 'submit', 'accesskey' => 'd', 'data-message' => __('Are you sure you want to delete this entry?')));
			$div->appendChild($button);

			$this->Form->appendChild($div);

		}

		public function __actionEdit(){

			$entry_id = intval($this->_context['entry_id']);

			if(@array_key_exists('save', $_POST['action']) || @array_key_exists("done", $_POST['action'])){

				$entryManager = new EntryManager($this->_Parent);

				if(!$ret = $entryManager->fetch($entry_id)) {
					Administration::instance()->customError(__('Unknown Entry'), __('The entry you are looking for could not be found.'));
				}
				$entry = $ret[0];

				$sectionManager = new SectionManager($this->_Parent);
				$section = $sectionManager->fetch($entry->get('section_id'));

				$post = General::getPostData();
				$fields = $post['fields'];

				if(__ENTRY_FIELD_ERROR__ == $entry->checkPostData($fields, $this->_errors)):
					$this->pageAlert(__('Some errors were encountered while attempting to save.'), Alert::ERROR);

				elseif(__ENTRY_OK__ != $entry->setDataFromPost($fields, $error)):
					$this->pageAlert($error['message'], Alert::ERROR);

				else:

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

					if(!$entry->commit()){
						define_safe('__SYM_DB_INSERT_FAILED__', true);
						$this->pageAlert(NULL, Alert::ERROR);

					}

					else{

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

						$prepopulate_field_id = $prepopulate_value = NULL;
						if(isset($_POST['prepopulate'])){
							$prepopulate_field_id = array_shift(array_keys($_POST['prepopulate']));
							$prepopulate_value = stripslashes(rawurldecode(array_shift($_POST['prepopulate'])));
						}

						redirect(sprintf(
							'%s/publish/%s/edit/%d/saved%s/',
							SYMPHONY_URL,
							$this->_context['section_handle'],
							$entry->get('id'),
							(!is_null($prepopulate_field_id) ? ":{$prepopulate_field_id}:{$prepopulate_value}" : NULL)
						));
					}

				endif;
			}

			elseif(@array_key_exists('delete', $_POST['action']) && is_numeric($entry_id)){
				/**
				 * Prior to deletion of entries. Array of Entry ID's is provided.
				 * The array can be manipulated
				 *
				 * @delegate Delete
				 * @param string $context
				 * '/publish/'
				 * @param array $checked
				 *  An array of Entry ID's passed by reference
				 */
				Symphony::ExtensionManager()->notifyMembers('Delete', '/publish/', array('entry_id' => $entry_id));

				$entryManager = new EntryManager($this->_Parent);
				$entryManager->delete($entry_id);

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
		private function __wrapFieldWithDiv(Field $field, Entry $entry){
			$div = new XMLElement('div', NULL, array('id' => 'field-' . $field->get('id'), 'class' => 'field field-'.$field->handle().($field->get('required') == 'yes' ? ' required' : '')));
			$field->displayPublishPanel(
				$div, $entry->getData($field->get('id')),
				(isset($this->_errors[$field->get('id')]) ? $this->_errors[$field->get('id')] : NULL),
				null, null, (is_numeric($entry->get('id')) ? $entry->get('id') : NULL)
			);
			return $div;
		}

	}
