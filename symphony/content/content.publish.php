<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.entrymanager.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.authormanager.php');	
	
	Class contentPublish extends AdministrationPage{
		
		var $_errors;
		
		function __construct(&$parent){
			parent::__construct($parent);
			$this->_errors = array();
		}
		
		function __switchboard($type='view'){

			$function = ($type == 'action' ? '__action' : '__view') . ucfirst($this->_context['page']);
			
			if(!method_exists($this, $function)) {
				
				## If there is no action function, just return without doing anything
				if($type == 'action') return;
				
				$this->_Parent->errorPageNotFound();
				
			}
			
			$this->$function();

		}
		
		function view(){			
			$this->__switchboard();	
		}
		
		function action(){			
			$this->__switchboard('action');		
		}
		
		function __viewIndex(){	
			
			$sectionManager = new SectionManager($this->_Parent);
			
			if(!$section_id = $sectionManager->fetchIDFromHandle($this->_context['section_handle']))
				$this->_Parent->customError(E_USER_ERROR, __('Unknown Section'), __('The Section you are looking, <code>%s</code> for could not be found.', array($this->_context['section_handle'])), false, true);
			
			$section = $sectionManager->fetch($section_id);

			$this->setPageType('table');
			$this->setTitle(__('%s &ndash; %s', array(__('Symphony'), $section->get('name'))));

			$entryManager = new EntryManager($this->_Parent);

		    $authorManager = new AuthorManager($this->_Parent);
		    $authors = $authorManager->fetch();
		
			$filter = $filter_value = $where = $joins = NULL;		
			$current_page = (isset($_REQUEST['pg']) && is_numeric($_REQUEST['pg']) ? max(1, intval($_REQUEST['pg'])) : 1);

			if(isset($_REQUEST['filter'])){
				
				list($field_handle, $filter_value) = explode(':', $_REQUEST['filter']);
				
				$filter_value = rawurldecode($filter_value);
				
				$filter = $this->_Parent->Database->fetchVar('id', 0, "SELECT `f`.`id` 
																		   FROM `tbl_fields` AS `f`, `tbl_sections` AS `s` 
																		   WHERE `s`.`id` = `f`.`parent_section` 
																		   AND f.`element_name` = '$field_handle' 
																		   AND `s`.`handle` = '".$section->get('handle')."' LIMIT 1");

				$field =& $entryManager->fieldManager->fetch($filter);
				
				if(is_object($field)){
					$field->buildDSRetrivalSQL(array($filter_value), $joins, $where, false);
					$filter_value = rawurlencode($filter_value);
				}
				
				else $filter = $filter_value = $where = $joins = NULL;
				

			}
			
			if(isset($_REQUEST['sort']) && is_numeric($_REQUEST['sort'])){
				$sort = intval($_REQUEST['sort']);
				$order = ($_REQUEST['order'] ? strtolower($_REQUEST['order']) : 'asc');
				
				if($section->get('entry_order') != $sort || $section->get('entry_order_direction') != $order){
					$sectionManager->edit($section->get('id'), array('entry_order' => $sort, 'entry_order_direction' => $order));
					redirect($this->_Parent->getCurrentPageURL().'?pg='.$current_page.($filter ? "&filter=$field_handle:$filter_value" : ''));
				}
			}

			elseif(isset($_REQUEST['unsort'])){
				$sectionManager->edit($section->get('id'), array('entry_order' => NULL, 'entry_order_direction' => NULL));
				redirect($this->_Parent->getCurrentPageURL().'?pg='.$current_page);
			}

			$this->Form->setAttribute('action', $this->_Parent->getCurrentPageURL(). '?pg=' . $current_page.($filter ? "&amp;filter=$field_handle:$filter_value" : ''));
			
			## Remove the create button if there is a section link field, and no filtering set for it
			$section_links = $section->fetchFields('sectionlink');

			if(count($section_links) > 1 || (!$filter && $section_links) || (is_object($section_links[0]) && $filter != $section_links[0]->get('id')))
				$this->appendSubheading($section->get('name'));

			else
				$this->appendSubheading($section->get('name'), Widget::Anchor(__('Create New'), $this->_Parent->getCurrentPageURL().'new/'.($filter ? '?prepopulate['.$filter.']=' . $filter_value : ''), __('Create a new entry'), 'create button'));
			
			$entries = $entryManager->fetchByPage($current_page, $section_id, $this->_Parent->Configuration->get('pagination_maximum_rows', 'symphony'), $where, $joins);

			$aTableHead = array();
			
			$visible_columns = $section->fetchVisibleColumns();
			
			if(is_array($visible_columns) && !empty($visible_columns)){
				foreach($visible_columns as $column){

					$label = $column->get('label');

					if($column->isSortable()) {
					
						if($column->get('id') == $section->get('entry_order')){
							$link = $this->_Parent->getCurrentPageURL() . '?pg='.$current_page.'&amp;sort='.$column->get('id').'&amp;order='. ($section->get('entry_order_direction') == 'desc' ? 'asc' : 'desc').($filter ? "&amp;filter=$field_handle:$filter_value" : '');							
							$anchor = Widget::Anchor($label, $link, __('Sort by %s %s', array(($section->get('entry_order_direction') == 'desc' ? __('ascending') : __('descending')), strtolower($column->get('label')))), 'active');
						}
						
						else{
							$link = $this->_Parent->getCurrentPageURL() . '?pg='.$current_page.'&amp;sort='.$column->get('id').'&amp;order=asc'.($filter ? "&amp;filter=$field_handle:$filter_value" : '');							
							$anchor = Widget::Anchor($label, $link, __('Sort by ascending %s', array(strtolower($column->get('label')))));
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
					$child_sections[$key] = $sectionManager->fetch($as['child_section_id']);
					$aTableHead[] = array($child_sections[$key]->get('name'), 'col');
				}
			}
			
			## Table Body
			$aTableBody = array();

			if(!is_array($entries['records']) || empty($entries['records'])){

				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))))
				);
			}

			else{

				$bEven = false;


				$field_pool = array();
				foreach($visible_columns as $column){
					$field_pool[$column->get('id')] = $column;
				}

				foreach($entries['records'] as $entry){

					$tableData = array();

					## Setup each cell
					if(!is_array($visible_columns) || empty($visible_columns)){
						$tableData[] = Widget::TableData(Widget::Anchor($entry->get('id'), $this->_Parent->getCurrentPageURL() . 'edit/' . $entry->get('id') . '/'));
					}

					else{
						
						$link = Widget::Anchor('None', $this->_Parent->getCurrentPageURL() . 'edit/' . $entry->get('id') . '/', $entry->get('id'), 'content');
						
						foreach ($visible_columns as $position => $column) {
							$data = $entry->getData($column->get('id'));
							$field = $field_pool[$column->get('id')];
							
							$value = $field->prepareTableValue($data, ($position == 0 ? $link : null));
							
							if (trim($value) == '') {
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

							$field = $entryManager->fieldManager->fetch($associated_sections[$key]['child_section_field_id']);

							$search_value = ($associated_sections[$key]['parent_section_field_id'] ? $field->fetchAssociatedEntrySearchValue($entry->getData($associated_sections[$key]['parent_section_field_id'])) : $entry->get('id'));

							$associated_entry_count = $field->fetchAssociatedEntryCount($search_value);

							$tableData[] = Widget::TableData(Widget::Anchor(''.max(0, intval($associated_entry_count)).'', URL . '/symphony/publish/'.$as->get('handle').'/?filter=' . $field->get('element_name').':'.rawurlencode($search_value), $entry->get('id'), 'content'));
						}
					}
					
					$tableData[count($tableData) - 1]->appendChild(Widget::Input('items['.$entry->get('id').']', NULL, 'checkbox'));

					## Add a row to the body array, assigning each cell to the row
					$aTableBody[] = Widget::TableRow($tableData, ($bEven ? 'even' : NULL));

					$bEven = !$bEven;			

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

			if(is_array($toggable_fields) && !empty($toggable_fields)){

				$index = 2;

				foreach($toggable_fields as $field){

					$options[$index] = array('label' => __('Set %s', array($field->get('label'))), 'options' => array());

					foreach($field->getToggleStates() as $value => $state){
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
				if($current_page > 1) $li->appendChild(Widget::Anchor(__('First'), $this->_Parent->getCurrentPageURL(). '?pg=1'.($filter ? "&amp;filter=$field_handle:$filter_value" : '')));
				else $li->setValue(__('First'));
				$ul->appendChild($li);

				## Previous
				$li = new XMLElement('li');
				if($current_page > 1) $li->appendChild(Widget::Anchor(__('&larr; Previous'), $this->_Parent->getCurrentPageURL(). '?pg=' . ($current_page - 1).($filter ? "&amp;filter=$field_handle:$filter_value" : '')));
				else $li->setValue(__('&larr; Previous'));
				$ul->appendChild($li);

				## Summary
				$li = new XMLElement('li', __('Page %s of %s', array($current_page, max($current_page, $entries['total-pages']))));
				$li->setAttribute('title', __('Viewing %s - %s of %s entries', array($entries['start'], min($entries['limit'], max(1, $entries['remaining-entries'])), $entries['total-entries'])));
				$ul->appendChild($li);

				## Next
				$li = new XMLElement('li');
				if($current_page < $entries['total-pages']) $li->appendChild(Widget::Anchor(__('Next &rarr;'), $this->_Parent->getCurrentPageURL(). '?pg=' . ($current_page + 1).($filter ? "&amp;filter=$field_handle:$filter_value" : '')));
				else $li->setValue(__('Next &rarr;'));
				$ul->appendChild($li);

				## Last
				$li = new XMLElement('li');
				if($current_page < $entries['total-pages']) $li->appendChild(Widget::Anchor(__('Last'), $this->_Parent->getCurrentPageURL(). '?pg=' . $entries['total-pages'].($filter ? "&amp;filter=$field_handle:$filter_value" : '')));
				else $li->setValue(__('Last'));
				$ul->appendChild($li);			

				$this->Form->appendChild($ul);	

			}
		}

		function __actionIndex(){	
			$checked = @array_keys($_POST['items']);

			if(is_array($checked) && !empty($checked)){
				switch($_POST['with-selected']) {

	            	case 'delete':

						## TODO: Fix Me
						###
						# Delegate: Delete
						# Description: Prior to deletion of entries. Array of Entries is provided.
						#              The array can be manipulated
						//$ExtensionManager->notifyMembers('Delete', getCurrentPage(), array('entry_id' => &$checked));

						$entryManager = new EntryManager($this->_Parent);					

						$entryManager->delete($checked);

					 	redirect($_SERVER['REQUEST_URI']);

					default:
					
						## TODO: Add delegate
						
						list($option, $field_id, $value) = explode('-', $_POST['with-selected'], 3);
						
						if($option == 'toggle'){

							$entryManager = new EntryManager($this->_Parent);
							$field = $entryManager->fieldManager->fetch($field_id);

							foreach($checked as $entry_id){
								$entry = $entryManager->fetch($entry_id);						
								$entry[0]->setData($field_id, $field->toggleFieldData($entry[0]->getData($field_id), $value));							
								$entry[0]->commit();
							}

							redirect($_SERVER['REQUEST_URI']);

						}

						break;
				}
			}
		}		
				
		function __viewNew(){
			
			$sectionManager = new SectionManager($this->_Parent);
			
			if(!$section_id = $sectionManager->fetchIDFromHandle($this->_context['section_handle']))
				$this->_Parent->customError(E_USER_ERROR, __('Unknown Section'), __('The Section you are looking, <code>%s</code> for could not be found.', array($this->_context['section_handle'])), false, true);
		
		    $section = $sectionManager->fetch($section_id);

			$this->setPageType('form');
			$this->Form->setAttribute('enctype', 'multipart/form-data');
			$this->setTitle(__('%s &ndash; %s', array(__('Symphony'), $section->get('name'))));
			$this->appendSubheading(__('Untitled'));
			$this->Form->appendChild(Widget::Input('MAX_FILE_SIZE', $this->_Parent->Configuration->get('max_upload_size', 'admin'), 'hidden'));
			
			$entryManager = new EntryManager($this->_Parent);
			
			$fields = array();
			
			## If there is post data floating around, due to errors, create an entry object
			if(isset($_POST['fields'])){
				
				$fields = $_POST['fields'];
				
				$entry =& $entryManager->create();
				$entry->set('section_id', $section_id);

				$entry->setDataFromPost($fields, $error, true);

			}

			## Brand new entry, so need to create some various objects
			else{		

				$entry =& $entryManager->create();
				$entry->set('section_id', $section_id);
				
				## Check if there is a field to prepopulate
				if(isset($_REQUEST['prepopulate'])){
					$field_id = array_keys($_REQUEST['prepopulate']);
					$field_id = end($field_id);
					
					$value = stripslashes(rawurldecode($_REQUEST['prepopulate'][$field_id]));
					
					if($field = $entryManager->fieldManager->fetch($field_id)){
						$entry->setDataFromPost(array($field->get('element_name') => $value), $error, true);
						$this->Form->prependChild(Widget::Input('prepopulate', "$field_id:".rawurlencode($value), 'hidden'));
					}
					
				}

			}			

			$primary = new XMLElement('fieldset');
			$primary->setAttribute('class', 'primary');

			$sidebar_fields = $section->fetchFields(NULL, 'sidebar');
			$main_fields = $section->fetchFields(NULL, 'main');

			if((!is_array($main_fields) || empty($main_fields)) && (!is_array($sidebar_fields) || empty($sidebar_fields))){
				$primary->appendChild(new XMLElement('p', __('It looks like your trying to create an entry. Perhaps you want fields first? <a href="%s/symphony/blueprints/sections/edit/%s/">Click here to create some.</a>', array(URL, $section->get('id')))));
				
				$this->Form->appendChild($primary);
			}

			else{

				if(is_array($main_fields) && !empty($main_fields)){
					foreach($main_fields as $field){
						$field->displayPublishPanel($primary, $entry->getData($field->get('id')), (isset($this->_errors[$field->get('id')]) ? $this->_errors[$field->get('id')] : NULL));	
					}
					
					$this->Form->appendChild($primary);
				}

				if(is_array($sidebar_fields) && !empty($sidebar_fields)){
					$sidebar = new XMLElement('fieldset');
					$sidebar->setAttribute('class', 'secondary');

					foreach($sidebar_fields as $field){
						$field->displayPublishPanel($sidebar, $entry->getData($field->get('id')), (isset($this->_errors[$field->get('id')]) ? $this->_errors[$field->get('id')] : NULL));
					}

					$this->Form->appendChild($sidebar);
				}

			}

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', __('Create Entry'), 'submit', array('accesskey' => 's')));

			$this->Form->appendChild($div);
		
				
		}
		
		function __actionNew(){
			
			if(array_key_exists('save', $_POST['action']) || array_key_exists("done", $_POST['action'])) {

				$sectionManager = new SectionManager($this->_Parent);

				$section_id = $sectionManager->fetchIDFromHandle($this->_context['section_handle']);

			    if(!$section = $sectionManager->fetch($section_id)) 
					$this->_Parent->customError(E_USER_ERROR, __('Unknown Section'), __('The Section you are looking, <code>%s</code> for could not be found.', $this->_context['section_handle']), false, true);
				
				$entryManager = new EntryManager($this->_Parent);

				$entry =& $entryManager->create();
				$entry->set('section_id', $section_id);
				$entry->set('author_id', $this->_Parent->Author->get('id'));
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
					$this->pageAlert(__('Some errors were encountered while attempting to save.'), AdministrationPage::PAGE_ALERT_ERROR);

				elseif(__ENTRY_OK__ != $entry->setDataFromPost($fields, $error)):
					$this->pageAlert($error['message'], AdministrationPage::PAGE_ALERT_ERROR);

				else:

					###
					# Delegate: EntryPreCreate
					# Description: Just prior to creation of an Entry. Entry object and fields are provided
					$this->_Parent->ExtensionManager->notifyMembers('EntryPreCreate', '/publish/new/', array('section' => $section, 'fields' => &$fields, 'entry' => &$entry));
					
					if(!$entry->commit()){
						define_safe('__SYM_DB_INSERT_FAILED__', true);
						$this->pageAlert(NULL, AdministrationPage::PAGE_ALERT_ERROR);

					}

					else{

						###
						# Delegate: EntryPostCreate
						# Description: Creation of an Entry. New Entry object is provided.			
						$this->_Parent->ExtensionManager->notifyMembers('EntryPostCreate', '/publish/new/', array('section' => $section, 'entry' => $entry, 'fields' => $fields));
					
			  		   	redirect(URL . '/symphony/publish/'.$this->_context['section_handle'].'/edit/'. $entry->get('id') . '/created' . (isset($_POST['prepopulate']) ? ':' . $_POST['prepopulate'] : '') . '/');

					}

				endif;
			}	
			
		}
		
		function __viewEdit(){		
			

			$sectionManager = new SectionManager($this->_Parent);
			
			if(!$section_id = $sectionManager->fetchIDFromHandle($this->_context['section_handle']))
				$this->_Parent->customError(E_USER_ERROR, __('Unknown Section'), __('The Section you are looking, <code>%s</code> for could not be found.', array($this->_context['section_handle'])), false, true);
		
		    $section = $sectionManager->fetch($section_id);

			$entry_id = intval($this->_context['entry_id']);

			$entryManager = new EntryManager($this->_Parent);

			if(!$existingEntry = $entryManager->fetch($entry_id)) $this->_Parent->customError(E_USER_ERROR, __('Unknown Entry'), __('The entry you are looking for could not be found.'), false, true);
			$existingEntry = $existingEntry[0];

			## If there is post data floating around, due to errors, create an entry object
			if(isset($_POST['fields'])){
				
				$fields = $_POST['fields'];
				
				$entry =& $entryManager->create();
				$entry->set('section_id', $existingEntry->get('section_id'));
				$entry->set('id', $entry_id);

				$entry->setDataFromPost($fields, $error, true);
				
			}

			## Editing an entry, so need to create some various objects
			else{

				$entry = $existingEntry;

				$sectionManager = new SectionManager($this->_Parent);
				$section = $sectionManager->fetch($entry->get('section_id'));

			}

			if(isset($this->_context['flag'])){
				
				$link = 'publish/'.$this->_context['section_handle'].'/new/';
				
				list($flag, $field_id, $value) = preg_split('/:/i', $this->_context['flag'], 3);
				
				if(is_numeric($field_id) && $value) $link .= "?prepopulate[$field_id]=$value";
				
				switch($flag){
					
					case 'saved':
						$this->pageAlert(__('%s updated successfully. <a href="%s/symphony/%s">Create another?</a>', array('Entry', URL, $link)), AdministrationPage::PAGE_ALERT_NOTICE);
						break;
						
					case 'created':
						$this->pageAlert(__('%s created successfully. <a href="%s/symphony/%s">Create another?</a>', array('Entry', URL, $link)), AdministrationPage::PAGE_ALERT_NOTICE);
						break;
					
				}
			}

			### Determine the page title
			$field_id = $this->_Parent->Database->fetchVar('id', 0, "SELECT `id` FROM `tbl_fields` WHERE `parent_section` = '".$section->get('id')."' ORDER BY `sortorder` LIMIT 1");
			$field = $entryManager->fieldManager->fetch($field_id);

			$title = trim(strip_tags($field->prepareTableValue($existingEntry->getData($field->get('id')))));
			
			if (trim($title) == '') {
				$title = 'Untitled';
			}

			$this->setPageType('form');
			$this->Form->setAttribute('enctype', 'multipart/form-data');
			$this->setTitle(__('%s &ndash; %s &ndash; %s', array(__('Symphony'), $section->get('name'), $title)));
			$this->appendSubheading($title);
			$this->Form->appendChild(Widget::Input('MAX_FILE_SIZE', $this->_Parent->Configuration->get('max_upload_size', 'admin'), 'hidden'));
			
			###

			$primary = new XMLElement('fieldset');
			$primary->setAttribute('class', 'primary');

			$sidebar_fields = $section->fetchFields(NULL, 'sidebar');
			$main_fields = $section->fetchFields(NULL, 'main');

			if((!is_array($main_fields) || empty($main_fields)) && (!is_array($sidebar_fields) || empty($sidebar_fields))){
				$primary->appendChild(new XMLElement('p', __('It looks like your trying to create an entry. Perhaps you want custom fields first? <a href="%s/symphony/blueprints/sections/edit/%s/">Click here to create some.</a>', array(URL, $section->get('id')))));
			}

			else{

				if(is_array($main_fields) && !empty($main_fields)){
					foreach($main_fields as $field){
						$field->displayPublishPanel($primary, $entry->getData($field->get('id')), (isset($this->_errors[$field->get('id')]) ? $this->_errors[$field->get('id')] : NULL));	
					}
					
					$this->Form->appendChild($primary);
				}
				
				if(is_array($sidebar_fields) && !empty($sidebar_fields)){
					$sidebar = new XMLElement('fieldset');
					$sidebar->setAttribute('class', 'secondary');

					foreach($sidebar_fields as $field){
						$field->displayPublishPanel($sidebar, $entry->getData($field->get('id')), (isset($this->_errors[$field->get('id')]) ? $this->_errors[$field->get('id')] : NULL));
					}

					$this->Form->appendChild($sidebar);
				}				

			}

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', array('accesskey' => 's')));
			
			$button = new XMLElement('button', __('Delete'));
			$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => __('Delete this entry')));
			$div->appendChild($button);

			$this->Form->appendChild($div);
			
		}
		
		function __actionEdit(){
			
			$entry_id = intval($this->_context['entry_id']);

			if(@array_key_exists('save', $_POST['action']) || @array_key_exists("done", $_POST['action'])){		

				$entryManager = new EntryManager($this->_Parent);

			    if(!$ret = $entryManager->fetch($entry_id)) $this->_Parent->customError(E_USER_ERROR, __('Unknown Entry'), __('The entry you are looking for could not be found.'), false, true);

				$entry = $ret[0];

				$sectionManager = new SectionManager($this->_Parent);
				$section = $sectionManager->fetch($entry->get('section_id'));

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
					$this->pageAlert(__('Some errors were encountered while attempting to save.'), AdministrationPage::PAGE_ALERT_ERROR);

				elseif(__ENTRY_OK__ != $entry->setDataFromPost($fields, $error)):
					$this->pageAlert($error['message'], AdministrationPage::PAGE_ALERT_ERROR);
						
				else:


					###
					# Delegate: EntryPreEdit
					# Description: Just prior to editing of an Entry.		
					$this->_Parent->ExtensionManager->notifyMembers('EntryPreEdit', '/publish/edit/', array('section' => $section, 'entry' => &$entry, 'fields' => $fields));

					if(!$entry->commit()){
						define_safe('__SYM_DB_INSERT_FAILED__', true);
						$this->pageAlert(NULL, AdministrationPage::PAGE_ALERT_ERROR);

					}

					else{

						###
						# Delegate: EntryPostEdit
						# Description: Editing an entry. Entry object is provided.		
						$this->_Parent->ExtensionManager->notifyMembers('EntryPostEdit', '/publish/edit/', array('section' => $section, 'entry' => $entry, 'fields' => $fields));


			  		    redirect(URL . '/symphony/publish/' . $this->_context['section_handle'] . '/edit/' . $entry_id . '/saved/');
					}

				endif;
			}

			elseif(@array_key_exists('delete', $_POST['action']) && is_numeric($entry_id)){

				## TODO: Fix Me
				###
				# Delegate: Delete
				# Description: Prior to deleting an entry. Entry ID is provided.
				##$ExtensionManager->notifyMembers('Delete', getCurrentPage(), array('entry_id' => $entry_id));

				$entryManager = new EntryManager($this->_Parent);

				$entryManager->delete($entry_id);

				redirect(URL . '/symphony/publish/'.$this->_context['section_handle'].'/');
			}			
			
		}		
		
	}


?>