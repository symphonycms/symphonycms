<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
 	require_once(TOOLKIT . '/class.sectionmanager.php');
 	require_once(TOOLKIT . '/class.fieldmanager.php');
	require_once(TOOLKIT . '/class.entrymanager.php');

	Class contentBlueprintsSections extends AdministrationPage{

		public $_errors;
		
		public function __construct(&$parent){
			parent::__construct($parent);
		}
		
		public function __viewIndex(){
			$this->setPageType('table');	
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Sections'))));
			$this->appendSubheading(__('Sections'), Widget::Anchor(__('Create New'), $this->_Parent->getCurrentPageURL().'new/', __('Create a section'), 'create button'));

		    $sectionManager = new SectionManager($this->_Parent);
		    $sections = $sectionManager->fetch(NULL, 'ASC', 'sortorder');

			$aTableHead = array(

				array(__('Name'), 'col'),
				array(__('Entries'), 'col'),
				array(__('Navigation Group'), 'col'),	

			);	

			$aTableBody = array();

			if(!is_array($sections) || empty($sections)){

				$aTableBody = array(
									Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
								);
			}

			else{
				
				$bOdd = true;

				foreach($sections as $s){
					
					$entry_count = intval(Symphony::Database()->fetchVar('count', 0, "SELECT count(*) AS `count` FROM `tbl_entries` WHERE `section_id` = '".$s->get('id')."' "));
					
					## Setup each cell
					$td1 = Widget::TableData(Widget::Anchor($s->get('name'), $this->_Parent->getCurrentPageURL() . 'edit/' . $s->get('id') .'/', NULL, 'content'));
					$td2 = Widget::TableData(Widget::Anchor("$entry_count", URL . '/symphony/publish/' . $s->get('handle') . '/'));
					$td3 = Widget::TableData($s->get('navigation_group'));
				
					$td3->appendChild(Widget::Input('items['.$s->get('id').']', 'on', 'checkbox'));

					## Add a row to the body array, assigning each cell to the row
					$aTableBody[] = Widget::TableRow(array($td1, $td2, $td3), ($bOdd ? 'odd' : NULL));
					
					$bOdd = !$bOdd;

				}
			}

			$table = Widget::Table(
								Widget::TableHead($aTableHead), 
								NULL, 
								Widget::TableBody($aTableBody),
								'orderable'
						);

			$this->Form->appendChild($table);
			
			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');
			
			$options = array(
				array(NULL, false, __('With Selected...')),
				array('delete', false, __('Delete'), 'confirm'),
				array('delete-entries', false, __('Delete Entries'), 'confirm')
			);

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
			
		    $fields = $_POST['fields'];
			$meta = $_POST['meta'];
			
			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));
			
			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
			
			@ksort($fields);

			$showEmptyTemplate = (is_array($fields) && !empty($fields) ? false : true);

			$meta['entry_order'] = (isset($meta['entry_order']) ? $meta['entry_order'] : 'date');
			$meta['subsection'] = (isset($meta['subsection']) ? 1 : 0);	
			$meta['hidden'] = (isset($meta['hidden']) ? 'yes' : 'no');	
			$meta['navigation_group'] = (isset($meta['navigation_group']) ? $meta['navigation_group'] : 'Content');

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));
			
			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$namediv = new XMLElement('div', NULL);
			
			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('meta[name]', $meta['name']));
			
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

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Fields')));
			
			$div = new XMLElement('div');
			$h3 = new XMLElement('h3', __('Fields'));
			$h3->setAttribute('class', 'label');
			$div->appendChild($h3);
			
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
					array_push($types, $type);
				}
			}
			
			uasort($types, create_function('$a, $b', 'return strnatcasecmp($a->_name, $b->_name);'));
			
			foreach ($types as $type) {		
				$defaults = array();
				
				$type->findDefaults($defaults);			
				$type->setArray($defaults);
				
				$wrapper = new XMLElement('li');
				$wrapper->setAttribute('class', 'template');
				
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

		    if(!$section = $sectionManager->fetch($section_id)) 
				$this->_Parent->customError(E_USER_ERROR, __('Unknown Section'), __('The Section you are looking for could not be found.'), false, true);

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
								'Section updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Sections</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									URL . '/symphony/blueprints/sections/new/', 
									URL . '/symphony/blueprints/sections/' 
								)
							), 
							Alert::SUCCESS);
						break;
						
					case 'created':
						$this->pageAlert(
							__(
								'Section created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Sections</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									URL . '/symphony/blueprints/sections/new/', 
									URL . '/symphony/blueprints/sections/' 
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

			$meta['subsection'] = ($meta['subsection'] == 'yes' ? 1 : 0);
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
			
			$label = Widget::Label('Name');
			$label->appendChild(Widget::Input('meta[name]', $meta['name']));
			
			if(isset($this->_errors['name'])) $namediv->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['name']));
			else $namediv->appendChild($label);
			
			$label = Widget::Label();
			$input = Widget::Input('meta[hidden]', 'yes', 'checkbox', ($meta['hidden'] == 'yes' ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Hide this section from the Publish menu', array($input->generate(false))));
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

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Fields')));
			
			$div = new XMLElement('div');
			$h3 = new XMLElement('h3', __('Fields'));
			$h3->setAttribute('class', 'label');
			$div->appendChild($h3);
			
			$ol = new XMLElement('ol');
			$ol->setAttribute('id', 'fields-duplicator');
			
			if(is_array($fields) && !empty($fields)){
				foreach($fields as $position => $field){

					$wrapper = new XMLElement('li');
					
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
				$wrapper->setAttribute('class', 'template');
				
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
			$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => __('Delete this section'), 'type' => 'submit'));
			$div->appendChild($button);

			$this->Form->appendChild($div);			
		}
		
		public function __actionIndex(){

			$checked = @array_keys($_POST['items']);

			if(is_array($checked) && !empty($checked)){
				switch($_POST['with-selected']) {

					case 'delete':
					
						$sectionManager = new SectionManager($this->_Parent);
						foreach($checked as $section_id) $sectionManager->delete($section_id);

						redirect(URL . '/symphony/blueprints/sections/');
						break;
						
					case 'delete-entries':

						$entryManager = new EntryManager($this->_Parent);
						foreach($checked as $section_id) {
							$entries = $entryManager->fetch(NULL, $section_id, NULL, NULL, NULL, NULL, false, false);
							$entry_ids = array();
							foreach($entries as $entry) {
								$entry_ids[] = $entry['id'];
							}

							###
							# Delegate: Delete
							# Description: Prior to deletion of entries. Array of Entries is provided.
							#              The array can be manipulated
							Administration::instance()->ExtensionManager->notifyMembers('Delete', '/publish/', array('entry_id' => &$entry_ids));

							$entryManager->delete($entry_ids);
						}

						redirect(URL . '/symphony/blueprints/sections/');
						break;
				}
			}
						
		}
		
		public function __actionNew(){		
			
			if(@array_key_exists('save', $_POST['action']) || @array_key_exists('done', $_POST['action'])) {

				$canProceed = true;

			    $fields = $_POST['fields'];
				$meta = $_POST['meta'];
				
				$this->_errors = array();
					
				## Check to ensure all the required section fields are filled
				if(!isset($meta['name']) || strlen(trim($meta['name'])) == 0){
					$required = array('Name');
					$this->_errors['name'] = __('This is a required field.');
					$canProceed = false;
				}

				## Check for duplicate section handle
				elseif(Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `name` = '" . Symphony::Database()->cleanValue($meta['name']) . "' LIMIT 1")){
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

					$name_list = array();

					foreach($fields as $position => $data){
						if(trim($data['element_name']) == '') 
							$data['element_name'] = $fields[$position]['element_name'] = Lang::createHandle($data['label'], NULL, '-', false, true, array('@^[\d-]+@i' => ''));

						if(trim($data['element_name']) != '' && in_array($data['element_name'], $name_list)){
							$this->_errors[$position] = array('element_name' => __('Two custom fields have the same element name. All element names must be unique.'));
							$canProceed = false;
							break;						
						}		
						$name_list[] = $data['element_name'];
					}	

					$fieldManager = new FieldManager($this->_Parent);

					$unique = array();

					foreach($fields as $position => $data){
						$required = NULL;

						$field = $fieldManager->create($data['type']);
						$field->setFromPOST($data);

						if($field->mustBeUnique() && !in_array($field->get('type'), $unique)) $unique[] = $field->get('type');
						elseif($field->mustBeUnique() && in_array($field->get('type'), $unique)){
							## Warning. cannot have 2 of this field!
							$canProceed = false;
							$this->_errors[$position] = array('label' => __('There is already a field of type <code>%s</code>. There can only be one per section.', array($field->name())));
						}

						$errors = array();

						if(Field::__OK__ != $field->checkFields($errors, false, false) && !empty($errors)){
							$this->_errors[$position] = $errors;
							$canProceed = false;
							break;					
						}
					}
				}


				if($canProceed){

			        $query = 'SELECT MAX(`sortorder`) + 1 AS `next` FROM tbl_sections LIMIT 1';
			        $next = Symphony::Database()->fetchVar('next', 0, $query);

			        $meta['sortorder'] = ($next ? $next : '1');
					$meta['handle'] = Lang::createHandle($meta['name']);
					
				 	$sectionManager = new SectionManager($this->_Parent);

					if(!$section_id = $sectionManager->add($meta)){
						$this->pageAlert(__('An unknown database occurred while attempting to create the section.'), Alert::ERROR);
					}

					else{

						## Save each custom field
						if(is_array($fields) && !empty($fields)){
							foreach($fields as $position => $data){

								$field = $fieldManager->create($data['type']);
								$field->setFromPOST($data);
								$field->set('sortorder', $position);
								$field->set('parent_section', $section_id);

								$field->commit();	

								$field_id = $field->get('id');

						        if($field_id){

									###
									# Delegate: FieldPostCreate
									# Description: After creation of an Field. New Field object is provided.
									$this->_Parent->ExtensionManager->notifyMembers('FieldPostCreate', '/blueprints/sections/', array('field' => &$field, 'data' => &$data));

						        }
							}
						}

						## TODO: Fix me
						###
						# Delegate: Create
						# Description: Creation of a new Section. Section ID and Primary Field ID are provided.
						#$ExtensionManager->notifyMembers('Create', getCurrentPage(), array('section_id' => $section_id));

		               	redirect(URL . "/symphony/blueprints/sections/edit/$section_id/created/");
								

			        }       
			    }
			}
			
		}
		
		public function __actionEdit(){


			if(@array_key_exists('save', $_POST['action']) || @array_key_exists('done', $_POST['action'])) {

				$canProceed = true;

			    $fields = $_POST['fields'];
				$meta = $_POST['meta'];

				$section_id = $this->_context[1];	
			    $sectionManager = new SectionManager($this->_Parent);
				$existing_section = $sectionManager->fetch($section_id);

				$fieldManager = new FieldManager($this->_Parent);

				$this->_errors = array();

				## Check to ensure all the required section fields are filled
				if(!isset($meta['name']) || trim($meta['name']) == ''){
					$required = array('Name');
					$this->_errors['name'] = __('This is a required field.');
					$canProceed = false;
				}

				## Check for duplicate section handle
				elseif(
					$meta['name'] != $existing_section->get('name') 
					&& Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `name` = '" . Symphony::Database()->cleanValue($meta['name']) . "' AND `id` != {$section_id} LIMIT 1")
				){
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
				elseif(is_array($fields) && !empty($fields)){

					## Check for duplicate CF names
					if($canProceed){
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

					if($canProceed){

						$fieldManager = new FieldManager($this->_Parent);
						
						$unique = array();
						
						foreach($fields as $position => $data){
							$required = NULL;

							$field = $fieldManager->create($data['type']);
							$field->setFromPOST($data);

							if($field->mustBeUnique() && !in_array($field->get('type'), $unique)) $unique[] = $field->get('type');
							elseif($field->mustBeUnique() && in_array($field->get('type'), $unique)){
								## Warning. cannot have 2 of this field!
								$canProceed = false;
								$this->_errors[$position] = array('label' => __('There is already a field of type <code>%s</code>. There can only be one per section.', array($field->name())));
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
					$meta['hidden'] = (isset($meta['hidden']) ? 'yes' : 'no');

			        if(!$sectionManager->edit($section_id, $meta)){
						$this->pageAlert(__('An unknown database occurred while attempting to create the section.'), Alert::ERROR);
					}

					else{

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

						## Save each custom field
						if(is_array($fields) && !empty($fields)){				
							foreach($fields as $position => $data){

								$field = $fieldManager->create($data['type']);
								$field->setFromPOST($data);
								$field->set('sortorder', (string)$position);
								$field->set('parent_section', $section_id);

								$bEdit = true;					
								if(!$field->get('id')) $bEdit = false;

								## Creation
								if($field->commit()){

									$field_id = $field->get('id');

									###
									# Delegate: FieldPostCreate
									# Delegate: FieldPostEdit
									# Description: After creation/editing of an Field. New Field object is provided.
									$this->_Parent->ExtensionManager->notifyMembers(($bEdit ? 'FieldPostEdit' : 'FieldPostCreate'), '/blueprints/sections/', array('field' => &$field, 'data' => &$data));

								}
							}
						}

						## TODO: Fix Me
						###
						# Delegate: Edit
						# Description: After editing a Section. The ID is provided.
						#$ExtensionManager->notifyMembers('Edit', getCurrentPage(), array('section_id' => $section_id));

		                redirect(URL . "/symphony/blueprints/sections/edit/$section_id/saved/");							

			        }       
			    }
			}

			if(@array_key_exists("delete", $_POST['action'])){
				$section_id = $this->_context[1];
			    $sectionManager = new SectionManager($this->_Parent);
				$sectionManager->delete($section_id);
				redirect(URL . '/symphony/blueprints/sections/');
			}
	
		}
		
	}
