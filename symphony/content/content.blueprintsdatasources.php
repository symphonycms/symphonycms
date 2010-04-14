<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	//require_once(TOOLKIT . '/class.datasourcemanager.php');
	//require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.messagestack.php');

	Class ContentBlueprintsDatasources extends AdministrationPage{

		protected $errors;
		protected $fields;
		protected $editing;
		protected $failed;
		protected $datasource;
		protected $handle;
		protected $status;
		protected $type;

		public function __construct(){
			parent::__construct();

			$this->errors = new MessageStack;
			$this->fields = array();
			$this->editing = $this->failed = false;
			$this->datasource = $this->handle = $this->status = $this->type = NULL;
		}

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
				array(__('Type'), 'col'),
				array(__('Author'), 'col')
			);

			$dsTableBody = array();

			$datasources = new DatasourceIterator;

			if ($datasources->length() <= 0) {
				$dsTableBody[] = Widget::TableRow(array(Widget::TableData(
					__('None found.'), 'inactive', NULL, count($dsTableHead)
				)));
			}

			else {
				foreach ($datasources as $pathname) {
					$ds = DataSource::load($pathname);

					$view_mode = ($ds->allowEditorToParse() == true ? 'edit' : 'info');
					$handle = preg_replace('/.php$/i', NULL, basename($ds->parameters()->pathname));

					// Name
					$col_name = Widget::TableData(Widget::Anchor(
						$ds->about()->name,
						URL . "/symphony/blueprints/datasources/{$view_mode}/{$handle}/",
						$handle . '.php'
					));
					$col_name->appendChild(Widget::Input("items[{$handle}]", NULL, 'checkbox'));


					// Source
					if(is_null($ds->parameters()->section)){
						$col_source = Widget::TableData(__('None'), 'inactive');
					}
					else{
						$col_source = $ds->prepareSourceColumnValue();
					}

					// Type
					if(is_null($ds->type())){
						$col_type = Widget::TableData(__('Unknown'), 'inactive');
					}
					else{
						$extension = ExtensionManager::instance()->about($ds->type());
						$col_type = Widget::TableData($extension['name']);
					}

					// Author
					if (isset($ds->about()->author->website)) {
						$col_author = Widget::TableData(Widget::Anchor(
							$ds->about()->author->name,
							General::validateURL($ds->about()->author->website)
						));
					}

					else if (isset($ds->about()->author->email)) {
						$col_author = Widget::TableData(Widget::Anchor(
							$ds->about()->author->name,
							'mailto:' . $ds->about()->author->email
						));
					}

					else {
						$col_author = Widget::TableData($ds->about()->author->name);
					}

					$dsTableBody[] = Widget::TableRow(array(
						$col_name, $col_source, $col_type, $col_author
					));
				}
			}

			$table = Widget::Table(
				Widget::TableHead($dsTableHead), NULL,
				Widget::TableBody($dsTableBody), NULL
			);
			$table->setAttribute('id', 'datasources-list');

			$this->Form->appendChild($table);

			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				array(NULL, false, __('With Selected...')),
				array('delete', false, __('Delete'))
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);
		}

		public function build($context) {
			if (isset($context[0]) and ($context[0] == 'edit' or $context[0] == 'new')) {
				$context[0] = 'form';
			}

			parent::build($context);
		}

		protected function __prepareForm() {

			$this->editing = isset($this->_context[1]);

			if (!$this->editing) {
				$this->type = $_REQUEST['type'];

				if (is_null($this->type)){
					$this->type = Symphony::Configuration()->core()->{'default-datasource-type'};
				}

				$this->datasource = ExtensionManager::instance()->create($this->type)->prepare(
					(isset($_POST['fields']) ? $_POST['fields'] : NULL)
				);
			}

			else {
				$this->handle = $this->_context[1];

				// Status message:
				$callback = Administration::instance()->getPageCallback();
				if(isset($callback['flag']) && !is_null($callback['flag'])){
					$this->status = $callback['flag'];
				}

				$this->datasource = Datasource::loadFromName($this->handle, NULL, false);
				$this->type = $this->datasource->type();

				$this->datasource = ExtensionManager::instance()->create($this->type)->prepare(
					(isset($_POST['fields']) ? $_POST['fields'] : NULL), $this->datasource
				);

				//$this->datasource = Datasource::loadFromName($this->handle, NULL, false); //DatasourceManager::instance()->create($this->handle, NULL, false);

				if (!$this->datasource->allowEditorToParse()) {
					redirect(URL . '/symphony/blueprints/datasources/info/' . $this->handle . '/');
				}

				$this->type = $this->datasource->type();
			}

			###
			# Delegate: DataSourceFormPrepare
			# Description: Prepare any data before the form view and action are fired.
			/*ExtensionManager::instance()->notifyMembers(
				'DataSourceFormPrepare', '/backend/',
				array(
					'type'		=> &$this->type,
					'handle'		=> &$this->handle,
					'datasource'	=> $this->datasource,
					'editing'		=> $this->editing,
					'failed'		=> &$this->failed,
					'fields'		=> &$this->fields,
					'errors'		=> &$this->errors
				)
			);*/
		}

		protected function __actionForm() {

			// Delete datasource:
			if ($this->editing && array_key_exists('delete', $_POST['action'])) {

				//if (!General::deleteFile(DATASOURCES . '/data.' . $this->handle . '.php')) {
				$this->__actionDelete(array($this->handle), URL . '/symphony/blueprints/datasources/');

				$this->pageAlert(
					__('Failed to delete <code>%s</code>. Please check permissions.', array(
						$this->handle
					)),
					Alert::ERROR
				);

				return;
			}


			// Saving
			try{
				$pathname = $this->datasource->save($this->errors);
				$handle = preg_replace('/.php$/i', NULL, basename($pathname));
				redirect(URL . "/symphony/blueprints/datasources/edit/{$handle}/:".($this->editing == true ? 'saved' : 'created')."/");
			}
			catch(Exception $e){
				$this->failed = true;

				// There is a special error if writing fails.
				if(isset($this->errors->write)){
					$this->pageAlert(
						$this->errors->write,
						Alert::ERROR
					);
				}
			}

			/*$type_file = NULL;
			$type_data = array();



			$this->fields = $_POST['fields'];

			// About info:
			if (!isset($this->fields['about']['name']) || empty($this->fields['about']['name'])) {
				$this->errors->append('about::name', __('This is a required field'));
				$this->failed = true;
			}

			###
			# Delegate: DataSourceFormAction
			# Description: Prepare any data before the form view and action are fired.
			ExtensionManager::instance()->notifyMembers(
				'DataSourceFormAction', '/backend/',
				array(
					'type'		=> &$this->type,
					'handle'		=> &$this->handle,
					'datasource'	=> $this->datasource,
					'editing'		=> $this->editing,
					'failed'		=> &$this->failed,
					'fields'		=> &$this->fields,
					'errors'		=> &$this->errors,
					'type_file'	=> &$type_file,
					'type_data'	=> &$type_data
				)
			);

			// Save type:
			if ($this->errors->length() <= 0) {
				$user = Administration::instance()->User;

				if (!file_exists($type_file)) {
					throw new Exception(sprintf("Unable to find Data Source type '%s'.", $type_file));
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

				foreach ($type_data as $value) {
					$default_data[] = var_export($value, true);
				}

				header('content-type: text/plain');
				echo vsprintf(file_get_contents($type_file), $default_data);

				exit;
			}*/
		}

		protected function __viewForm() {

			// Show page alert:
			if ($this->failed) {
				$this->pageAlert(
					__('An error occurred while processing this form. <a href="#error">See below for details.</a>'),
					Alert::ERROR
				);
			}

			else if (!is_null($this->status)) {
				switch ($this->status) {
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

			// Track type with a hidden field:
			if($this->editing || ($this->editing && isset($_POST['type']))){
				$input = Widget::Input('type', $this->type, 'hidden');
				$this->Form->appendChild($input);
			}

			// Let user choose type:
			else{
				$label = Widget::Label(__('Type'));

				$options = array();
				foreach(ExtensionManager::instance()->listByType('Data Source') as $e){
					if($e['status'] != Extension::ENABLED) continue;
					$options[] = array($e['handle'], ($this->type == $e['handle']), $e['name']);
				}

				$select = Widget::Select('type', $options);
				$select->setAttribute('id', 'master-switch');
				$this->Form->appendChild($select);
			}

			if(is_null($this->datasource->about()->name) || strlen(trim($this->datasource->about()->name)) == 0){
				$this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array(
					__('Symphony'), __('Data Sources'), __('Untitled')
				)));
				$this->appendSubheading(General::sanitize(__('Untitled')));
			}

			else{
				$this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array(
					__('Symphony'), __('Data Sources'), $this->datasource->about()->name
				)));
				$this->appendSubheading(General::sanitize($this->datasource->about()->name));
			}

			ExtensionManager::instance()->create($this->type)->view($this->datasource, $this->Form, $this->errors);

			/*
			###
			# Delegate: DataSourceFormView
			# Description: Prepare any data before the form view and action are fired.
			ExtensionManager::instance()->notifyMembers(
				'DataSourceFormView', '/backend/',
				array(
					'type'		=> &$this->type,
					'handle'		=> &$this->handle,
					'datasource'	=> $this->datasource,
					'editing'		=> $this->editing,
					'failed'		=> &$this->failed,
					'fields'		=> &$this->fields,
					'errors'		=> &$this->errors,
					'wrapper'		=> $this->Form
				)
			);
			*/

			$actions = new XMLElement('div');
			$actions->setAttribute('class', 'actions');

			$save = Widget::Input('action[save]', __('Create Data Source'), 'submit');
			$save->setAttribute('accesskey', 's');
			$actions->appendChild($save);

			if ($this->editing == true) {
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

			$this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array(__('Symphony'), __('Data Source'), $about->name)));
			$this->appendSubheading($about->name);
			$this->Form->setAttribute('id', 'controller');

			$link = $about->author->name;

			if(isset($about->author->website)){
				$link = Widget::Anchor($about->author->name, General::validateURL($about->author->website));
			}

			elseif(isset($about->author->email)){
				$link = Widget::Anchor($about->author->name, 'mailto:' . $about->author->email);
			}

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
						$fieldset->appendChild(new XMLElement('p', $value . ', released on ' . DateTimeObj::get(__SYM_DATE_FORMAT__, strtotime($about->{'release-date'}))));
						break;

					case 'description':
						$fieldset = new XMLElement('fieldset');
						$fieldset->appendChild(new XMLElement('legend', 'Description'));
						$fieldset->appendChild((is_object($about->description) ? $about->description : new XMLElement('p', $about->description)));

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

		protected function __actionDelete(array $datasources, $redirect=NULL) {
			$success = true;

			foreach ($datasources as $ds) {
				if(!General::deleteFile(DATASOURCES . "/{$ds}.php")){
					$this->pageAlert(__('Failed to delete <code>%s</code>. Please check permissions.', array($this->_context[1])), Alert::ERROR);
				}

				// To Do: Delete reference from View XML

				/*$sql = "SELECT * FROM `tbl_pages` WHERE `data_sources` REGEXP '[[:<:]]".$ds."[[:>:]]' ";
				$pages = Symphony::Database()->fetch($sql);

				if(is_array($pages) && !empty($pages)){
					foreach($pages as $page){

						$page['data_sources'] = preg_replace('/\b'.$ds.'\b/i', '', $page['data_sources']);

						Symphony::Database()->update($page, 'tbl_pages', "`id` = '".$page['id']."'");
					}
				}*/
			}

			if($success == true && !is_null($redirect)){
				redirect($redirect);
			}
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

