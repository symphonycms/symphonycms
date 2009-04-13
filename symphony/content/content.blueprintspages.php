<?php
	
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.eventmanager.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');
	require_once(TOOLKIT . '/class.xsltprocess.php');
		
	class contentBlueprintsPages extends AdministrationPage {
		private $_errors;
		
		public function __viewIndex() {
			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Pages'))));

			$this->appendSubheading(__('Pages'), Widget::Anchor(
				__('Create New'), $this->_Parent->getCurrentPageURL() . 'new/',
				__('Create a new page'), 'create button'
			));
			
			$pages = $this->_Parent->Database->fetch("
				SELECT
					p.*
				FROM
					`tbl_pages` AS p
				ORDER BY
					p.sortorder ASC
			");
			
			$aTableHead = array(
				array(__('Title'), 'col'),
				array(__('<acronym title="Universal Resource Locator">URL</acronym>'), 'col'),
				array(__('<acronym title="Universal Resource Locator">URL</acronym> Parameters'), 'col'),
				array(__('Type'), 'col')
			);	
			
			$aTableBody = array();
			
			if (!is_array($pages) or empty($pages)) {
				$aTableBody = array(Widget::TableRow(array(
					Widget::TableData(__('None found.'), 'inactive', null, count($aTableHead))
				), 'odd'));
				
			}
			
			else{
				
				$bOdd = true;
				
				foreach ($pages as $page) {
					$page_title = $this->_Parent->resolvePageTitle($page['id']);
					$page_url = URL . '/' . $this->_Parent->resolvePagePath($page['id']) . '/';
					$page_edit_url = $this->_Parent->getCurrentPageURL() . 'edit/' . $page['id'] . '/';
					
					$page_types = $this->_Parent->Database->fetchCol('type', "SELECT `type` FROM `tbl_pages_types` WHERE page_id = '".$page['id']."' ORDER BY `type` ASC");
					
					$col_title = Widget::TableData(Widget::Anchor(
						$page_title, $page_edit_url, $page['handle']
					));
					$col_title->appendChild(Widget::Input("items[{$page['id']}]", null, 'checkbox'));
					
					$col_url = Widget::TableData(Widget::Anchor($page_url, $page_url));
					
					if ($page['params']) {
						$col_params = Widget::TableData(trim($page['params'], '/'));
						
					} else {
						$col_params = Widget::TableData(__('None'), 'inactive');
					}
					
					if (!empty($page_types)) {
						$col_types = Widget::TableData(implode(', ', $page_types));
						
					} else {
						$col_types = Widget::TableData(__('None'), 'inactive');
					}
					
					$aTableBody[] = Widget::TableRow(array($col_title, $col_url, $col_params, $col_types), ($bOdd ? 'odd' : NULL));
					
					$bOdd = !$bOdd;
				}
			}
			
			$table = Widget::Table(
				Widget::TableHead($aTableHead), null, 
				Widget::TableBody($aTableBody), 'orderable'
			);
			
			$this->Form->appendChild($table);
			
			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');
			
			$options = array(
				array(null, false, __('With Selected...')),
				array('delete', false, __('Delete'))							
			);
			
			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));
			
			$this->Form->appendChild($tableActions);
		}
		
		## Both the Edit and New pages need the same form
		function __viewNew(){
			$this->__form();
		}
		
		function __viewEdit(){
			$this->__form();			
		}
		
		function __form(){
			
			$this->setPageType('form');
						
			$fields = array();
			
			if($this->_context[0] == 'edit'){
				if(!$page_id = $this->_context[1]) redirect(URL . '/symphony/blueprints/pages/');
					
				if(!$existing = $this->_Parent->Database->fetchRow(0, "SELECT * FROM `tbl_pages` WHERE `id` = '$page_id' LIMIT 1"))
					$this->_Parent->customError(E_USER_ERROR, __('Page not found'), __('The page you requested to edit does not exist.'), false, true, 'error', array('header' => 'HTTP/1.0 404 Not Found'));
			}
			
			if(isset($this->_context[2])){
				switch($this->_context[2]){
					
					case 'saved':
						$this->pageAlert(
							__(
								'Page updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Pages</a>', 
								array(
									DateTimeObj::get(__SYM_TIME_FORMAT__), 
									URL . '/symphony/blueprints/pages/new/', 
									URL . '/symphony/blueprints/pages/' 
								)
							), 
							Alert::SUCCESS);						
						
						break;
						
					case 'created':
						$this->pageAlert(
							__(
								'Page created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Pages</a>', 
								array(
									DateTimeObj::get(__SYM_TIME_FORMAT__), 
									URL . '/symphony/blueprints/pages/new/', 
									URL . '/symphony/blueprints/pages/' 
								)
							), 
							Alert::SUCCESS);
						break;
					
				}
			}
			
			if(isset($_POST['fields'])){
				$fields = $_POST['fields'];
			}
			
			elseif($this->_context[0] == 'edit'){
				
				$fields = $existing;

				$types = $this->_Parent->Database->fetchCol('type', "SELECT `type` FROM `tbl_pages_types` WHERE page_id = '$page_id' ORDER BY `type` ASC");		
				$fields['type'] = @implode(', ', $types);

				$fields['data_sources'] = preg_split('/,/i', $fields['data_sources'], -1, PREG_SPLIT_NO_EMPTY);
				$fields['events'] = preg_split('/,/i', $fields['events'], -1, PREG_SPLIT_NO_EMPTY);
				$fields['body'] = @file_get_contents(PAGES . '/' . trim(str_replace('/', '_', $fields['path'] . '_' . $fields['handle']), '_') . ".xsl");

			}
			
			else{

				$fields['body'] = '<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">
	
</xsl:template>
	
</xsl:stylesheet>';
	
			}
			
			$title = ($this->_context[0] == 'edit' ? $fields['title'] : NULL);
			if(trim($title) == '') $title = $existing['title'];
			
			$this->setTitle(__(($title ? '%1$s &ndash; %2$s &ndash; %3$s' : '%1$s &ndash; %2$s'), array(__('Symphony'), __('Pages'), $title)));
			$this->appendSubheading(($title ? $title : __('Untitled')));

			$div = new XMLElement('div');
			$div->setAttribute('id', 'configure');
						
			$div->appendChild(new XMLElement('h3', __('URL Settings')));
			$group = new XMLElement('div');
			$group->setAttribute('class', 'triple group');
			
			$pages = $this->_Parent->Database->fetch("SELECT * FROM `tbl_pages` " . ($this->_context[0] == 'edit' ? "WHERE `id` != '$page_id' " : '') . "ORDER BY `title` ASC");
			
			$label = Widget::Label(__('Parent Page'));
			
			$options = array(
				array('', false, '/')
			);
			
			if (is_array($pages) and !empty($pages)) {
				if (!function_exists('__compare_pages')) {
					function __compare_pages($a, $b) {
						return strnatcasecmp($a[2], $b[2]);
					}
				}
				
				foreach ($pages as $page) {
					$options[] = array(
						$page['id'], $fields['parent'] == $page['id'],
						'/' . $this->_Parent->resolvePagePath($page['id'])
					);
				}
				
				usort($options, '__compare_pages');
			}
			
			$label->appendChild(Widget::Select('fields[parent]', $options));		
			$group->appendChild($label);
			
			$label = Widget::Label(__('URL Handle'));
			$label->appendChild(Widget::Input('fields[handle]', $fields['handle']));
			$group->appendChild((isset($this->_errors['handle']) ? $this->wrapFormElementWithError($label, $this->_errors['handle']) : $label));
				
			$label = Widget::Label(__('URL Parameters'));
			$label->appendChild(Widget::Input('fields[params]', $fields['params']));				
			$group->appendChild($label);
			
			$div->appendChild($group);
		
			$div->appendChild(new XMLElement('h3', __('Page Metadata')));
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'triple group');

			$label = Widget::Label(__('Events'));
			
			$EventManager = new EventManager($this->_Parent);
			$events = $EventManager->listAll();
			
			$options = array();
			if(is_array($events) && !empty($events)){		
				foreach($events as $name => $about) $options[] = array($name, @in_array($name, $fields['events']), $about['name']);
			}

			$label->appendChild(Widget::Select('fields[events][]', $options, array('multiple' => 'multiple')));		
			$group->appendChild($label);

			$label = Widget::Label(__('Data Sources'));
			
			$DSManager = new DatasourceManager($this->_Parent);
			$datasources = $DSManager->listAll();
			
			$options = array();
			if(is_array($datasources) && !empty($datasources)){		
				foreach($datasources as $name => $about) $options[] = array($name, @in_array($name, $fields['data_sources']), $about['name']);
			}

			$label->appendChild(Widget::Select('fields[data_sources][]', $options, array('multiple' => 'multiple')));
			$group->appendChild($label);
			
			$div3 = new XMLElement('div');
			$label = Widget::Label(__('Page Type'));
			$label->appendChild(Widget::Input('fields[type]', $fields['type']));
			$div3->appendChild((isset($this->_errors['type']) ? $this->wrapFormElementWithError($label, $this->_errors['type']) : $label));
			
			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'tags');
			if($types = $this->__fetchAvailablePageTypes()) foreach($types as $type) $ul->appendChild(new XMLElement('li', $type));
			$div3->appendChild($ul);
			
			$group->appendChild($div3);
			$div->appendChild($group);

			$this->Form->appendChild($div);
							
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'primary');
			
			$label = Widget::Label(__('Title'));		
			$label->appendChild(Widget::Input('fields[title]', General::sanitize($fields['title'])));
			$fieldset->appendChild((isset($this->_errors['title']) ? $this->wrapFormElementWithError($label, $this->_errors['title']) : $label));
			
			$label = Widget::Label(__('Body'));
			$label->appendChild(Widget::Textarea('fields[body]', '25', '50', General::sanitize($fields['body']), array('class' => 'code')));
			$fieldset->appendChild((isset($this->_errors['body']) ? $this->wrapFormElementWithError($label, $this->_errors['body']) : $label));
			
			$this->Form->appendChild($fieldset);
			
			$utilities = General::listStructure(UTILITIES, array('xsl'), false, 'asc', UTILITIES);
			$utilities = $utilities['filelist'];			
			
			if(is_array($utilities) && !empty($utilities)){
			
				$div = new XMLElement('div');
				$div->setAttribute('class', 'secondary');
				
				$h3 = new XMLElement('h3', __('Utilities'));
				$h3->setAttribute('class', 'label');
				$div->appendChild($h3);
				
				$ul = new XMLElement('ul');
				$ul->setAttribute('id', 'utilities');
			
				$i = 0;
				foreach($utilities as $util){
					$li = new XMLElement('li');

					if ($i++ % 2 != 1) {
						$li->setAttribute('class', 'odd');
					}

					$li->appendChild(Widget::Anchor($util, URL . '/symphony/blueprints/utilities/edit/' . str_replace('.xsl', '', $util) . '/', NULL));
					$ul->appendChild($li);
				}
			
				$div->appendChild($ul);
			
				$this->Form->appendChild($div);
							
			}
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', ($this->_context[0] == 'edit' ? __('Save Changes') : __('Create Page')), 'submit', array('accesskey' => 's')));
			
			if($this->_context[0] == 'edit'){
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => __('Delete this page')));
				$div->appendChild($button);
			}
			
			$this->Form->appendChild($div);

		}
		
		function __actionNew(){
			if(@array_key_exists('save', $_POST['action'])){

				$fields = $_POST['fields'];
				
				$this->_errors = array();

				if(!isset($fields['body']) || trim($fields['body']) == '') $this->_errors['body'] = __('Body is a required field');
				elseif(!General::validateXML($fields['body'], $errors, false, new XSLTProcess())) $this->_errors['body'] = __('This document is not well formed. The following error was returned: <code>%s</code>', array($errors[0]['message']));
			
				if(!isset($fields['title']) || trim($fields['title']) == '') $this->_errors['title'] = __('Title is a required field');

				if(trim($fields['type']) != '' && preg_match('/(index|404|403)/i', $fields['type'])){
					
					$haystack = strtolower($fields['type']);
					
					if(preg_match('/\bindex\b/i', $haystack, $matches) && $row = $this->_Parent->Database->fetchRow(0, "SELECT * FROM `tbl_pages_types` WHERE `type` = 'index' LIMIT 1")){					
						$this->_errors['type'] = __('An index type page already exists.');
					}
					
					elseif(preg_match('/\b404\b/i', $haystack, $matches) && $row = $this->_Parent->Database->fetchRow(0, "SELECT * FROM `tbl_pages_types` WHERE `type` = '404' LIMIT 1")){	
						$this->_errors['type'] = __('A 404 type page already exists.');
					}	
					
					elseif(preg_match('/\b403\b/i', $haystack, $matches) && $row = $this->_Parent->Database->fetchRow(0, "SELECT * FROM `tbl_pages_types` WHERE `type` = '403' LIMIT 1")){	
						$this->_errors['type'] = __('A 403 type page already exists.');
					}
										
				}			

				if(empty($this->_errors)){

					## Manipulate some fields
					$fields['sortorder'] = $this->_Parent->Database->fetchVar('next', 0, "SELECT MAX(sortorder) + 1 as `next` FROM `tbl_pages` LIMIT 1");

					if(empty($fields['sortorder']) || !is_numeric($fields['sortorder'])) $fields['sortorder'] = 1;
										
					$autogenerated_handle = false;
					
					if(trim($fields['handle'] ) == ''){ 
						$fields['handle'] = $fields['title'];
						$autogenerated_handle = true;
					}
					
					$fields['handle'] = Lang::createHandle($fields['handle']);		

					if($fields['params']) $fields['params'] = trim(preg_replace('@\/{2,}@', '/', $fields['params']), '/'); //trim($fields['params'], '/');
					
					## Clean up type list
					$types = preg_split('/,\s*/', $fields['type'], -1, PREG_SPLIT_NO_EMPTY);
					$types = @array_map('trim', $types);
					unset($fields['type']);
					
					//if(trim($fields['type'])) $fields['type'] = preg_replace('/\s*,\s*/i', ', ', $fields['type']);
					//else $fields['type'] = NULL;			

					## Manipulate some fields
					$fields['parent'] = ($fields['parent'] != 'None' ? $fields['parent'] : NULL);			
					
					$fields['data_sources'] = @implode(',', $fields['data_sources']);			
					$fields['events'] = @implode(',', $fields['events']);	
					
					$fields['path'] = NULL;
					if($fields['parent']) $fields['path'] = $this->_Parent->resolvePagePath(intval($fields['parent']));

					$filename = trim(str_replace('/', '_', $fields['path'] . '_' . $fields['handle']), '_');
					
					## Duplicate
					if($this->_Parent->Database->fetchRow(0, "SELECT * FROM `tbl_pages` 
										 WHERE `handle` = '" . $fields['handle'] . "' 
										 AND `path` ".($fields['path'] ? " = '".$fields['path']."'" : ' IS NULL')." 
										 LIMIT 1")){
											
						if($autogenerated_handle) $this->_errors['title'] = __('A page with that title already exists');
						else $this->_errors['handle'] = __('A page with that handle already exists'); 

					}
					
					else{	

						## Write the file
						if(!$write = General::writeFile(PAGES . "/$filename.xsl" , $fields['body'], $this->_Parent->Configuration->get('write_mode', 'file')))
							$this->pageAlert(__('Page could not be written to disk. Please check permissions on <code>/workspace/pages</code>.'), Alert::ERROR);

						## Write Successful, add record to the database
						else{

							## No longer need the body text
							unset($fields['body']);

							## Insert the new data
							if(!$this->_Parent->Database->insert($fields, 'tbl_pages')) $this->pageAlert(__('Unknown errors occurred while attempting to save. Please check your <a href="%s">activity log</a>.', array(URL.'/symphony/system/log/')), Alert::ERROR);

							else{
								
								$page_id = $this->_Parent->Database->getInsertID();

								if(is_array($types) && !empty($types)){
									foreach($types as $type) $this->_Parent->Database->insert(array('page_id' => $page_id, 'type' => $type), 'tbl_pages_types');
								}

								## TODO: Fix Me
								###
								# Delegate: Create
								# Description: After saving the Page. The Page's database ID is provided.
								//$ExtensionManager->notifyMembers('Create', getCurrentPage(), array('page_id' => $page_id));

			                    redirect(URL . "/symphony/blueprints/pages/edit/$page_id/created/");

							}
						}
					}
				}
				
				if(is_array($this->_errors) && !empty($this->_errors)) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);				
			}			
		}
		
		function __actionEdit(){
			
			if(!$page_id = $this->_context[1]) redirect(URL . '/symphony/blueprints/pages/');

			if(@array_key_exists('delete', $_POST['action'])) {

				## TODO: Fix Me
				###
				# Delegate: Delete
				# Description: Prior to deletion. Provided with Page's database ID
				//$ExtensionManager->notifyMembers('Delete', getCurrentPage(), array('page' => $page_id));

			    $page = $this->_Parent->Database->fetchRow(0, "SELECT * FROM tbl_pages WHERE `id` = '$page_id'");

				$filename = $page['path'] . '_' . $page['handle'];
				$filename = trim(str_replace('/', '_', $filename), '_');

				$this->_Parent->Database->delete('tbl_pages', " `id` = '$page_id'");
				$this->_Parent->Database->delete('tbl_pages_types', " `page_id` = '$page_id'");	  
				$this->_Parent->Database->query("UPDATE tbl_pages SET `sortorder` = (`sortorder` + 1) WHERE `sortorder` < '$page_id'");

				General::deleteFile(PAGES . "/$filename.xsl");

				redirect(URL . '/symphony/blueprints/pages/');

			}

			elseif(@array_key_exists('save', $_POST['action'])){

				$fields = $_POST['fields'];
				
				$this->_errors = array();
				
				if(!isset($fields['body']) || trim($fields['body']) == '') $this->_errors['body'] = __('Body is a required field');
				elseif(!General::validateXML($fields['body'], $errors, false, new XSLTProcess())) $this->_errors['body'] = __('This document is not well formed. The following error was returned: <code>%s</code>', array($errors[0]['message']));

				if(!isset($fields['title']) || trim($fields['title']) == '') $this->_errors['title'] = __('Title is a required field');

				if(trim($fields['type']) != '' && preg_match('/(index|404|403)/i', $fields['type'])){
					
					$haystack = strtolower($fields['type']);
					
					if(preg_match('/\bindex\b/i', $haystack, $matches) && $row = $this->_Parent->Database->fetchRow(0, "SELECT * FROM `tbl_pages_types` WHERE `page_id` != '$page_id' AND `type` = 'index' LIMIT 1")){					
						$this->_errors['type'] = __('An index type page already exists.');
					}
					
					elseif(preg_match('/\b404\b/i', $haystack, $matches) && $row = $this->_Parent->Database->fetchRow(0, "SELECT * FROM `tbl_pages_types` WHERE `page_id` != '$page_id' AND `type` = '404' LIMIT 1")){	
						$this->_errors['type'] = __('A 404 type page already exists.');
					}	

					elseif(preg_match('/\b403\b/i', $haystack, $matches) && $row = $this->_Parent->Database->fetchRow(0, "SELECT * FROM `tbl_pages_types` WHERE `page_id` != '$page_id' AND `type` = '403' LIMIT 1")){	
						$this->_errors['type'] = __('A 403 type page already exists.');
					}					
				}
				
				if(empty($this->_errors)){

					## Manipulate some fields
					//$fields['sortorder'] = $this->_Parent->Database->fetchVar('next', 0, "SELECT MAX(sortorder) + 1 as `next` FROM `tbl_pages` LIMIT 1");
					//
					//if(empty($fields['sortorder']) || !is_numeric($fields['sortorder'])) $fields['sortorder'] = 1;
										
					$autogenerated_handle = false;
					
					if(trim($fields['handle'] ) == ''){ 
						$fields['handle'] = $fields['title'];
						$autogenerated_handle = true;
					}
					
					$fields['handle'] = Lang::createHandle($fields['handle']);		

					if($fields['params']) $fields['params'] = trim(preg_replace('@\/{2,}@', '/', $fields['params']), '/');

					## Clean up type list
					$types = preg_split('/,\s*/', $fields['type'], -1, PREG_SPLIT_NO_EMPTY);
					$types = @array_map('trim', $types);
					unset($fields['type']);
					
					//if(trim($fields['type'])) $fields['type'] = preg_replace('/\s*,\s*/i', ', ', $fields['type']);
					//else $fields['type'] = NULL;		

					## Manipulate some fields
					$fields['parent'] = ($fields['parent'] != 'None' ? $fields['parent'] : NULL);			

					$fields['data_sources'] = @implode(',', $fields['data_sources']);			
					$fields['events'] = @implode(',', $fields['events']);	

					$fields['path'] = NULL;
					if($fields['parent']) $fields['path'] = $this->_Parent->resolvePagePath(intval($fields['parent']));
				
					$new_filename = trim(str_replace('/', '_', $fields['path'] . '_' . $fields['handle']), '_');

					$current = $this->_Parent->Database->fetchRow(0, "SELECT * FROM `tbl_pages` WHERE `id` = '$page_id' LIMIT 1");	

					$current_filename = $current['path'] . '_' . $current['handle'];
					$current_filename = trim(str_replace('/', '_', $current_filename), '_');
					
					## Duplicate
					if($this->_Parent->Database->fetchRow(0, "SELECT * FROM `tbl_pages` 
										 WHERE `handle` = '" . $fields['handle'] . "' 
										 AND `id` != '$page_id' 
										 AND `path` ".($fields['path'] ? " = '".$fields['path']."'" : ' IS NULL')." 
										 LIMIT 1")){
											
						if($autogenerated_handle) $this->_errors['title'] = __('A page with that title %s already exists', array(($fields['parent'] ? __('and parent') : '')));
						else $this->_errors['handle'] = __('A page with that handle %s already exists', array(($fields['parent'] ? __('and parent') : '')));

					}
					
					else{	

						## Write the file
						if(!$write = General::writeFile(PAGES . "/$new_filename.xsl" , $fields['body'], $this->_Parent->Configuration->get('write_mode', 'file')))
							$this->pageAlert(__('Page could not be written to disk. Please check permissions on <code>/workspace/pages</code>.'), Alert::ERROR); 			

						## Write Successful, add record to the database
						else{
							
							if($new_filename != $current_filename) @unlink(PAGES . "/$current_filename.xsl");
							
							## No longer need the body text
							unset($fields['body']);

							## Insert the new data
							if(!$this->_Parent->Database->update($fields, 'tbl_pages', "`id` = '$page_id'")) $this->pageAlert(__('Unknown errors occurred while attempting to save. Please check your <a href="%s">activity log</a>.', array(URL.'/symphony/system/log/')), Alert::ERROR);
							
							else{
								
								$this->_Parent->Database->delete('tbl_pages_types', " `page_id` = '$page_id'");
								
								if(is_array($types) && !empty($types)){
									foreach($types as $type) $this->_Parent->Database->insert(array('page_id' => $page_id, 'type' => $type), 'tbl_pages_types');
								}

								## TODO: Fix Me
								###
								# Delegate: Edit
								# Description: After saving the page. The Page's database ID is provided.
								//$ExtensionManager->notifyMembers('Edit', getCurrentPage(), array('page_id' => $page_id));

			                    redirect(URL . "/symphony/blueprints/pages/edit/$page_id/saved/");

							}
						}
					}
				}
				
				if(is_array($this->_errors) && !empty($this->_errors)) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
			}
		}
		
		function __actionIndex(){

			$checked = @array_keys($_POST['items']);

			if(is_array($checked) && !empty($checked)){
				switch($_POST['with-selected']) {

					case 'delete':

						$pages = $checked;
						
						## TODO: Fix Me
						###
						# Delegate: Delete
						# Description: Prior to deletion. Provided with an array of pages for deletion that can be modified.
						//$ExtensionManager->notifyMembers('Delete', getCurrentPage(), array('pages' => &$pages));			

						$pagesList = join (', ', array_map ('intval', $pages));

						// 1. Fetch page details
						$query = 'SELECT `id`, `sortorder`, `handle`, `path` FROM tbl_pages WHERE `id` IN (' . $pagesList .')';
						$details = $this->_Parent->Database->fetch($query);

						$this->_Parent->Database->delete('tbl_pages', " `id` IN('".implode("','",$checked)."')");
						$this->_Parent->Database->delete('tbl_pages_types', " `page_id` IN('".implode("','",$checked)."')");	  

						foreach($details as $r){

							$filename = $r['path'] . '_' . $r['handle'];
							$filename = trim(str_replace('/', '_', $filename), '_');

							$this->_Parent->Database->query("UPDATE tbl_pages SET `sortorder` = (`sortorder` + 1) WHERE `sortorder` < '".$r['sortorder']."'");     
							General::deleteFile(PAGES . "/$filename.xsl");
						}

						redirect($this->_Parent->getCurrentPageURL());	
						break;  	

				}
			}
		}	
	}


?>