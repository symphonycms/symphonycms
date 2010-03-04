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
			
			$datasources = DataSourceManager::instance()->listAll();
			
			if (!is_array($datasources) or empty($datasources)) {
				$dsTableBody[] = Widget::TableRow(array(Widget::TableData(
					__('None found.'), 'inactive', null, count($dsTableHead)
				)));
			}
			
			else foreach ($datasources as $ds) {
				$instance = DataSourceManager::instance()->create($ds['handle'], NULL, false);
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
						$section = SectionManager::instance()->fetch($ds['type']);
						
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
				$this->template = $_REQUEST['template'];
				
				if (!$this->template) $this->template = 'sections';
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
			if ($this->failed) {
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
			
			// Track template type with a hidden field:
			if ($this->editing or isset($_POST['template'])) {
				$input = Widget::Input('template', $this->template, 'hidden');
				$this->Form->appendChild($input);
			}
			
			// Let user choose template type:
			else {
				$label = Widget::Label(__('Template'));
				$select = Widget::Select('template', array(
					array('sections', ($this->template == 'sections'), __('Sections')),
					array('users', ($this->template == 'users'), __('Users')),
					array('navigation', ($this->template == 'navigation'), __('Navigation')),
					array('dynamic_xml', ($this->template == 'dynamic_xml'), __('Dynamic XML')),	
					array('static_xml', ($this->template == 'static_xml'), __('Static XML')),
				));
				$select->setAttribute('id', 'datasource_template_switch');
				$this->Form->appendChild($select);
			}
			
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
		
		function __viewInfo(){
			$this->setPageType('form');	
			
			$datasource = DataSource::loadFromName($this->_context[1], NULL, false);	
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
			
		function __appendUserFilter(&$wrapper, $h4_label, $name, $value=NULL, $templateOnly=true){
						
			if(!$templateOnly){				
			
				$li = new XMLElement('li');
				$li->setAttribute('class', 'unique');
				$li->appendChild(new XMLElement('h4', $h4_label));		
				$label = Widget::Label(__('Value'));
				$label->appendChild(Widget::Input('fields[filter][user]['.$name.']', General::sanitize($value)));
				$li->appendChild($label);
			
			 	$wrapper->appendChild($li);	
			}
			
			$li = new XMLElement('li');
			$li->setAttribute('class', 'unique template');
			$li->appendChild(new XMLElement('h4', $h4_label));		
			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('fields[filter][user]['.$name.']'));
			$li->appendChild($label);
		
		 	$wrapper->appendChild($li);
						
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
	
