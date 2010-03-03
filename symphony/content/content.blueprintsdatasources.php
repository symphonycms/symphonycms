<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');	
	require_once(TOOLKIT . '/class.sectionmanager.php');
	
	Class ContentBlueprintsDatasources extends AdministrationPage{
		
		public function __viewIndex() {
			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Data Sources'))));
			
			$this->appendSubheading(__('Data Sources') . $heading, Widget::Anchor(
				__('Create New'), Administration::instance()->getCurrentPageURL() . 'new/',
				__('Create a new data source'), 'create button'
			));
			
			$dsTableHead = array(
				array(__('Name'), 'col'),
				array(__('Source'), 'col'),
				array(__('Author'), 'col')
			);
			
			$dsTableBody = array();
			
			$DSManager = new DatasourceManager($this->_Parent);
			$sectionManager = new SectionManager($this->_Parent);
			$datasources = $DSManager->listAll();
			
			if (!is_array($datasources) or empty($datasources)) {
				$dsTableBody[] = Widget::TableRow(array(Widget::TableData(
					__('None found.'), 'inactive', null, count($dsTableHead)
				)));
			}
			
			else foreach ($datasources as $ds) {
				$instance = $DSManager->create($ds['handle'], NULL, false);
				$view_mode = ($ds['can_parse'] == true ? 'edit' : 'info');
				
				$col_name = Widget::TableData(Widget::Anchor(
					$ds['name'],
					URL . '/symphony/blueprints/datasources/' . $view_mode . '/' . $ds['handle'] . '/',
					'data.' . $ds['handle'] . '.php'
				));
				$col_name->appendChild(Widget::Input("items[{$ds['handle']}]", null, 'checkbox'));
				
				switch ($ds['type']) {
					case null:
						$col_source = Widget::TableData(__('None'), 'inactive');
						break;
						
					case (is_numeric($ds['type'])):
						$section = $sectionManager->fetch($ds['type']);
						
						if ($section instanceof Section) {
							$section = $section->_data;
							$col_source = Widget::TableData(Widget::Anchor(
								$section['name'],
								URL . '/symphony/blueprints/sections/edit/' . $section['id'] . '/',
								$section['handle']
							));
						}
						
						else {
							$col_source = Widget::TableData(__('None'), 'inactive');
						}
						break;
						
					case "dynamic_xml":
						$url_parts = parse_url($instance->dsParamURL);
						$col_source = Widget::TableData(ucwords($url_parts['host']));
						break;
						
					case "static_xml":
						$col_source = Widget::TableData('Static XML');
						break;
					
					default:
						$col_source = Widget::TableData(ucwords(preg_replace('/_/',' ', $ds['type'])));
				}
				
				if (isset($ds['author']['website'])) {
					$col_author = Widget::TableData(Widget::Anchor(
						$ds['author']['name'],
						General::validateURL($ds['author']['website'])
					));
				}
				
				else if (isset($ds['author']['email'])) {
					$col_author = Widget::TableData(Widget::Anchor(
						$ds['author']['name'],
						'mailto:' . $ds['author']['email']
					));	
				}
				
				else {
					$col_author = Widget::TableData($ds['author']['name']);
				}
				
				$dsTableBody[] = Widget::TableRow(array(
					$col_name, $col_source, $col_author
				));
			}
			
			$table = Widget::Table(
				Widget::TableHead($dsTableHead), null, 
				Widget::TableBody($dsTableBody), null
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
		
		
		protected $errors = array(
			'about'		=> array()
		);
		protected $fields = array();
		protected $editing = false;
		protected $failed = false;
		protected $datasource = null;
		protected $handle = null;
		protected $status = null;
		protected $template = null;
		
		public function build($context) {
			if (isset($context[0]) and ($context[0] == 'edit' or $context[0] == 'new')) {
				$context[0] = 'form';
			}
			
			parent::build($context);
		}
		
		protected function __prepareForm() {
			$this->editing = isset($this->_context[1]);
			
			if (!$this->editing) {
				$this->template = $_GET['template'];
			}
			
			else {
				$this->handle = $this->_context[1];
				$this->status = $this->_context[2];
				
				$datasourceManager = DatasourceManager::instance();
				$this->datasource = $datasourceManager->create($this->handle, null, false);
				
				if (!$this->datasource->allowEditorToParse()) {
					redirect(URL . '/symphony/blueprints/datasources/info/' . $this->handle . '/');
				}
				
				$this->template = $this->datasource->getTemplate();
				$this->fields['about'] = $this->datasource->about();
			}
			
			###
			# Delegate: DataSourceFormPrepare
			# Description: Prepare any data before the form view and action are fired.
			ExtensionManager::instance()->notifyMembers(
				'DataSourceFormPrepare', '/backend/', array(
					'template'		=> &$this->template,
					'handle'		=> &$this->handle,
					'datasource'	=> $this->datasource,
					'editing'		=> $this->editing,
					'failed'		=> &$this->failed,
					'fields'		=> &$this->fields,
					'errors'		=> &$this->errors
				)
			);
		}
		
		protected function __actionForm() {
			$template_file = null;
			$template_data = array();
			
			// Delete datasource:
			if ($this->editing and array_key_exists('delete', $_POST['action'])) {
		    	if (!General::deleteFile(DATASOURCES . '/data.' . $this->handle . '.php')) {
					$this->pageAlert(
						__('Failed to delete <code>%s</code>. Please check permissions.', array(
							$this->handle
						)),
						Alert::ERROR
					);
				}
				
		    	else redirect(URL . '/symphony/blueprints/datasources/');
			}
			
			$this->fields = $_POST['fields'];
			
			// About info:
			if (!isset($this->fields['about']['name']) or empty($this->fields['about']['name'])) {
				$this->errors['about']['name'] = 'Name must not be empty.';
				$this->failed = true;
			}
			
			###
			# Delegate: DataSourceFormAction
			# Description: Prepare any data before the form view and action are fired.
			ExtensionManager::instance()->notifyMembers(
				'DataSourceFormAction', '/backend/', array(
					'template'		=> &$this->template,
					'handle'		=> &$this->handle,
					'datasource'	=> $this->datasource,
					'editing'		=> $this->editing,
					'failed'		=> &$this->failed,
					'fields'		=> &$this->fields,
					'errors'		=> &$this->errors,
					'template_file'	=> &$template_file,
					'template_data'	=> &$template_data
				)
			);
			
			// Save template:
			if ($this->failed === false) {
				$user = Administration::instance()->User;
				
				if (!file_exists($template_file)) {
					throw new Exception(sprintf("Unable to find Data Source Template '%s'.", $template_file));
				}
				
				$default_data = array(
					// Class name:
					str_replace(' ', '_', ucwords(
						str_replace('-', ' ', Lang::createHandle($this->fields['about']['name']))
					)),
					
					// About info:
					var_export($this->fields['about']['name'], true),
					var_export($user->getFullName(), true),
					var_export(URL, true),
					var_export($user->get('email'), true),
					var_export('1.0', true),
					var_export(DateTimeObj::getGMT('c'), true),
				);
				
				foreach ($template_data as $value) {
					$default_data[] = var_export($value, true);
				}
				
				header('content-type: text/plain');
				echo vsprintf(file_get_contents($template_file), $default_data);
				
				exit;
			}
		}
		
		protected function __viewForm() {
			// Show page alert:
			if (is_array($this->_errors) and !empty($this->_errors)) {
				$this->pageAlert(
					__('An error occurred while processing this form. <a href="#error">See below for details.</a>'),
					Alert::ERROR
				);
			}
			
			else if ($this->_status) {
				switch ($this->_status) {
					case 'saved':
						$this->pageAlert(
							__(
								'Data source updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Data sources</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									URL . '/symphony/blueprints/datasources/new/', 
									URL . '/symphony/blueprints/datasources/'
								)
							), 
							Alert::SUCCESS
						);
						break;
						
					case 'created':
						$this->pageAlert(
							__(
								'Data source created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Data sources</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									URL . '/symphony/blueprints/datasources/new/', 
									URL . '/symphony/blueprints/datasources/' 
								)
							), 
							Alert::SUCCESS
						);
						break;
				}
			}
			
			$this->setPageType('form');
			
			$label = Widget::Label(__('Template'));
			$select = Widget::Select('template', array(
				array('sections', ($this->template == 'sections'), __('Sections')),
				array('users', ($this->template == 'users'), __('Users')),
				array('navigation', ($this->template == 'navigation'), __('Navigation')),
				array('dynamic_xml', ($this->template == 'dynamic_xml'), __('Dynamic XML')),	
				array('static_xml', ($this->template == 'static_xml'), __('Static XML')),
			));
			$select->setAttribute('id', 'datasource_template_switch');
			
			if ($this->editing) {
				$select->setAttribute('disabled', 'disabled');
			}
			
			$this->Form->appendChild($select);
			
			if (!isset($this->fields['about']['name']) or empty($this->fields['about']['name'])) {
				$this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array(
					__('Symphony'), __('Data Sources'), __('Untitled')
				)));
				$this->appendSubheading(General::sanitize(__('Untitled')));
			}
			
			else {
				$this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array(
					__('Symphony'), __('Data Sources'), $this->fields['about']['name']
				)));
				$this->appendSubheading(General::sanitize($this->fields['about']['name']));
			}
			
			/*
			// Essential fields:
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Name'));
			$input = Widget::Input('fields[about][name]', General::sanitize($this->fields['about']['name']));
			$label->appendChild($input);
			
			if (isset($this->errors['about']['name'])) {
				$label = Widget::wrapFormElementWithError($label, $this->errors['about']['name']);
			}
			
			$group->appendChild($label);
			
			$label = Widget::Label(__('Template'));
			$select = Widget::Select('template', array(
				array('sections', ($this->template == 'sections'), __('Sections')),
				array('users', ($this->template == 'users'), __('Users')),
				array('navigation', ($this->template == 'navigation'), __('Navigation')),
				array('dynamic_xml', ($this->template == 'dynamic_xml'), __('Dynamic XML')),	
				array('static_xml', ($this->template == 'static_xml'), __('Static XML')),
			));
			
			if ($this->editing) {
				$select->setAttribute('disabled', 'disabled');
			}
			
			$label->appendChild($select);
			$group->appendChild($label);
			$fieldset->appendChild($group);
			$this->Form->appendChild($fieldset);
			*/
			
			###
			# Delegate: DataSourceFormView
			# Description: Prepare any data before the form view and action are fired.
			ExtensionManager::instance()->notifyMembers(
				'DataSourceFormView', '/backend/', array(
					'template'		=> &$this->template,
					'handle'		=> &$this->handle,
					'datasource'	=> $this->datasource,
					'editing'		=> $this->editing,
					'failed'		=> &$this->failed,
					'fields'		=> &$this->fields,
					'errors'		=> &$this->errors,
					'wrapper'		=> $this->Form
				)
			);
			
			$actions = new XMLElement('div');
			$actions->setAttribute('class', 'actions');
			
			$save = Widget::Input('action[save]', __('Create Data Source'), 'submit');
			$save->setAttribute('accesskey', 's');
			$actions->appendChild($save);
			
			if ($this->is_editing) {
				$save->setAttribute('value', __('Save Changes'));
				$button = new XMLElement('button', __('Delete'));
				$button->setAttribute('name', 'action[delete]');
				$button->setAttribute('class', 'confirm delete');
				$button->setAttribute('type', 'submit');
				$button->setAttribute('title', __('Delete this data source'));
				$actions->appendChild($button);
			}
			
			$this->Form->appendChild($actions);			
		}
		
		
		
		
		
		
		
		function __oldform(){

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));
			
			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
			
			if(isset($this->_context[2])){
				switch($this->_context[2]){
					
					case 'saved':
						$this->pageAlert(
							__(
								'Data source updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Data sources</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									URL . '/symphony/blueprints/datasources/new/', 
									URL . '/symphony/blueprints/datasources/'
								)
							), 
							Alert::SUCCESS);
						break;
						
					case 'created':
						$this->pageAlert(
							__(
								'Data source created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Data sources</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									URL . '/symphony/blueprints/datasources/new/', 
									URL . '/symphony/blueprints/datasources/' 
								)
							), 
							Alert::SUCCESS);
						break;
					
				}
			}
			
			$fields = array();
			
			if(isset($_POST['fields'])){
				$fields = $_POST['fields'];
				
				if (is_array($fields['filter']) && !empty($fields['filter'])) {
					$filters = array();
					
					foreach ($fields['filter'] as $f) {
						foreach($f as $key => $val) $filters[$key] = $val;
					}
					
					$fields['filter'][$fields['source']] = $filters;
				}
				
			}
			
			elseif($this->_context[0] == 'edit'){
				
				$isEditing = true;
				$handle = $this->_context[1];
				
				$datasourceManager = new DatasourceManager($this->_Parent);
				$existing =& $datasourceManager->create($handle, NULL, false);
				
				if (!$existing->allowEditorToParse()) redirect(URL . '/symphony/blueprints/datasources/info/' . $handle . '/');
				
				$about = $existing->about();
				$fields['name'] = $about['name'];
				$fields['order'] = $existing->dsParamORDER;
				$fields['param'] = (isset($existing->dsParamPARAMOUTPUT) ? $existing->dsParamPARAMOUTPUT : array());
				$fields['required_url_param'] = $existing->dsParamREQUIREDPARAM;
				if ($existing->dsParamINCLUDEDELEMENTS) {
					$fields['xml_elements'] = $existing->dsParamINCLUDEDELEMENTS;
				} else {
					$fields['xml_elements'] = array();
				}
				$fields['sort'] = $existing->dsParamSORT;
				$fields['page_number'] = $existing->dsParamSTARTPAGE;
				$fields['limit_type'] = $existing->dsParamLIMITTYPE;
				$fields['group'] = $existing->dsParamGROUP;
				$fields['html_encode'] = $existing->dsParamHTMLENCODE;
				$fields['associated_entry_counts'] = $existing->dsParamASSOCIATEDENTRYCOUNTS;				
				if ($fields['associated_entry_counts'] == NULL) $fields['associated_entry_counts'] = 'yes';
				if($existing->dsParamREDIRECTONEMPTY == 'yes') $fields['redirect_on_empty'] = 'yes';
				
				if(!empty($fields['param']) && !is_array($fields['param'])){
					$fields['param'] = array($fields['param']);
				}
				
				$existing->dsParamFILTERS = @array_map('stripslashes', $existing->dsParamFILTERS);
				
				$fields['source'] = $existing->getSource();
				
				switch($fields['source']){
					case 'users':					
						$fields['filter']['user'] = $existing->dsParamFILTERS;
						$fields['max_records'] = $existing->dsParamLIMIT;			
						break;
						
					case 'navigation':
						$fields['filter']['navigation'] = $existing->dsParamFILTERS;					
						break;
						
					case 'dynamic_xml':
						$namespaces = $existing->dsParamFILTERS;
						
						$fields['dynamic_xml'] = array('namespace' => array());
						
						$fields['dynamic_xml']['namespace']['name'] = @array_keys($namespaces);
						$fields['dynamic_xml']['namespace']['uri'] = @array_values($namespaces);
					
						$fields['dynamic_xml']['url'] = $existing->dsParamURL;
						$fields['dynamic_xml']['xpath'] = $existing->dsParamXPATH;
						$fields['dynamic_xml']['cache'] = $existing->dsParamCACHE;
						$fields['dynamic_xml']['timeout'] =	(isset($existing->dsParamTIMEOUT) ? $existing->dsParamTIMEOUT : 6);
										
						break;
						
					case 'static_xml':
						$fields['static_xml'] = trim($existing->grab());		
						break;
						
					default:
						$fields['filter'][$fields['source']] = $existing->dsParamFILTERS;	
						$fields['max_records'] = $existing->dsParamLIMIT;			
						break;					
				}	
				
			}
			
			else {
				$fields['dynamic_xml']['url'] = 'http://';
				$fields['dynamic_xml']['cache'] = '30';
				$fields['dynamic_xml']['xpath'] = '/';
				$fields['dynamic_xml']['timeout'] = '6';
				
				$fields['max_records'] = '20';
				$fields['page_number'] = '1';
				
				$fields['order'] = 'desc';
				$fields['limit_type'] = 'entries';
				$this->template = $_GET['template'];
				$fields['associated_entry_counts'] = NULL;
			}
			
			if (!$isEditing) {
				$options = array(
					array(__('users'), ($this->template == __('users')), __('Users')),
					array(__('navigation'), ($this->template == __('navigation')), __('Navigation')),
					array(__('dynamic_xml'), ($this->template == __('dynamic_xml')), __('Dynamic XML')),	
					array(__('static_xml'), ($this->template == __('static_xml')), __('Static XML')),
				);
				
				$master_select = new XMLElement('div');
				$master_select->setAttribute('id', 'master');
				$master_select->appendChild(Widget::Select('tab', $options));
				$this->Form->appendChild($master_select);
			}
			
			$this->setPageType('form');	
			$this->setTitle(__(($isEditing ? '%1$s &ndash; %2$s &ndash; %3$s' : '%1$s &ndash; %2$s'), array(__('Symphony'), __('Data Sources'), $about['name'])));
			$this->appendSubheading(($isEditing ? $about['name'] : __('Untitled')));
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[name]', General::sanitize($fields['name'])));
			
			if(isset($this->_errors['name'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['name']));
			else $div->appendChild($label);
			
			$fieldset->appendChild($div);
			$this->Form->appendChild($fieldset);
			
			$source = 'Sections';
			if(isset($_GET['source'])){
				$source = $_GET['source'];
			}
			elseif(in_array($fields['source'], array('users', 'navigation', 'dynamic_xml', 'static_xml'))){
				$source = $fields['source'];
			}
			
			if (strtolower($source) != 'sections') {
				###
				# Delegate: EditDataSourceForm|NewDataSourceForm
				# Description: Just prior to creation of an Entry. Entry object and fields are provided
				ExtensionManager::instance()->notifyMembers(
					($isEditing ? 'EditDataSourceForm' : 'NewDataSourceForm'), '/backend/', array(
						'type'		=> $source,
						'fields'	=> $fields,
						'errors'	=> null,
						'wrapper'	=> $this->Form
					)
				);
			}
			
			else {
				$this->__sourceSections($fields);
			}
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', ($isEditing ? __('Save Changes') : __('Create Data Source')), 'submit', array('accesskey' => 's')));
			
			if($isEditing){
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => __('Delete this data source'), 'type' => 'submit'));
				$div->appendChild($button);
			}
			
			$this->Form->appendChild($div);			
			
			return;

		}

		function __viewInfo(){
			$this->setPageType('form');	
			
			$DSManager = new DatasourceManager($this->_Parent);
			$datasource = $DSManager->create($this->_context[1], NULL, false);	
			$about = $datasource->about();

			$this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array(__('Symphony'), __('Data Source'), $about['name'])));
			$this->appendSubheading($about['name']);
			$this->Form->setAttribute('id', 'controller');

			$link = $about['user']['name'];

			if(isset($about['user']['website']))
				$link = Widget::Anchor($about['user']['name'], General::validateURL($about['user']['website']));

			elseif(isset($about['user']['email']))
				$link = Widget::Anchor($about['user']['name'], 'mailto:' . $about['user']['email']);
							
			foreach($about as $key => $value) {
				
				$fieldset = NULL;
				
				switch($key) {
					case 'user':
						$fieldset = new XMLElement('fieldset');
						$fieldset->appendChild(new XMLElement('legend', 'User'));
						$fieldset->appendChild(new XMLElement('p', $link->generate(false)));
						break;
					
					case 'version':
						$fieldset = new XMLElement('fieldset');
						$fieldset->appendChild(new XMLElement('legend', 'Version'));
						$fieldset->appendChild(new XMLElement('p', $value . ', released on ' . DateTimeObj::get(__SYM_DATE_FORMAT__, strtotime($about['release-date']))));
						break;
						
					case 'description':
						$fieldset = new XMLElement('fieldset');
						$fieldset->appendChild(new XMLElement('legend', 'Description'));
						$fieldset->appendChild((is_object($about['description']) ? $about['description'] : new XMLElement('p', $about['description'])));
					
					case 'example':
						if (is_callable(array($datasource, 'example'))) {
							$fieldset = new XMLElement('fieldset');
							$fieldset->appendChild(new XMLElement('legend', 'Example XML'));

							$example = $datasource->example();

							if(is_object($example)) {
								 $fieldset->appendChild($example);
							} else {
								$p = new XMLElement('p');
								$p->appendChild(new XMLElement('pre', '<code>' . str_replace('<', '&lt;', $example) . '</code>'));
								$fieldset->appendChild($p);
							}
						}
						break;
				}
				
				if ($fieldset) {
					$fieldset->setAttribute('class', 'settings');				
					$this->Form->appendChild($fieldset);
				}
				
			}
			
			/*
			$dl->appendChild(new XMLElement('dt', __('URL Parameters')));
			if(!is_array($about['recognised-url-param']) || empty($about['recognised-url-param'])){
				$dl->appendChild(new XMLElement('dd', '<code>'.__('None').'</code>'));
			}			
			else{
				$dd = new XMLElement('dd');
				$ul = new XMLElement('ul');
				
				foreach($about['recognised-url-param'] as $f) $ul->appendChild(new XMLElement('li', '<code>' . $f . '</code>'));

				$dd->appendChild($ul);
				$dl->appendChild($dd);
			}			
			$fieldset->appendChild($dl);
			*/
	
		}
		
		function __oldactionEdit(){
			if(array_key_exists('save', $_POST['action'])) return $this->__formAction();
			elseif(array_key_exists('delete', $_POST['action'])){
				
				## TODO: Fix Me
				###
				# Delegate: Delete
				# Description: Prior to deleting the datasource file. Target file path is provided.
				#$ExtensionManager->notifyMembers('Delete', getCurrentPage(), array("file" => DATASOURCES . "/data." . $_REQUEST['file'] . ".php"));

		    	if(!General::deleteFile(DATASOURCES . '/data.' . $this->_context[1] . '.php'))
					$this->pageAlert(__('Failed to delete <code>%s</code>. Please check permissions.', array($this->_context[1])), Alert::ERROR);

		    	else redirect(URL . '/symphony/blueprints/components/');
						
			} 
		}
		
		function __oldactionNew(){
			if(array_key_exists('save', $_POST['action'])) return $this->__formAction();
		}
		
		private static function __isValidPageString($string){
			return (bool)preg_match('/^(?:\{\$[\w-]+(?::\$[\w-]+)*(?::\d+)?}|\d+)$/', $string);
			
		}
		
		function __oldformAction(){
				
			$fields = $_POST['fields'];
			
			$this->_errors = array();
			
			if(trim($fields['name']) == '') $this->_errors['name'] = __('This is a required field');
			
			if($fields['source'] == 'static_xml'){

				if(trim($fields['static_xml']) == '') $this->_errors['static_xml'] = __('This is a required field');
				else{
					$xml_errors = NULL;
					
					include_once(TOOLKIT . '/class.xsltprocess.php');
					
					General::validateXML($fields['static_xml'], $xml_errors, false, new XsltProcess());

					if(!empty($xml_errors)) $this->_errors['static_xml'] = __('XML is invalid');
				}
			}
			
			elseif($fields['source'] == 'dynamic_xml'){
				
				if(trim($fields['dynamic_xml']['url']) == '') $this->_errors['dynamic_xml']['url'] = __('This is a required field');
				
				if(trim($fields['dynamic_xml']['xpath']) == '') $this->_errors['dynamic_xml']['xpath'] = __('This is a required field');
				
				if(!is_numeric($fields['dynamic_xml']['cache'])) $this->_errors['dynamic_xml']['cache'] = __('Must be a valid number');
				elseif($fields['dynamic_xml']['cache'] < 1) $this->_errors['dynamic_xml']['cache'] = __('Must be greater than zero');
				
			}
			
			else{
							
				if($fields['source'] != 'navigation'){
					
					if(strlen(trim($fields['max_records'])) == 0 || (is_numeric($fields['max_records']) && $fields['max_records'] < 1)){
						$this->_errors['max_records'] = __('A result limit must be set');
					}
					elseif(!self::__isValidPageString($fields['max_records'])){
						$this->_errors['max_records'] = __('Must be a valid number or parameter');
					}


					if(strlen(trim($fields['page_number'])) == 0 || (is_numeric($fields['page_number']) && $fields['page_number'] < 1)){
						$this->_errors['page_number'] = __('A page number must be set');
					}
					elseif(!self::__isValidPageString($fields['page_number'])){
						$this->_errors['page_number'] = __('Must be a valid number or parameter');
					}
				}
				
			}

			$classname = Lang::createHandle($fields['name'], NULL, '_', false, true, array('@^[^a-z]+@i' => '', '/[^\w-\.]/i' => ''));
			$rootelement = str_replace('_', '-', $classname);
			
			$file = DATASOURCES . '/data.' . $classname . '.php';
			
			$isDuplicate = false;
			$queueForDeletion = NULL;
			
			if($this->_context[0] == 'new' && @is_file($file)) $isDuplicate = true;
			elseif($this->_context[0] == 'edit'){
				$existing_handle = $this->_context[1];
				if($classname != $existing_handle && @is_file($file)) $isDuplicate = true;
				elseif($classname != $existing_handle) $queueForDeletion = DATASOURCES . '/data.' . $existing_handle . '.php';			
			}
			
			##Duplicate
			if($isDuplicate) $this->_errors['name'] = __('A Data source with the name <code>%s</code> name already exists', array($classname));
			
			if(empty($this->_errors)){
				
				$dsShell = file_get_contents(TEMPLATE . '/datasource.tpl');
				
				//$oDate = $this->_Parent->getDateObj();
			
				$params = array(
					'rootelement' => $rootelement,
				);
				
				$about = array(
					'name' => $fields['name'],
					'version' => '1.0',
					'release date' => DateTimeObj::getGMT('c'), //date('Y-m-d', $oDate->get(true, false)),
					'author name' => Administration::instance()->User->getFullName(),
					'author website' => URL,
					'author email' => Administration::instance()->User->email
				);

				$source = $fields['source'];
				
				$filter = NULL;
				$elements = NULL;
							
				switch($source){
					
					case 'users':
					
						$filters = $fields['filter']['user'];
						
						$elements = $fields['xml_elements'];
						
						$params['order'] = $fields['order'];
						$params['limit'] = $fields['max_records'];						
						$params['redirectonempty'] = (isset($fields['redirect_on_empty']) ? 'yes' : 'no');
						$params['requiredparam'] = $fields['required_url_param'];
						$params['paramoutput'] = $fields['param'];
						$params['sort'] = $fields['sort'];
						$params['startpage'] = $fields['page_number'];
						
						$dsShell = str_replace('<!-- GRAB -->', "include(TOOLKIT . '/data-sources/datasource.user.php');", $dsShell);
						
						break;
						
					case 'navigation':
					
						$filters = $fields['filter']['navigation'];
					
						$params['order'] = $fields['order'];
						$params['redirectonempty'] = (isset($fields['redirect_on_empty']) ? 'yes' : 'no');
						$params['requiredparam'] = $fields['required_url_param'];			
						
						$dsShell = str_replace('<!-- GRAB -->', "include(TOOLKIT . '/data-sources/datasource.navigation.php');", $dsShell);
						
						break;
						
					case 'dynamic_xml':
					
						$namespaces = $fields['dynamic_xml']['namespace'];
						
						$filters = array();
						
						for($ii = 0; $ii < count($namespaces['name']); $ii++){
							$filters[$namespaces['name'][$ii]] = $namespaces['uri'][$ii];
						}
						
						$params['url'] = $fields['dynamic_xml']['url'];
						$params['xpath'] = $fields['dynamic_xml']['xpath'];
						$params['cache'] = $fields['dynamic_xml']['cache'];
						$params['timeout'] = (isset($fields['dynamic_xml']['timeout']) ? (int)$fields['dynamic_xml']['timeout'] : '6');
						
						$dsShell = str_replace('<!-- GRAB -->', "include(TOOLKIT . '/data-sources/datasource.dynamic_xml.php');", $dsShell);
						
						break;
						
					case 'static_xml':
						
						$fields['static_xml'] = trim($fields['static_xml']);
						
						if(preg_match('/^<\?xml/i', $fields['static_xml']) == true){
							// Need to remove any XML declaration
							$fields['static_xml'] = preg_replace('/^<\?xml[^>]+>/i', NULL, $fields['static_xml']);
						}
						
						$value = sprintf(
							'$result = "%s";',
							addslashes(trim($fields['static_xml']))
						);
						$dsShell = str_replace('<!-- GRAB -->', $value, $dsShell);
						break;
						
					default:

						$elements = $fields['xml_elements'];

						if(is_array($fields['filter']) && !empty($fields['filter'])){
							$filters = array();
							
							foreach($fields['filter'] as $f){
								foreach($f as $key => $val) $filters[$key] = $val;
							}
						}
						
						$params['order'] = $fields['order'];
						$params['group'] = $fields['group'];
						$params['limit'] = $fields['max_records'];
						$params['redirectonempty'] = (isset($fields['redirect_on_empty']) ? 'yes' : 'no');
						$params['requiredparam'] = $fields['required_url_param'];
						$params['paramoutput'] = $fields['param'];
						$params['sort'] = $fields['sort'];
						$params['startpage'] = $fields['page_number'];
						$params['htmlencode'] = $fields['html_encode'];
						$params['associatedentrycounts'] = $fields['associated_entry_counts'];
						
						if ($params['associatedentrycounts'] == NULL) $params['associatedentrycounts'] = 'no';
						
						$dsShell = str_replace('<!-- GRAB -->', "include(TOOLKIT . '/data-sources/datasource.section.php');", $dsShell);
						
						break;
											
				}
				
				$this->__injectVarList($dsShell, $params);
				$this->__injectAboutInformation($dsShell, $about);
				$this->__injectIncludedElements($dsShell, $elements);
				$this->__injectFilters($dsShell, $filters);
				
				$dsShell = str_replace('<!-- CLASS NAME -->', $classname, $dsShell);
				$dsShell = str_replace('<!-- SOURCE -->', $source, $dsShell);
				
				if(preg_match_all('@{(\$ds-[^}]+)}@i', $dsShell, $matches)){
					
					$dependancies = array();
					
					foreach($matches[1] as $match){
						if(preg_match_all('/(\$ds-[^:]+)/i', $match, $inner_matches)) $dependancies = array_merge($dependancies, $inner_matches[1]);
					}
					
					$dependancies = General::array_remove_duplicates($dependancies);
					
					$dsShell = str_replace('<!-- DS DEPENDANCY LIST -->', "'" . implode("', '", $dependancies) . "'", $dsShell);
				}
								
				## Remove left over placeholders
				$dsShell = preg_replace(array('/<!--[\w ]++-->/', '/(\r\n){2,}/', '/(\t+[\r\n]){2,}/'), '', $dsShell);	

				##Write the file
				if(!is_writable(dirname($file)) || !$write = General::writeFile($file, $dsShell, Symphony::Configuration()->get('write_mode', 'file')))
					$this->pageAlert(__('Failed to write Data source to <code>%s</code>. Please check permissions.', array(DATASOURCES)), Alert::ERROR);

				##Write Successful, add record to the database
				else{
					
					if($queueForDeletion){
						
						General::deleteFile($queueForDeletion);
						
						## Update pages that use this DS
				
						$sql = "SELECT * FROM `tbl_pages` WHERE `data_sources` REGEXP '[[:<:]]".$existing_handle."[[:>:]]' ";
						$pages = Symphony::Database()->fetch($sql);

						if(is_array($pages) && !empty($pages)){
							foreach($pages as $page){
								
								$page['data_sources'] = preg_replace('/\b'.$existing_handle.'\b/i', $classname, $page['data_sources']);
								
								Symphony::Database()->update($page, 'tbl_pages', "`id` = '".$page['id']."'");
							}
						}
					}
					
					### TODO: Fix me
					###
					# Delegate: Create
					# Description: After saving the datasource, the file path is provided and an array 
					#              of variables set by the editor
					#$ExtensionManager->notifyMembers('Create', getCurrentPage(), array('file' => $file, 'defines' => $defines, 'var' => $var));

	                redirect(URL . '/symphony/blueprints/datasources/edit/'.$classname.'/'.($this->_context[0] == 'new' ? 'created' : 'saved') . '/');

				}
			}
		}
		
		function __injectIncludedElements(&$shell, $elements){
			if(!is_array($elements) || empty($elements)) return;
			
			$shell = str_replace('<!-- INCLUDED ELEMENTS -->', "public \$dsParamINCLUDEDELEMENTS = array(" . self::CRLF . "\t\t\t'" . implode("'," . self::CRLF . "\t\t\t'", $elements) . "'" . self::CRLF . '		);' . self::CRLF, $shell);
			
		}
		
		function __injectFilters(&$shell, $filters){
			if(!is_array($filters) || empty($filters)) return;
			
			$string = 'public $dsParamFILTERS = array(' . self::CRLF;
			           							
			foreach($filters as $key => $val){
				if(strlen(trim($val)) == 0) continue;
				$string .= "\t\t\t'$key' => '" . addslashes($val) . "'," . self::CRLF;
			}
			
			$string .= '		);' . self::CRLF;
			
			$shell = str_replace('<!-- FILTERS -->', trim($string), $shell);
			
		}
		
		function __injectAboutInformation(&$shell, $details){
			if(!is_array($details) || empty($details)) return;
			
			foreach($details as $key => $val) $shell = str_replace('<!-- ' . strtoupper($key) . ' -->', addslashes($val), $shell);
		}
		
		function __injectVarList(&$shell, $vars){
			if(!is_array($vars) || empty($vars)) return;
			
			$var_list = NULL;
			foreach($vars as $key => $val){
				
				if(!is_array($val) && strlen(trim($val)) == 0) continue;
				
				$var_list .= sprintf('		public $dsParam%s = ', strtoupper($key));
				
				if(is_array($val) && !empty($val)){
					$var_list .= 'array(' . self::CRLF;
					foreach($val as $item){
						$var_list .= sprintf("\t\t\t'%s',", addslashes($item)) . self::CRLF;	
					}
					$var_list .= '		);' . self::CRLF;
				}
				else{
					$var_list .= sprintf("'%s';", addslashes($val)) . self::CRLF;
				}
			}
			
			$shell = str_replace('<!-- VAR LIST -->', trim($var_list), $shell);
			
		}
		
		protected function __actionDelete($datasources, $redirect) {
			$success = true;

			if(!is_array($datasources)) $datasources = array($datasources);
			
			foreach ($datasources as $ds) {
				if(!General::deleteFile(DATASOURCES . '/data.' . $ds . '.php'))
					$this->pageAlert(__('Failed to delete <code>%s</code>. Please check permissions.', array($this->_context[1])), Alert::ERROR);
				
				$sql = "SELECT * FROM `tbl_pages` WHERE `data_sources` REGEXP '[[:<:]]".$ds."[[:>:]]' ";
				$pages = Symphony::Database()->fetch($sql);

				if(is_array($pages) && !empty($pages)){
					foreach($pages as $page){

						$page['data_sources'] = preg_replace('/\b'.$ds.'\b/i', '', $page['data_sources']);
						
						Symphony::Database()->update($page, 'tbl_pages', "`id` = '".$page['id']."'");
					}
				}
			}
			
			if($success) redirect($redirect);
		}
		
		public function __actionIndex() {
			$checked = @array_keys($_POST['items']);
			
			if(is_array($checked) && !empty($checked)) {
				switch ($_POST['with-selected']) {
					case 'delete':
						$this->__actionDelete($checked, URL . '/symphony/blueprints/datasources/');
						break; 
				}
			}
		}

	
	}
	
