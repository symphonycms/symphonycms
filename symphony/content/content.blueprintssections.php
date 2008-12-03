<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
 	require_once(TOOLKIT . '/class.sectionmanager.php');
 	require_once(TOOLKIT . '/class.fieldmanager.php');

	Class contentBlueprintsSections extends AdministrationPage{

		var $_errors;
		
		var $_templateOrder;
		
		function __construct(&$parent){
			parent::__construct($parent);
			
			$this->_templateOrder = array('input', 'textarea', 'taglist', 'select', 'checkbox', 'sectionlink', 'date', 'author', 'upload');
			
		}
		
		function __viewIndex(){
			$this->setPageType('table');	
			$this->setTitle('Symphony &ndash; Sections');
			$this->appendSubheading('Sections', Widget::Anchor('Create New', $this->_Parent->getCurrentPageURL().'new/', 'Create a section', 'create button'));	

		    $sectionManager = new SectionManager($this->_Parent);
		    $sections = $sectionManager->fetch(NULL, 'ASC', 'sortorder');

			$aTableHead = array(

				array('Name', 'col'),
				array('Entries', 'col'),

			);	

			$aTableBody = array();

			if(!is_array($sections) || empty($sections)){

				$aTableBody = array(
									Widget::TableRow(array(Widget::TableData(__('None Found.'), 'inactive', NULL, count($aTableHead))))
								);
			}

			else{

				foreach($sections as $s){
					
					$entry_count = intval($this->_Parent->Database->fetchVar('count', 0, "SELECT count(*) AS `count` FROM `tbl_entries` WHERE `section_id` = '".$s->get('id')."' "));
					
					## Setup each cell
					$td1 = Widget::TableData(Widget::Anchor($s->get('name'), $this->_Parent->getCurrentPageURL() . 'edit/' . $s->get('id') .'/', NULL, 'content'));
					$td2 = Widget::TableData(Widget::Anchor("$entry_count", URL . '/symphony/publish/' . $s->get('handle') . '/'));
				
					$td2->appendChild(Widget::Input('items['.$s->get('id').']', 'on', 'checkbox'));

					## Add a row to the body array, assigning each cell to the row
					$aTableBody[] = Widget::TableRow(array($td1, $td2), ($bEven ? 'even' : NULL));		

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
				array(NULL, false, 'With Selected...'),
				array('delete', false, 'Delete')									
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', 'Apply', 'submit'));
			
			$this->Form->appendChild($tableActions);			
			
			
		}
	
		function __viewNew(){
			
			$this->setPageType('form');	
			$this->setTitle('Symphony &ndash; Sections');
			$this->appendSubheading('Untitled');
		
			$fieldManager = new FieldManager($this->_Parent);
			$types = array_union_simple($this->_templateOrder, $fieldManager->fetchTypes());
			
		    $fields = $_POST['fields'];
			$meta = $_POST['meta'];
			
			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));
			
			if($formHasErrors) $this->pageAlert('An error occurred while processing this form. <a href="#error">See below for details.</a>', AdministrationPage::PAGE_ALERT_ERROR);
			
			@ksort($fields);

			$showEmptyTemplate = (is_array($fields) && !empty($fields) ? false : true);

			$meta['entry_order'] = (isset($meta['entry_order']) ? $meta['entry_order'] : 'date');
			$meta['subsection'] = (isset($meta['subsection']) ? 1 : 0);	
			$meta['hidden'] = (isset($meta['hidden']) ? 'yes' : 'no');	

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', 'Essentials'));
			
			$label = Widget::Label('Name');
			$label->appendChild(Widget::Input('meta[name]', $meta['name']));
			
			if(isset($this->_errors['name'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['name']));
			else $fieldset->appendChild($label);	
			
			$label = Widget::Label();
			$input = Widget::Input('meta[hidden]', 'yes', 'checkbox', ($meta['hidden'] == 'yes' ? array('checked' => 'checked') : NULL));
			$label->setValue($input->generate(false) . ' Hide this section from the Publish menu');
			$fieldset->appendChild($label);			
			
			$this->Form->appendChild($fieldset);		

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', 'Fields'));
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'subsection');
			$div->appendChild(new XMLElement('h3', 'Fields'));
				
			$ol = new XMLElement('ol');
			
			if(!$showEmptyTemplate){

				foreach($fields as $position => $data){
					if($input = $fieldManager->create($data['type'])){
						$input->setArray($data);

						$wrapper =& new XMLElement('li');
						
						$input->set('sortorder', $position);
						$input->displaySettingsPanel($wrapper, (isset($this->_errors[$position]) ? $this->_errors[$position] : NULL));
						$ol->appendChild($wrapper);

					}
				}
			}

			foreach($types as $t){		
				if($input = $fieldManager->create($t)){

					$defaults = array();

					$input->findDefaults($defaults);			
					$input->setArray($defaults);

					$wrapper =& new XMLElement('li');
					$wrapper->setAttribute('class', 'template');
					
					$input->set('sortorder', '-1');
					$input->displaySettingsPanel($wrapper);
				
					$ol->appendChild($wrapper);

				}
			}

			$div->appendChild($ol);
			$fieldset->appendChild($div);

			$this->Form->appendChild($fieldset);
		
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', 'Create Section', 'submit', array('accesskey' => 's')));

			$this->Form->appendChild($div);			
			
		}
		
		function __viewEdit(){
			
			$section_id = $this->_context[1];	

		    $sectionManager = new SectionManager($this->_Parent);

		    if(!$section = $sectionManager->fetch($section_id)) 
				$this->_Parent->customError(E_USER_ERROR, 'Unknown Section', 'The Section you are looking for could not be found.', false, true);

			$meta = $section->get();

			$fieldManager = new FieldManager($this->_Parent);	
			$types = array_union_simple($this->_templateOrder, $fieldManager->fetchTypes());

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));			
			if($formHasErrors) $this->pageAlert('An error occurred while processing this form. <a href="#error">See below for details.</a>', AdministrationPage::PAGE_ALERT_ERROR);	


			if(isset($this->_context[2])){
				switch($this->_context[2]){
					
					case 'saved':
						$this->pageAlert('{1} updated successfully. <a href="'.URL.'/symphony/{2}">Create another?</a>', AdministrationPage::PAGE_ALERT_NOTICE, array('Section', 'blueprints/sections/new/'));
						break;
						
					case 'created':
						$this->pageAlert('{1} created successfully. <a href="'.URL.'/symphony/{2}">Create another?</a>', AdministrationPage::PAGE_ALERT_NOTICE, array('Section', 'blueprints/sections/new/'));
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
			$this->setTitle('Symphony &ndash; Sections &ndash; ' . $meta['name']);
			$this->appendSubheading($meta['name']);

			$fieldset = new XMLElement('fieldset');

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', 'Essentials'));
			
			$label = Widget::Label('Name');
			$label->appendChild(Widget::Input('meta[name]', $meta['name']));
			
			if(isset($this->_errors['name'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['name']));
			else $fieldset->appendChild($label);

			$label = Widget::Label();
			$input = Widget::Input('meta[hidden]', 'yes', 'checkbox', ($meta['hidden'] == 'yes' ? array('checked' => 'checked') : NULL));
			$label->setValue($input->generate(false) . ' Hide this section from the Publish menu');
			$fieldset->appendChild($label);
			
			$this->Form->appendChild($fieldset);

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', 'Fields'));
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'subsection');
			$div->appendChild(new XMLElement('h3', 'Fields'));
				
			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'orderable subsection');
			
			if(is_array($fields) && !empty($fields)){
				foreach($fields as $position => $field){

					$wrapper =& new XMLElement('li');
					
					$field->set('sortorder', $position);
					$field->displaySettingsPanel($wrapper, (isset($this->_errors[$position]) ? $this->_errors[$position] : NULL));
					$ol->appendChild($wrapper);

				}
			}

			foreach($types as $t){	
				if($field = $fieldManager->create($t)){

					$defaults = array();

					$field->findDefaults($defaults);			
					$field->setArray($defaults);

					$wrapper =& new XMLElement('li');
					$wrapper->setAttribute('class', 'template');

					$field->set('sortorder', '-1');
					$field->displaySettingsPanel($wrapper);
					$ol->appendChild($wrapper);

				}
			}

			$div->appendChild($ol);
			$fieldset->appendChild($div);
			
			$this->Form->appendChild($fieldset);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', 'Save Changes', 'submit', array('accesskey' => 's')));
		
			$button = new XMLElement('button', 'Delete');
			$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => 'Delete this section'));
			$div->appendChild($button);

			$this->Form->appendChild($div);			
		}
		
		function __actionIndex(){

			$checked = @array_keys($_POST['items']);

			if(is_array($checked) && !empty($checked)){
				switch($_POST['with-selected']) {

					case 'delete':
					
						$sectionManager = new SectionManager($this->_Parent);
						foreach($checked as $section_id) $sectionManager->delete($section_id);

						redirect(URL . '/symphony/blueprints/sections/');
						break;
				}
			}
						
		}
		
		function __actionNew(){		
			
			if(@array_key_exists('save', $_POST['action']) || @array_key_exists('done', $_POST['action'])) {

				$canProceed = true;

			    $fields = $_POST['fields'];
				$meta = $_POST['meta'];
				
				$this->_errors = array();
					
				## Check to ensure all the required section fields are filled
				if(!isset($meta['name']) || trim($meta['name']) == ''){
					$required = array('Name');
					$this->_errors['name'] = 'This is a required field.';
					$canProceed = false;
				}

				## Check for duplicate section handle
				elseif($this->_Parent->Database->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `name` = '" . $meta['name'] . "' LIMIT 1")){
					$this->_errors['name'] = 'A Section with the name <code>'.$meta['name'].'</code> name already exists';
					$canProceed = false;
				}

				## Basic custom field checking
				if(is_array($fields) && !empty($fields)){

					## Ensure there are no subsections CF's if the section itself is a subsection
					/*if(isset($meta['subsection'])){
						foreach($fields as $field){
							if($field['type'] == 'subsection'){
								$Admin->pageAlert('You cannot have Subsection type custom fields if the Section is a Subsection.', NULL, true, 'error');
								$canProceed = false;
								break;
							}
						}
					}*/

					## Check for duplicate CF names
					//if($canProceed){
						$name_list = array();

						foreach($fields as $position => $data){
							if(trim($data['element_name']) == '') 
								$data['element_name'] = $fields[$position]['element_name'] = Lang::createHandle($data['label'], NULL, '-', false, true, array('@^[\d-]+@i' => ''));

							if(trim($data['element_name']) != '' && in_array($data['element_name'], $name_list)){
								$this->_errors[$position] = array('element_name' => 'Two custom fields have the same element name. All element names must be unique.');
								$canProceed = false;
								break;						
							}		
							$name_list[] = $data['element_name'];
						}	
					//}


					//if($canProceed){

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
								$this->_errors[$position] = array('label' => 'There is already a field of type <code>'.$field->name().'</code>. There can only be one per section.');
							}

							$errors = array();

							if(Field::__OK__ != $field->checkFields($errors, false, false) && !empty($errors)){
								$this->_errors[$position] = $errors;
								$canProceed = false;
								break;					
							}
						}
						
					//}
				}


				if($canProceed){

			        $query = 'SELECT MAX(`sortorder`) + 1 AS `next` FROM tbl_sections LIMIT 1';
			        $next = $this->_Parent->Database->fetchVar('next', 0, $query);

			        $meta['sortorder'] = ($next ? $next : '1');
					$meta['handle'] = Lang::createHandle($meta['name']);
					
				 	$sectionManager = new SectionManager($this->_Parent);

					if(!$section_id = $sectionManager->add($meta)){
						$this->pageAlert('An unknown database occurred while attempting to create the section.', AdministrationPage::PAGE_ALERT_ERROR);
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

									## TODO: Fix me
									###
									# Delegate: Create
									# Description: After creation of a new custom field. The ID is provided.
									#$ExtensionManager->notifyMembers('Create', getCurrentPage(), array('field_id' => $field_id));			

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
		
		function __actionEdit(){


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
					$this->_errors['name'] = 'This is a required field.';
					$canProceed = false;
				}

				## Check for duplicate section handle
				elseif($meta['name'] != $existing_section->get('name') && $this->_Parent->Database->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `name` = '" . $meta['name'] . " AND `id` != ' . $section_id . ' LIMIT 1")){
					$this->_errors['name'] = 'A Section with the name <code>'.$meta['name'].'</code> name already exists';
					$canProceed = false;
				}

				## Basic custom field checking
				elseif(is_array($fields) && !empty($fields)){

					## Ensure there are no subsections CF's if the section itself is a subsection
					/*if(isset($meta['subsection'])){
						foreach($fields as $f){
							if($f['type'] == 'subsection'){
								$Admin->pageAlert('You cannot have Subsection type custom fields if the Section is a Subsection.', NULL, true, 'error');
								$canProceed = false;
								break;
							}
						}
					}*/

					## Check for duplicate CF names
					if($canProceed){
						$name_list = array();

						foreach($fields as $position => $data){
							if(trim($data['element_name']) == '') 
								$data['element_name'] = $fields[$position]['element_name'] = Lang::createHandle($data['label'], NULL, '-', false, true, array('@^[\d-]+@i' => ''));

							if(trim($data['element_name']) != '' && in_array($data['element_name'], $name_list)){
								$this->_errors[$position] = array('label' => 'Two custom fields have the same element name. All element names must be unique.');
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
								$this->_errors[$position] = array('label' => 'There is already a field of type <code>'.$field->name().'</code>. There can only be one per section.');
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
						$this->pageAlert('An unknown database occurred while attempting to create the section.', AdministrationPage::PAGE_ALERT_ERROR);
					}

					else{

						## Delete missing CF's
						$id_list = array();
						if(is_array($fields) && !empty($fields)){
							foreach($fields as $position => $data){
								if(isset($data['id'])) $id_list[] = $data['id'];
							}
						}

						$missing_cfs = $this->_Parent->Database->fetchCol('id', "SELECT `id` FROM `tbl_fields` WHERE `parent_section` = '$section_id' AND `id` NOT IN ('".@implode("', '", $id_list)."')");

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
									
									## TODO: Fix Me
									###
									# Delegate: Create
									# Delegate: Edit
									# Description: After creation/editing of a new custom field. The ID is provided.
									##$ExtensionManager->notifyMembers(($bEdit ? 'Edit' : 'Create'), getCurrentPage(), array('field_id' => $field_id));			

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

?>