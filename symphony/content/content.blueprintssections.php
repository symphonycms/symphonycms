<?php
	/**
	 * @package content
	 */

	/**
	 * This page controls the creation and maintainence of Symphony
	 * Sections through the Section Index and Section Editor.
	 */
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.fieldmanager.php');
	require_once(TOOLKIT . '/class.entrymanager.php');

	Class contentBlueprintsSections extends AdministrationPage{

		public $_errors;

		public function __viewIndex(){
			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Sections'))));
			$this->appendSubheading(__('Sections'), Widget::Anchor(__('Create New'), Administration::instance()->getCurrentPageURL().'new/', __('Create a section'), 'create button', NULL, array('accesskey' => 'c')));

			$sectionManager = new SectionManager($this->_Parent);
			$sections = $sectionManager->fetch(NULL, 'ASC', 'sortorder');

			$aTableHead = array(
				array(__('Name'), 'col'),
				array(__('Entries'), 'col'),
				array(__('Navigation Group'), 'col')
			);

			$aTableBody = array();

			if(!is_array($sections) || empty($sections)){
				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}

			else{
				foreach($sections as $s){

					$entry_count = intval(Symphony::Database()->fetchVar('count', 0, "SELECT count(*) AS `count` FROM `tbl_entries` WHERE `section_id` = '".$s->get('id')."' "));

					## Setup each cell
					$td1 = Widget::TableData(Widget::Anchor($s->get('name'), Administration::instance()->getCurrentPageURL() . 'edit/' . $s->get('id') .'/', NULL, 'content'));
					$td2 = Widget::TableData(Widget::Anchor("$entry_count", SYMPHONY_URL . '/publish/' . $s->get('handle') . '/'));
					$td3 = Widget::TableData($s->get('navigation_group'));

					$td3->appendChild(Widget::Input('items['.$s->get('id').']', 'on', 'checkbox'));

					## Add a row to the body array, assigning each cell to the row
					$aTableBody[] = Widget::TableRow(array($td1, $td2, $td3));

				}
			}

			$table = Widget::Table(
				Widget::TableHead($aTableHead),
				NULL,
				Widget::TableBody($aTableBody),
				'orderable selectable'
			);

			$this->Form->appendChild($table);

			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				array(NULL, false, __('With Selected...')),
				array('delete', false, __('Delete'), 'confirm', null, array(
					'data-message' => __('Are you sure you want to delete the selected sections?')
				)),
				array('delete-entries', false, __('Delete Entries'), 'confirm', null, array(
					'data-message' => __('Are you sure you want to delete all entries in the selected sections?')
				))
			);

			if (is_array($sections) && !empty($sections))  {
				$index = 3;
				$options[$index] = array('label' => __('Set navigation group'), 'options' => array());

				$groups = array();
				foreach($sections as $s){
					if(in_array($s->get('navigation_group'), $groups)) continue;
					$groups[] = $s->get('navigation_group');

					$value = 'set-navigation-group-' . urlencode($s->get('navigation_group'));
					$options[$index]['options'][] = array($value, false, $s->get('navigation_group'));
				}
			}

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);
		}

		public function __viewNew(){

			$this->setPageType('form');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Sections'))));
			$this->appendSubheading(__('Untitled'));

			$fieldManager = new FieldManager($this->_Parent);
			$types = array();

			$fields = is_array($_POST['fields']) ? $_POST['fields'] : array();
			$meta = $_POST['meta'];

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);

			$showEmptyTemplate = (is_array($fields) && !empty($fields) ? false : true);

			if(!$showEmptyTemplate) ksort($fields);

			$meta['entry_order'] = (isset($meta['entry_order']) ? $meta['entry_order'] : 'date');
			$meta['hidden'] = (isset($meta['hidden']) ? 'yes' : 'no');
			
			// Set navigation group, if not already set 
			if(!isset($meta['navigation_group'])) {
				$meta['navigation_group'] = (isset($this->_navigation[0]['name']) ? $this->_navigation[0]['name'] : __('Content'));
			}

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));

			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$namediv = new XMLElement('div', NULL);

			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('meta[name]', General::sanitize($meta['name'])));

			if(isset($this->_errors['name'])) $namediv->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['name']));
			else $namediv->appendChild($label);

			$label = Widget::Label();
			$input = Widget::Input('meta[hidden]', 'yes', 'checkbox', ($meta['hidden'] == 'yes' ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Hide this section from the back-end menu', array($input->generate(false))));
			$namediv->appendChild($label);
			$div->appendChild($namediv);

			$navgroupdiv = new XMLElement('div', NULL);
			$sectionManager = new SectionManager($this->_Parent);
			$sections = $sectionManager->fetch(NULL, 'ASC', 'sortorder');
			$label = Widget::Label(__('Navigation Group') . ' <i>' . __('Created if does not exist') . '</i>');
			$label->appendChild(Widget::Input('meta[navigation_group]', $meta['navigation_group']));

			if(isset($this->_errors['navigation_group'])) $navgroupdiv->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['navigation_group']));
			else $navgroupdiv->appendChild($label);

			if(is_array($sections) && !empty($sections)){
				$ul = new XMLElement('ul', NULL, array('class' => 'tags singular'));
				$groups = array();
				foreach($sections as $s){
					if(in_array($s->get('navigation_group'), $groups)) continue;
					$ul->appendChild(new XMLElement('li', $s->get('navigation_group')));
					$groups[] = $s->get('navigation_group');
				}

				$navgroupdiv->appendChild($ul);
			}

			$div->appendChild($navgroupdiv);

			$fieldset->appendChild($div);

			$this->Form->appendChild($fieldset);

			/**
			 * Allows extensions to add elements to the header of the Section Editor
			 * form. Usually for section settings, this delegate is passed the current
			 * `$meta` array and the `$this->_errors` array.
			 *
			 * @delegate AddSectionElements
			 * @since Symphony 2.2
			 * @param string $context
			 * '/blueprints/sections/'
			 * @param XMLElement $form
			 *  An XMLElement of the current `$this->Form`, just after the Section
			 *  settings have been appended, but before the Fields duplicator
			 * @param array $meta
			 *  The current $_POST['meta'] array
			 * @param array $errors
			 *  The current errors array
			 */
			Symphony::ExtensionManager()->notifyMembers('AddSectionElements', '/blueprints/sections/', array(
				'form' => &$this->Form,
				'meta' => &$meta,
				'errors' => &$this->_errors
			));

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Fields')));

			$div = new XMLElement('div');
			$p = new XMLElement('p', __('Fields'));
			$p->setAttribute('class', 'label');
			$div->appendChild($p);

			$ol = new XMLElement('ol');
			$ol->setAttribute('id', 'fields-duplicator');

			if(!$showEmptyTemplate){
				foreach($fields as $position => $data){
					if($input = $fieldManager->create($data['type'])){
						$input->setArray($data);

						$wrapper = new XMLElement('li');

						$input->set('sortorder', $position);
						$input->displaySettingsPanel($wrapper, (isset($this->_errors[$position]) ? $this->_errors[$position] : NULL));
						$ol->appendChild($wrapper);

					}
				}
			}

			foreach ($fieldManager->fetchTypes() as $type) {
				if ($type = $fieldManager->create($type)) {
					$types[] = $type;
				}
			}

			uasort($types, create_function('$a, $b', 'return strnatcasecmp($a->_name, $b->_name);'));

			foreach ($types as $type) {
				$defaults = array();

				$type->findDefaults($defaults);
				$type->setArray($defaults);

				$wrapper = new XMLElement('li');
				$wrapper->setAttribute('class', 'template field-' . $type->handle() . ($type->mustBeUnique() ? ' unique' : NULL));
				$wrapper->setAttribute('data-type', $type->handle());

				$type->set('sortorder', '-1');
				$type->displaySettingsPanel($wrapper);

				$ol->appendChild($wrapper);
			}

			$div->appendChild($ol);
			$fieldset->appendChild($div);

			$this->Form->appendChild($fieldset);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', __('Create Section'), 'submit', array('accesskey' => 's')));

			$this->Form->appendChild($div);

		}

		public function __viewEdit(){

			$section_id = $this->_context[1];

			$sectionManager = new SectionManager($this->_Parent);

			if(!$section = $sectionManager->fetch($section_id)) {
				Administration::instance()->customError(__('Unknown Section'), __('The Section you are looking for could not be found.'));
			}
			$meta = $section->get();

			$fieldManager = new FieldManager($this->_Parent);
			$types = array();

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));
			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);

			if(isset($this->_context[2])){
				switch($this->_context[2]){

					case 'saved':
						$this->pageAlert(
							__(
								'Section updated at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Sections</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									SYMPHONY_URL . '/blueprints/sections/new/',
									SYMPHONY_URL . '/blueprints/sections/'
								)
							),
							Alert::SUCCESS);
						break;

					case 'created':
						$this->pageAlert(
							__(
								'Section created at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Sections</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									SYMPHONY_URL . '/blueprints/sections/new/',
									SYMPHONY_URL . '/blueprints/sections/'
								)
							),
							Alert::SUCCESS);
						break;

				}
			}

			if(isset($_POST['fields'])){
				$fields = array();

				if(is_array($_POST['fields']) && !empty($_POST['fields'])){
					foreach($_POST['fields'] as $position => $data){
						if($fields[$position] = $fieldManager->create($data['type'])){
							$fields[$position]->setArray($data);
							$fields[$position]->set('sortorder', $position);
						}
					}
				}
			}

			else $fields = $fieldManager->fetch(NULL, $section_id);

			$meta['entry_order'] = (isset($meta['entry_order']) ? $meta['entry_order'] : 'date');

			if(isset($_POST['meta'])){
				$meta = $_POST['meta'];
				$meta['hidden'] = (isset($meta['hidden']) ? 'yes' : 'no');

				if($meta['name'] == '') $meta['name'] = $section->get('name');
			}

			$this->setPageType('form');
			$this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array(__('Symphony'), __('Sections'), $meta['name'])));
			$this->appendSubheading($meta['name']);

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));

			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$namediv = new XMLElement('div', NULL);

			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('meta[name]', General::sanitize($meta['name'])));

			if(isset($this->_errors['name'])) $namediv->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['name']));
			else $namediv->appendChild($label);

			$label = Widget::Label();
			$input = Widget::Input('meta[hidden]', 'yes', 'checkbox', ($meta['hidden'] == 'yes' ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Hide this section from the back-end menu', array($input->generate(false))));
			$namediv->appendChild($label);
			$div->appendChild($namediv);

			$navgroupdiv = new XMLElement('div', NULL);
			$sectionManager = new SectionManager($this->_Parent);
			$sections = $sectionManager->fetch(NULL, 'ASC', 'sortorder');
			$label = Widget::Label(__('Navigation Group') . ' <i>' . __('Choose only one. Created if does not exist') . '</i>');
			$label->appendChild(Widget::Input('meta[navigation_group]', $meta['navigation_group']));

			if(isset($this->_errors['navigation_group'])) $navgroupdiv->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['navigation_group']));
			else $navgroupdiv->appendChild($label);

			if(is_array($sections) && !empty($sections)){
				$ul = new XMLElement('ul', NULL, array('class' => 'tags singular'));
				$groups = array();
				foreach($sections as $s){
					if(in_array($s->get('navigation_group'), $groups)) continue;
					$ul->appendChild(new XMLElement('li', $s->get('navigation_group')));
					$groups[] = $s->get('navigation_group');
				}

				$navgroupdiv->appendChild($ul);
			}

			$div->appendChild($navgroupdiv);

			$fieldset->appendChild($div);

			$this->Form->appendChild($fieldset);

			/**
			 * Allows extensions to add elements to the header of the Section Editor
			 * form. Usually for section settings, this delegate is passed the current
			 * `$meta` array and the `$this->_errors` array.
			 *
			 * @delegate AddSectionElements
			 * @since Symphony 2.2
			 * @param string $context
			 * '/blueprints/sections/'
			 * @param XMLElement $form
			 *  An XMLElement of the current `$this->Form`, just after the Section
			 *  settings have been appended, but before the Fields duplicator
			 * @param array $meta
			 *  The current $_POST['meta'] array
			 * @param array $errors
			 *  The current errors array
			 */
			Symphony::ExtensionManager()->notifyMembers('AddSectionElements', '/blueprints/sections/', array(
				'form' => &$this->Form,
				'meta' => &$meta,
				'errors' => &$this->_errors
			));

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Fields')));

			$div = new XMLElement('div');
			$p = new XMLElement('p', __('Fields'));
			$p->setAttribute('class', 'label');
			$div->appendChild($p);

			$ol = new XMLElement('ol');
			$ol->setAttribute('id', 'fields-duplicator');

			if(is_array($fields) && !empty($fields)){
				foreach($fields as $position => $field){

					$wrapper = new XMLElement('li', NULL, array('class' => 'field-' . $field->handle() . ($field->mustBeUnique() ? ' unique' : NULL)));
					$wrapper->setAttribute('data-type', $field->handle());

					$field->set('sortorder', $position);
					$field->displaySettingsPanel($wrapper, (isset($this->_errors[$position]) ? $this->_errors[$position] : NULL));
					$ol->appendChild($wrapper);

				}
			}

			foreach ($fieldManager->fetchTypes() as $type) {
				if ($type = $fieldManager->create($type)) {
					array_push($types, $type);
				}
			}

			uasort($types, create_function('$a, $b', 'return strnatcasecmp($a->_name, $b->_name);'));

			foreach ($types as $type) {
				$defaults = array();

				$type->findDefaults($defaults);
				$type->setArray($defaults);

				$wrapper = new XMLElement('li');

				$wrapper->setAttribute('class', 'template field-' . $type->handle() . ($type->mustBeUnique() ? ' unique' : NULL));
				$wrapper->setAttribute('data-type', $type->handle());

				$type->set('sortorder', '-1');
				$type->displaySettingsPanel($wrapper);

				$ol->appendChild($wrapper);
			}

			$div->appendChild($ol);
			$fieldset->appendChild($div);

			$this->Form->appendChild($fieldset);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', array('accesskey' => 's')));

			$button = new XMLElement('button', __('Delete'));
			$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this section'), 'type' => 'submit', 'accesskey' => 'd', 'data-message' => __('Are you sure you want to delete this section?')));
			$div->appendChild($button);

			$this->Form->appendChild($div);
		}

		public function __actionIndex(){

			$checked = (is_array($_POST['items'])) ? array_keys($_POST['items']) : null;

			if(is_array($checked) && !empty($checked)){

				if($_POST['with-selected'] == 'delete') {
					/**
					 * Just prior to calling the Section Manager's delete function
					 *
					 * @delegate SectionPreDelete
					 * @since Symphony 2.2
					 * @param string $context
					 * '/blueprints/sections/'
					 * @param array $section_ids
					 *  An array of Section ID's passed by reference
					 */
					Symphony::ExtensionManager()->notifyMembers('SectionPreDelete', '/blueprints/sections/', array('section_ids' => &$checked));

					$sectionManager = new SectionManager($this->_Parent);
					foreach($checked as $section_id) $sectionManager->delete($section_id);

					redirect(SYMPHONY_URL . '/blueprints/sections/');
				}

				else if($_POST['with-selected'] == 'delete-entries') {
					$entryManager = new EntryManager($this->_Parent);
					foreach($checked as $section_id) {
						$entries = $entryManager->fetch(NULL, $section_id, NULL, NULL, NULL, NULL, false, false);
						$entry_ids = array();
						foreach($entries as $entry) {
							$entry_ids[] = $entry['id'];
						}

						/**
						 * Prior to deletion of entries.
						 *
						 * @delegate Delete
						 * @param string $context
						 * '/publish/'
						 * @param array $entry_id
						 *  An array of Entry ID's that are about to be deleted, passed by reference
						 */
						Symphony::ExtensionManager()->notifyMembers('Delete', '/publish/', array('entry_id' => &$entry_ids));

						$entryManager->delete($entry_ids);
					}

					redirect(SYMPHONY_URL . '/blueprints/sections/');
				}

				else if(preg_match('/^set-navigation-group-/', $_POST['with-selected'])) {
					$sectionManager = new SectionManager($this->_Parent);

					$navigation_group = preg_replace('/^set-navigation-group-/', null, $_POST['with-selected']);

					foreach($checked as $section_id) {
						$sectionManager->edit($section_id, array('navigation_group' => urldecode($navigation_group)));
					}

					redirect(SYMPHONY_URL . '/blueprints/sections/');
				}
			}

		}

		public function __actionNew(){
			if(@array_key_exists('save', $_POST['action']) || @array_key_exists('done', $_POST['action'])) {

				$canProceed = true;
				$edit = ($this->_context[0] == "edit");
				$this->_errors = array();

				$fields = $_POST['fields'];
				$meta = $_POST['meta'];

				$fieldManager = new FieldManager($this->_Parent);

				if($edit) {
					$section_id = $this->_context[1];
					$sectionManager = new SectionManager($this->_Parent);
					$existing_section = $sectionManager->fetch($section_id);
				}

				## Check to ensure all the required section fields are filled
				if(!isset($meta['name']) || strlen(trim($meta['name'])) == 0){
					$required = array('Name');
					$this->_errors['name'] = __('This is a required field.');
					$canProceed = false;
				}

				## Check for duplicate section handle
				elseif($edit) {
					if(
						$meta['name'] != $existing_section->get('name')
						&& Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `handle` = '" . Lang::createHandle($meta['name']) . "' AND `id` != {$section_id} LIMIT 1")
					){
						$this->_errors['name'] = __('A Section with the name <code>%s</code> name already exists', array($meta['name']));
						$canProceed = false;
					}
				}
				elseif(Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `handle` = '" . Lang::createHandle($meta['name']). "' LIMIT 1")){
					$this->_errors['name'] = __('A Section with the name <code>%s</code> name already exists', array($meta['name']));
					$canProceed = false;
				}

				## Check to ensure all the required section fields are filled
				if(!isset($meta['navigation_group']) || strlen(trim($meta['navigation_group'])) == 0){
					$required = array('Navigation Group');
					$this->_errors['navigation_group'] = __('This is a required field.');
					$canProceed = false;
				}

				## Basic custom field checking
				if(is_array($fields) && !empty($fields)){

					## Check for duplicate CF names
					if($canProceed) {
						$name_list = array();

						foreach($fields as $position => $data){
							if(trim($data['element_name']) == '')
								$data['element_name'] = $fields[$position]['element_name'] = Lang::createHandle($data['label'], NULL, '-', false, true, array('@^[\d-]+@i' => ''));

							if(trim($data['element_name']) != '' && in_array($data['element_name'], $name_list)){
								$this->_errors[$position] = array('label' => __('Two custom fields have the same element name. All element names must be unique.'));
								$canProceed = false;
								break;
							}
							$name_list[] = $data['element_name'];
						}
					}

					if($canProceed) {
						$unique = array();

						foreach($fields as $position => $data){
							$required = NULL;

							$field = $fieldManager->create($data['type']);
							$field->setFromPOST($data);

							if($field->mustBeUnique() && !in_array($field->get('type'), $unique)) $unique[] = $field->get('type');
							elseif($field->mustBeUnique() && in_array($field->get('type'), $unique)){
								## Warning. cannot have 2 of this field!
								$canProceed = false;
								$this->_errors[$position] = array('label' => __('There is already a field of type <code>%s</code>. There can only be one per section.', array($field->handle())));
							}

							$errors = array();

							if(Field::__OK__ != $field->checkFields($errors, false, false) && !empty($errors)){
								$this->_errors[$position] = $errors;
								$canProceed = false;
								break;
							}
						}
					}
				}

				if($canProceed){
					$meta['handle'] = Lang::createHandle($meta['name']);

					// If we are creating a new Section
					if(!$edit) {
						$next = Symphony::Database()->fetchVar('next', 0, 'SELECT MAX(`sortorder`) + 1 AS `next` FROM tbl_sections LIMIT 1');

						$meta['sortorder'] = ($next ? $next : '1');

						$sectionManager = new SectionManager($this->_Parent);

						/**
						 * Just prior to saving the Section settings. Use with caution as
						 * there is no additional processing to ensure that Field's or Section's
						 * are unique.
						 *
						 * @delegate SectionPreCreate
						 * @since Symphony 2.2
						 * @param string $context
						 * '/blueprints/sections/'
						 * @param array $meta
						 *  The section's settings, passed by reference
						 * @param array $fields
						 *  An associative array of the fields that will be saved to this
						 *  section with the key being the position in the Section Editor
						 *  and the value being a Field object, passed by reference
						 */
						Symphony::ExtensionManager()->notifyMembers('SectionPreCreate', '/blueprints/sections/', array('meta' => &$meta, 'fields' => &$fields));

						if(!$section_id = $sectionManager->add($meta)){
							$this->pageAlert(__('An unknown database occurred while attempting to create the section.'), Alert::ERROR);
						}
					}

					// We are editing a Section
					else {
						$meta['hidden'] = (isset($meta['hidden']) ? 'yes' : 'no');

						/**
						 * Just prior to updating the Section settings. Use with caution as
						 * there is no additional processing to ensure that Field's or Section's
						 * are unique.
						 *
						 * @delegate SectionPreEdit
						 * @since Symphony 2.2
						 * @param string $context
						 * '/blueprints/sections/'
						 * @param integer $section_id
						 *  The Section ID that is about to be edited.
						 * @param array $meta
						 *  The section's settings, passed by reference
						 * @param array $fields
						 *  An associative array of the fields that will be saved to this
						 *  section with the key being the position in the Section Editor
						 *  and the value being a Field object, passed by reference
						 */
						Symphony::ExtensionManager()->notifyMembers('SectionPreEdit', '/blueprints/sections/', array('section_id' => $section_id, 'meta' => &$meta, 'fields' => &$fields));

						if(!$sectionManager->edit($section_id, $meta)){
							$canProceed = false;
							$this->pageAlert(__('An unknown database occurred while attempting to create the section.'), Alert::ERROR);
						}
					}

					if($section_id && $canProceed) {
						if($edit) {
							## Delete missing CF's
							$id_list = array();
							if(is_array($fields) && !empty($fields)){
								foreach($fields as $position => $data){
									if(isset($data['id'])) $id_list[] = $data['id'];
								}
							}

							$missing_cfs = Symphony::Database()->fetchCol('id', "SELECT `id` FROM `tbl_fields` WHERE `parent_section` = '$section_id' AND `id` NOT IN ('".@implode("', '", $id_list)."')");

							if(is_array($missing_cfs) && !empty($missing_cfs)){
								foreach($missing_cfs as $id){
									$fieldManager->delete($id);
								}
							}
						}

						## Save each custom field
						if(is_array($fields) && !empty($fields)){
							foreach($fields as $position => $data){
								$field = $fieldManager->create($data['type']);
								$field->setFromPOST($data);
								$field->set('sortorder', (string)$position);
								$field->set('parent_section', $section_id);

								$newField = !(boolean)$field->get('id');

								$field->commit();

								$field_id = $field->get('id');

								if($field_id){

									if($newField) {
										/**
										 * After creation of a Field.
										 *
										 * @delegate FieldPostCreate
										 * @param string $context
										 * '/blueprints/sections/'
										 * @param Field $field
										 *  The Field object, passed by reference
										 * @param array $data
										 *  The settings for ths `$field`, passed by reference
										 */
										Symphony::ExtensionManager()->notifyMembers('FieldPostCreate', '/blueprints/sections/', array('field' => &$field, 'data' => &$data));
									}
									else {
										/**
										 * After editing of a Field.
										 *
										 * @delegate FieldPostEdit
										 * @param string $context
										 * '/blueprints/sections/'
										 * @param Field $field
										 *  The Field object, passed by reference
										 * @param array $data
										 *  The settings for ths `$field`, passed by reference
										 */
										Symphony::ExtensionManager()->notifyMembers('FieldPostEdit', '/blueprints/sections/', array('field' => &$field, 'data' => &$data));
									}
								}
							}
						}

						if(!$edit) {
							/**
							 * After the Section has been created, and all the Field's have been
							 * created for this section, but just before the redirect
							 *
							 * @delegate SectionPostCreate
							 * @since Symphony 2.2
							 * @param string $context
							 * '/blueprints/sections/'
							 * @param integer $section_id
							 *  The newly created Section ID.
							 */
							Symphony::ExtensionManager()->notifyMembers('SectionPostCreate', '/blueprints/sections/', array('section_id' => $section_id));

							redirect(SYMPHONY_URL . "/blueprints/sections/edit/$section_id/created/");
						}
						else {
							/**
							 * After the Section has been updated, and all the Field's have been
							 * updated for this section, but just before the redirect
							 *
							 * @delegate SectionPostEdit
							 * @since Symphony 2.2
							 * @param string $context
							 * '/blueprints/sections/'
							 * @param integer $section_id
							 *  The edited Section ID.
							 */
							Symphony::ExtensionManager()->notifyMembers('SectionPostEdit', '/blueprints/sections/', array('section_id' => $section_id));

							redirect(SYMPHONY_URL . "/blueprints/sections/edit/$section_id/saved/");

						}
					}
				}
			}

			if(@array_key_exists("delete", $_POST['action'])){
				$section_id = array($this->_context[1]);
				$sectionManager = new SectionManager($this->_Parent);

				/**
				 * Just prior to calling the Section Manager's delete function
				 *
				 * @delegate SectionPreDelete
				 * @since Symphony 2.2
				 * @param string $context
				 * '/blueprints/sections/'
				 * @param array $section_ids
				 *  An array of Section ID's passed by reference
				 */
				Symphony::ExtensionManager()->notifyMembers('SectionPreDelete', '/blueprints/sections/', array('section_ids' => &$section_id));

				foreach($section_id as $section) $sectionManager->delete($section);
				redirect(SYMPHONY_URL . '/blueprints/sections/');
			}
		}

		public function __actionEdit(){
			return $this->__actionNew();
		}
	}
