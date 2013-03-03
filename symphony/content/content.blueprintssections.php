<?php
	/**
	 * @package content
	 */

	/**
	 * This page controls the creation and maintenance of Symphony
	 * Sections through the Section Index and Section Editor.
	 */
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.fieldmanager.php');
	require_once(TOOLKIT . '/class.entrymanager.php');

	Class contentBlueprintsSections extends AdministrationPage{

		public $_errors = array();

		public function __viewIndex(){
			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Sections'), __('Symphony'))));
			$this->appendSubheading(__('Sections'), Widget::Anchor(__('Create New'), Administration::instance()->getCurrentPageURL().'new/', __('Create a section'), 'create button', NULL, array('accesskey' => 'c')));

			$sections = SectionManager::fetch(NULL, 'ASC', 'sortorder');

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

					$entry_count = EntryManager::fetchCount($s->get('id'));

					// Setup each cell
					$td1 = Widget::TableData(Widget::Anchor($s->get('name'), Administration::instance()->getCurrentPageURL() . 'edit/' . $s->get('id') .'/', NULL, 'content'));
					$td2 = Widget::TableData(Widget::Anchor("$entry_count", SYMPHONY_URL . '/publish/' . $s->get('handle') . '/'));
					$td3 = Widget::TableData($s->get('navigation_group'));

					$td3->appendChild(Widget::Input('items['.$s->get('id').']', 'on', 'checkbox'));

					// Add a row to the body array, assigning each cell to the row
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

			/**
			 * Allows an extension to modify the existing options for this page's
			 * With Selected menu. If the `$options` parameter is an empty array,
			 * the 'With Selected' menu will not be rendered.
			 *
			 * @delegate AddCustomActions
			 * @since Symphony 2.3.2
			 * @param string $context
			 * '/blueprints/sections/'
			 * @param array $options
			 *  An array of arrays, where each child array represents an option
			 *  in the With Selected menu. Options should follow the same format
			 *  expected by `Widget::__SelectBuildOption`. Passed by reference.
			 */
			Symphony::ExtensionManager()->notifyMembers('AddCustomActions', '/blueprints/sections/', array(
				'options' => &$options
			));

			if(!empty($options)) {
				$tableActions->appendChild(Widget::Apply($options));
				$this->Form->appendChild($tableActions);
			}
		}

		public function __viewNew(){
			$this->setPageType('form');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Sections'), __('Symphony'))));
			$this->appendSubheading(__('Untitled'));
			$this->insertBreadcrumbs(array(
				Widget::Anchor(__('Sections'), SYMPHONY_URL . '/blueprints/sections/'),
			));

			$types = array();

			$fields = (isset($_POST['fields']) && is_array($_POST['fields'])) ? $_POST['fields'] : array();
			$meta = (isset($_POST['meta']) && is_array($_POST['meta'])) ? $_POST['meta'] : array('name'=>null);

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

			if($formHasErrors) {
				$this->pageAlert(
					__('An error occurred while processing this form. See below for details.')
					, Alert::ERROR
				);
			}

			$showEmptyTemplate = (is_array($fields) && !empty($fields) ? false : true);

			if(!$showEmptyTemplate) ksort($fields);

			$meta['hidden'] = (isset($meta['hidden']) ? 'yes' : 'no');

			// Set navigation group, if not already set
			if(!isset($meta['navigation_group'])) {
				$meta['navigation_group'] = (isset($this->_navigation[0]['name']) ? $this->_navigation[0]['name'] : __('Content'));
			}

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));

			$div = new XMLElement('div', NULL, array('class' => 'two columns'));
			$namediv = new XMLElement('div', NULL, array('class' => 'column'));

			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('meta[name]', (isset($meta['name']) ? General::sanitize($meta['name']) : null)));

			if(isset($this->_errors['name'])) $namediv->appendChild(Widget::Error($label, $this->_errors['name']));
			else $namediv->appendChild($label);

			$label = Widget::Label();
			$input = Widget::Input('meta[hidden]', 'yes', 'checkbox', ($meta['hidden'] == 'yes' ? array('checked' => 'checked') : null));
			$label->setValue(__('%s Hide this section from the back-end menu', array($input->generate(false))));
			$namediv->appendChild($label);
			$div->appendChild($namediv);

			$navgroupdiv = new XMLElement('div', NULL, array('class' => 'column'));
			$sections = SectionManager::fetch(NULL, 'ASC', 'sortorder');
			$label = Widget::Label(__('Navigation Group'));
			$label->appendChild(Widget::Input('meta[navigation_group]', $meta['navigation_group']));

			if(isset($this->_errors['navigation_group'])) $navgroupdiv->appendChild(Widget::Error($label, $this->_errors['navigation_group']));
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

			$div = new XMLElement('div', null, array('class' => 'frame'));

			$ol = new XMLElement('ol');
			$ol->setAttribute('id', 'fields-duplicator');
			$ol->setAttribute('data-add', __('Add field'));
			$ol->setAttribute('data-remove', __('Remove field'));

			if(!$showEmptyTemplate){
				foreach($fields as $position => $data){
					if($input = FieldManager::create($data['type'])){
						$input->setArray($data);

						$wrapper = new XMLElement('li');

						$input->set('sortorder', $position);
						$input->displaySettingsPanel($wrapper, (isset($this->_errors[$position]) ? $this->_errors[$position] : NULL));
						$ol->appendChild($wrapper);

					}
				}
			}

			foreach (FieldManager::listAll() as $type) {
				if ($type = FieldManager::create($type)) {
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

			if(!$section = SectionManager::fetch($section_id)) {
				Administration::instance()->throwCustomError(
					__('The Section, %s, could not be found.', array($section_id)),
					__('Unknown Section'),
					Page::HTTP_STATUS_NOT_FOUND
				);
			}
			$meta = $section->get();
			$section_id = $meta['id'];
			$types = array();

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));
			if($formHasErrors) {
				$this->pageAlert(
					__('An error occurred while processing this form. See below for details.')
					, Alert::ERROR
				);
			}
			// These alerts are only valid if the form doesn't have errors
			else if(isset($this->_context[2])) {
				switch($this->_context[2]) {
					case 'saved':
						$this->pageAlert(
							__('Section updated at %s.', array(DateTimeObj::getTimeAgo()))
							. ' <a href="' . SYMPHONY_URL . '/blueprints/sections/new/" accesskey="c">'
							. __('Create another?')
							. '</a> <a href="' . SYMPHONY_URL . '/blueprints/sections/" accesskey="a">'
							. __('View all Sections')
							. '</a>'
							, Alert::SUCCESS);
						break;

					case 'created':
						$this->pageAlert(
							__('Section created at %s.', array(DateTimeObj::getTimeAgo()))
							. ' <a href="' . SYMPHONY_URL . '/blueprints/sections/new/" accesskey="c">'
							. __('Create another?')
							. '</a> <a href="' . SYMPHONY_URL . '/blueprints/sections/" accesskey="a">'
							. __('View all Sections')
							. '</a>'
							, Alert::SUCCESS);
						break;
				}
			}

			if(isset($_POST['fields'])){
				$fields = array();

				if(is_array($_POST['fields']) && !empty($_POST['fields'])){
					foreach($_POST['fields'] as $position => $data){
						if($fields[$position] = FieldManager::create($data['type'])){
							$fields[$position]->setArray($data);
							$fields[$position]->set('sortorder', $position);
						}
					}
				}
			}

			else {
				$fields = FieldManager::fetch(NULL, $section_id);
				$fields = array_values($fields);
			}

			if(isset($_POST['meta'])){
				$meta = $_POST['meta'];
				$meta['hidden'] = (isset($meta['hidden']) ? 'yes' : 'no');

				if($meta['name'] == '') $meta['name'] = $section->get('name');
			}

			$this->setPageType('form');
			$this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array($meta['name'], __('Sections'), __('Symphony'))));
			$this->appendSubheading($meta['name'],
				Widget::Anchor(__('View Entries'), SYMPHONY_URL . '/publish/' . $section->get('handle'), __('View Section Entries'), 'button')
			);
			$this->insertBreadcrumbs(array(
				Widget::Anchor(__('Sections'), SYMPHONY_URL . '/blueprints/sections/'),
			));

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));

			$div = new XMLElement('div', NULL, array('class' => 'two columns'));
			$namediv = new XMLElement('div', NULL, array('class' => 'column'));

			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('meta[name]', General::sanitize($meta['name'])));

			if(isset($this->_errors['name'])) $namediv->appendChild(Widget::Error($label, $this->_errors['name']));
			else $namediv->appendChild($label);

			$label = Widget::Label();
			$input = Widget::Input('meta[hidden]', 'yes', 'checkbox', ($meta['hidden'] == 'yes' ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Hide this section from the back-end menu', array($input->generate(false))));
			$namediv->appendChild($label);
			$div->appendChild($namediv);

			$navgroupdiv = new XMLElement('div', NULL, array('class' => 'column'));
			$sections = SectionManager::fetch(NULL, 'ASC', 'sortorder');
			$label = Widget::Label(__('Navigation Group'));
			$label->appendChild(Widget::Input('meta[navigation_group]', $meta['navigation_group']));

			if(isset($this->_errors['navigation_group'])) $navgroupdiv->appendChild(Widget::Error($label, $this->_errors['navigation_group']));
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

			$fieldset = new XMLElement('fieldset', null, array('id' => 'fields', 'class' => 'settings'));
			$fieldset->appendChild(new XMLElement('legend', __('Fields')));

			$div = new XMLElement('div', null, array('class' => 'frame'));

			$ol = new XMLElement('ol');
			$ol->setAttribute('id', 'fields-duplicator');
			$ol->setAttribute('data-add', __('Add field'));
			$ol->setAttribute('data-remove', __('Remove field'));

			if(is_array($fields) && !empty($fields)){
				foreach($fields as $position => $field){

					$wrapper = new XMLElement('li', NULL, array('class' => 'field-' . $field->handle() . ($field->mustBeUnique() ? ' unique' : NULL)));
					$wrapper->setAttribute('data-type', $field->handle());

					$field->set('sortorder', $position);
					$field->displaySettingsPanel($wrapper, (isset($this->_errors[$position]) ? $this->_errors[$position] : NULL));
					$ol->appendChild($wrapper);

				}
			}

			foreach (FieldManager::listAll() as $type) {
				if ($type = FieldManager::create($type)) {
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

		public function __actionIndex() {
			$checked = (is_array($_POST['items'])) ? array_keys($_POST['items']) : null;

			if(is_array($checked) && !empty($checked)){
				/**
				 * Extensions can listen for any custom actions that were added
				 * through `AddCustomPreferenceFieldsets` or `AddCustomActions`
				 * delegates.
				 *
				 * @delegate CustomActions
				 * @since Symphony 2.3.2
				 * @param string $context
				 *  '/blueprints/sections/'
				 * @param array $checked
				 *  An array of the selected rows. The value is usually the ID of the
				 *  the associated object. 
				 */
				Symphony::ExtensionManager()->notifyMembers('CustomActions', '/blueprints/sections/', array(
					'checked' => $checked
				));

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

					foreach($checked as $section_id) SectionManager::delete($section_id);

					redirect(SYMPHONY_URL . '/blueprints/sections/');
				}

				else if($_POST['with-selected'] == 'delete-entries') {
					foreach($checked as $section_id) {
						$entries = EntryManager::fetch(NULL, $section_id, NULL, NULL, NULL, NULL, false, false, null, false);
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

						EntryManager::delete($entry_ids, $section_id);
					}

					redirect(SYMPHONY_URL . '/blueprints/sections/');
				}

				else if(preg_match('/^set-navigation-group-/', $_POST['with-selected'])) {
					$navigation_group = preg_replace('/^set-navigation-group-/', null, $_POST['with-selected']);

					foreach($checked as $section_id) {
						SectionManager::edit($section_id, array('navigation_group' => urldecode($navigation_group)));
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

				$fields = isset($_POST['fields']) ? $_POST['fields'] : array();
				$meta = $_POST['meta'];

				if($edit) {
					$section_id = $this->_context[1];
					$existing_section = SectionManager::fetch($section_id);
				}

				// Check to ensure all the required section fields are filled
				if(!isset($meta['name']) || strlen(trim($meta['name'])) == 0){
					$this->_errors['name'] = __('This is a required field.');
					$canProceed = false;
				}

				// Check for duplicate section handle
				elseif($edit) {
					$s = SectionManager::fetchIDFromHandle(Lang::createHandle($meta['name']));
					if(
						$meta['name'] !== $existing_section->get('name')
						&& !is_null($s) && $s !== $section_id
					) {
						$this->_errors['name'] = __('A Section with the name %s already exists', array('<code>' . $meta['name'] . '</code>'));
						$canProceed = false;
					}
				}
				elseif(!is_null(SectionManager::fetchIDFromHandle(Lang::createHandle($meta['name'])))) {
					$this->_errors['name'] = __('A Section with the name %s already exists', array('<code>' . $meta['name'] . '</code>'));
					$canProceed = false;
				}

				// Check to ensure all the required section fields are filled
				if(!isset($meta['navigation_group']) || strlen(trim($meta['navigation_group'])) == 0){
					$this->_errors['navigation_group'] = __('This is a required field.');
					$canProceed = false;
				}

				// Basic custom field checking
				if(is_array($fields) && !empty($fields)){
					// Check for duplicate CF names
					if($canProceed) {
						$name_list = array();

						foreach($fields as $position => $data){
							if(trim($data['element_name']) == '') {
								$data['element_name'] = $fields[$position]['element_name'] = $_POST['fields'][$position]['element_name'] = Lang::createHandle($data['label'], 255, '-', false, true, array('@^[\d-]+@i' => ''));
							}

							if(trim($data['element_name']) != '' && in_array($data['element_name'], $name_list)){
								$this->_errors[$position] = array('element_name' => __('A field with this handle already exists. All handle must be unique.'));
								$canProceed = false;
								break;
							}

							$name_list[] = $data['element_name'];
						}
					}

					if($canProceed) {
						$unique = array();

						foreach($fields as $position => $data){
							$field = FieldManager::create($data['type']);
							$field->setFromPOST($data);

							if(isset($existing_section)) {
								$field->set('parent_section', $existing_section->get('id'));
							}

							if($field->mustBeUnique() && !in_array($field->get('type'), $unique)) $unique[] = $field->get('type');
							elseif($field->mustBeUnique() && in_array($field->get('type'), $unique)){
								// Warning. cannot have 2 of this field!
								$canProceed = false;
								$this->_errors[$position] = array('label' => __('There is already a field of type %s. There can only be one per section.', array('<code>' . $field->handle() . '</code>')));
							}

							$errors = array();

							if(Field::__OK__ != $field->checkFields($errors, false) && !empty($errors)){
								$this->_errors[$position] = $errors;
								$canProceed = false;
							}
						}
					}
				}

				if($canProceed){
					$meta['handle'] = Lang::createHandle($meta['name']);

					// If we are creating a new Section
					if(!$edit) {

						$meta['sortorder'] = SectionManager::fetchNextSortOrder();

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

						if(!$section_id = SectionManager::add($meta)){
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

						if(!SectionManager::edit($section_id, $meta)){
							$canProceed = false;
							$this->pageAlert(__('An unknown database occurred while attempting to create the section.'), Alert::ERROR);
						}
					}

					if($section_id && $canProceed) {
						if($edit) {
							// Delete missing CF's
							$id_list = array();
							if(is_array($fields) && !empty($fields)){
								foreach($fields as $position => $data){
									if(isset($data['id'])) $id_list[] = $data['id'];
								}
							}

							$missing_cfs = Symphony::Database()->fetchCol('id', "SELECT `id` FROM `tbl_fields` WHERE `parent_section` = '$section_id' AND `id` NOT IN ('".@implode("', '", $id_list)."')");

							if(is_array($missing_cfs) && !empty($missing_cfs)){
								foreach($missing_cfs as $id){
									FieldManager::delete($id);
								}
							}
						}

						// Save each custom field
						if(is_array($fields) && !empty($fields)){
							foreach($fields as $position => $data){
								$field = FieldManager::create($data['type']);
								$field->setFromPOST($data);
								$field->set('sortorder', (string)$position);
								$field->set('parent_section', $section_id);

								$newField = !(boolean)$field->get('id');

								$field->commit();
								$field_id = $field->get('id');

								if($field_id) {
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

				foreach($section_id as $section) SectionManager::delete($section);
				redirect(SYMPHONY_URL . '/blueprints/sections/');
			}
		}

		public function __actionEdit(){
			return $this->__actionNew();
		}
	}
