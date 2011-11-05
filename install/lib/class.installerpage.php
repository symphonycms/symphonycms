<?php

	/**
	 * @package content
	 */

	require_once(TOOLKIT . '/class.htmlpage.php');

	Class InstallerPage extends HTMLPage {

		protected $_errors;

		public function __construct($template, $params = array()) {
			parent::__construct();
			$this->_template = $template;
			$this->_params = $params;

			$this->setTitle(__('Symphony Installation'));

			$this->Html->setDTD('<!DOCTYPE html>');
			$this->Html->setAttribute('lang', Lang::get());
			$this->addElementToHead(new XMLElement('meta', NULL, array('charset' => 'UTF-8')), 0);

			$this->addStylesheetToHead(INSTALL_URL . '/assets/main.css', 'screen', 40);
			$this->addScriptToHead(INSTALL_URL . '/assets/main.js', 50);
		}

		protected function __build() {
			parent::__build();

			$this->Form = Widget::Form(INSTALL_FILENAME . ($_GET['lang'] ? '?lang=' . $_GET['lang'] : ''), 'post');

			$title = new XMLElement('h1', __('Install Symphony'));
			$version = new XMLElement('em', __('Version %s', array(VERSION)));
			$languages = new XMLElement('ul');

			foreach(Lang::getAvailableLanguages(false) as $code => $lang) {
				$languages->appendChild(new XMLElement(
					'li',
					Widget::Anchor(
						$lang,
						"?lang={$code}"
					),
					($_REQUEST['lang'] == $code || ($_REQUEST['lang'] == NULL && $code == 'en')) ? array('class' => 'selected') : array()
				));
			}

			$languages->appendChild(new XMLElement(
				'li',
				Widget::Anchor(
					__('Symphony is also available in other languages'),
					'http://symphony-cms.com/download/extensions/translations/'
				),
				array('class' => 'more')
			));

			$title->appendChild($version);
			$this->Body->appendChild($title);
			$this->Body->appendChild($languages);
			$this->Body->appendChild($this->Form);

			$function = 'view' . str_replace('_', '', ucfirst($this->_template));

			$this->$function();
		}

		public function viewExisting() {
			$h2 = new XMLElement('h2', __('Existing Symphony Installation'));
			$p = new XMLElement('p', __('It appears that Symphony has already been installed at this location.'));

			$this->Form->appendChild($h2);
			$this->Form->appendChild($p);
		}

		public function viewRequirements() {
			$h2 = new XMLElement('h2', __('Outstanding Requirements'));
			$p = new XMLElement('p', __('Symphony needs the following requirements satisfied before installation can proceed.'));

			$this->Form->appendChild($h2);
			$this->Form->appendChild($p);

			if(!empty($this->_params['errors'])){
				$dl = new XMLElement('dl');

				foreach($this->_params['errors'] as $err){
					$dl->appendChild(new XMLElement('dt', $err['msg']));
					$dl->appendChild(new XMLElement('dd', $err['details']));
				}

				$this->Form->appendChild($dl);
			}
		}

		public function viewFailure() {
			$h2 = new XMLElement('h2', __('Installation Failure'));
			$p = new XMLElement('p', __('An error occurred during installation.') . ' <a href="install-log.txt">' . __('View your log for more details') . '</a>.');

			$this->Form->appendChild($h2);
			$this->Form->appendChild($p);
		}

		public function viewSuccess() {
			$h2 = new XMLElement('h2', __('Installation Complete'));
			$p = new XMLElement('p', __('Before proceeding, please make sure to delete %s for security reasons.', array('<code>install/</code>')));
			$this->Form->appendChild($h2);
			$this->Form->appendChild($p);

			$submit = new XMLElement('div', null, array('class' => 'submit'));
			$submit->appendChild(Widget::input('submit', __('I promise, now take me to the login page'), 'submit'));
			$submit->appendChild(Widget::input('installation_completed', 'true', 'hidden'));

			$this->Form->setAttribute('action', 'http://' . rtrim(str_replace('http://', '', INSTALL_DOMAIN), '/') . '/symphony/');
			$this->Form->appendChild($submit);
		}

		public function viewConfiguration() {
			$conf = $this->_params['default-config'];

			/* -----------------------------------------------
			 * Populating fields array
			 * -----------------------------------------------
			 */

			if(isset($_POST['fields'])){
				$fields = $_POST['fields'];

				if(!$fields['database']['drop-tables']){
					$fields['database']['drop-tables'] = 'no';
				}

				if(!$fields['database']['use-server-encoding']){
					$fields['database']['use-server-encoding'] = 'no';
				}
			}
			else{
				$fields = array(

					'docroot'					=> DOCROOT,

					'database' => array(
						'host'					=> $conf['database']['host'],
						'port'					=> $conf['database']['port'],
						'user'					=> $conf['database']['user'],
						'password'				=> $conf['database']['password'],
						'db'					=> $conf['database']['db'],
						'tbl_prefix'			=> $conf['database']['tbl_prefix'],
						'drop-tables'			=> 'yes',
						'use-server-encoding'	=> 'yes',
					),

					'permission' => array(
						'file'					=> '0644',
						'directory'				=> '0755'
					),

					'general' => array(
						'sitename'				=> $conf['general']['sitename']
					),

					'region' => array(
						'date_format'			=> $conf['region']['date_format'],
						'time_format'			=> $conf['region']['time_format'],
						'datetime_separator'	=> $conf['region']['datetime_separator']
					)

				);
			}

			/* -----------------------------------------------
			 * Environment settings 
			 * -----------------------------------------------
			 */

			$Environment = new XMLElement('fieldset');
			$Environment->appendChild(new XMLElement('legend', __('Environment Settings')));
			$Environment->appendChild(new XMLElement('p', __('Symphony is ready to be installed at the following location.')));

			$label = Widget::label(__('Root Path'), Widget::input('fields[docroot]', $fields['docroot']));
			$Environment->appendChild($label);

			$this->__appendError(
				array('no-symphony-dir', 'no-write-permission-root', 'no-write-permission-workspace'),
				$label, $Environment
			);

			$this->Form->appendChild($Environment);

			/* -----------------------------------------------
			 * Website & Locale settings 
			 * -----------------------------------------------
			 */

			$Environment = new XMLElement('fieldset');
			$Environment->appendChild(new XMLElement('legend', __('Website Preferences')));

			$label = Widget::label(__('Name'), Widget::input('fields[general][sitename]', $fields['general']['sitename']));
			$Environment->appendChild($label);

			$this->__appendError(
				array('general-no-sitename'),
				$label, $Environment
			);

			$Fieldset = new XMLElement('fieldset');
			$Fieldset->appendChild(new XMLElement('legend', __('Date and Time')));
			$Fieldset->appendChild(new XMLElement('p', __('Customise how Date and Time values are displayed throughout the Administration interface.')));

			// Timezones
			$options = DateTimeObj::getTimezonesSelectOptions((
				isset($fields['region']['timezone'])
				? $fields['region']['timezone']
				: date_default_timezone_get()
			));
			$Fieldset->appendChild(Widget::label(__('Region'), Widget::Select('fields[region][timezone]', $options)));

			// Date formats
			$options = DateTimeObj::getDateFormatsSelectOptions($fields['region']['date_format']);
			$Fieldset->appendChild(Widget::Label(__('Date Format'), Widget::Select('fields[region][date_format]', $options)));

			// Time formats
			$options = DateTimeObj::getTimeFormatsSelectOptions($fields['region']['time_format']);
			$Fieldset->appendChild(Widget::Label(__('Time Format'), Widget::Select('fields[region][time_format]', $options)));

			$Environment->appendChild($Fieldset);

			$this->Form->appendChild($Environment);

			/* -----------------------------------------------
			 * Database settings 
			 * -----------------------------------------------
			 */

			$Database = new XMLElement('fieldset');
			$Database->appendChild(new XMLElement('legend', __('Database Connection')));
			$Database->appendChild(new XMLElement('p', __('Please provide Symphony with access to a database.')));

			// Database name
			$label = Widget::label(__('Database'), Widget::input('fields[database][db]', $fields['database']['db']), $class);
			$Database->appendChild($label);

			$this->__appendError(
				array('database-incorrect-version', 'unknown-database'),
				$label, $Database
			);

			// Database credentials
			$Div = new XMLElement('div', null, array('class' => 'group'));
			$Div->appendChild(Widget::label(__('Username'), Widget::input('fields[database][user]', $fields['database']['user'])));
			$Div->appendChild(Widget::label(__('Password'), Widget::input('fields[database][password]', $fields['database']['password'], 'password')));
			$Database->appendChild($Div);

			$this->__appendError(
				array('no-database-connection'),
				$Div, $Database
			);

			// Advanced configuration
			$Fieldset = new XMLElement('fieldset');
			$Fieldset->appendChild(new XMLElement('legend', __('Advanced Configuration')));
			$Fieldset->appendChild(new XMLElement('p', __('Leave these fields unless you are sure they need to be changed.')));

			// Advanced configuration: Host, Port
			$Div = new XMLElement('div', null, array('class' => 'group'));
			$Div->appendChild(Widget::label(__('Host'), Widget::input('fields[database][host]', $fields['database']['host'])));
			$Div->appendChild(Widget::label(__('Port'), Widget::input('fields[database][port]', $fields['database']['port'])));
			$Fieldset->appendChild($Div);

			$this->__appendError(
				array('no-database-connection'),
				$Div, $Fieldset
			);

			// Advanced configuration: Table Prefix
			$label = Widget::label(__('Table Prefix'), Widget::input('fields[database][tbl_prefix]', $fields['database']['tbl_prefix']));
			$Fieldset->appendChild($label);

			$this->__appendError(
				array('database-table-clash'),
				$label, $Fieldset
			);

			// Advanced configuration: Table Prefix: Drop existing tables
			$Fieldset->appendChild(Widget::label(
				__('Drop existing tables'),
				Widget::input(
					'fields[database][drop-tables]',
					'yes', 'checkbox',
					$fields['database']['drop-tables'] == 'yes' ? array('checked' => 'checked') : array()
				),
			'option'));

			// Advanced configuration: Table Prefix: Use UTF-8 at all times unless otherwise specified
			$Fieldset->appendChild(Widget::label(
				__('Always use %s encoding', array('<code>UTF-8</code>')),
				Widget::input(
					'fields[database][use-server-encoding]',
					'yes', 'checkbox',
					$fields['database']['use-server-encoding'] == 'yes' ? array('checked' => 'checked') : array()
				),
			'option'));
			$Fieldset->appendChild(new XMLElement('p',
				__('If unchecked, Symphony will use your database’s default encoding instead of %s.', array('<code>UTF-8</code>'))
			));

			$Database->appendChild($Fieldset);

			$this->Form->appendChild($Database);

			/* -----------------------------------------------
			 * Permission settings 
			 * -----------------------------------------------
			 */

			$Permissions = new XMLElement('fieldset');
			$Permissions->appendChild(new XMLElement('legend', __('Permission Settings')));
			$Permissions->appendChild(new XMLElement('p', __('Symphony needs permission to read and write both files and directories.')));

			$Div = new XMLElement('div', null, array('class' => 'group'));
			$Div->appendChild(Widget::label(__('Files'), Widget::input('fields[permission][file]', $fields['permission']['file'])));
			$Div->appendChild(Widget::label(__('Directories'), Widget::input('fields[permission][directory]', $fields['permission']['directory'])));

			$Permissions->appendChild($Div);
			$this->Form->appendChild($Permissions);

			/* -----------------------------------------------
			 * User settings 
			 * -----------------------------------------------
			 */

			$User = new XMLElement('fieldset');
			$User->appendChild(new XMLElement('legend', __('User Information')));
			$User->appendChild(new XMLElement('p', __('Once installed, you will be able to login to the Symphony admin with these user details.')));

			// Username
			$label = Widget::label(__('Username'), Widget::input('fields[user][username]', $fields['user']['username']));
			$User->appendChild($label);

			$this->__appendError(
				array('user-no-username'),
				$label, $User
			);

			// Password
			$Div = new XMLElement('div', null, array('class' => 'group'));
			$Div->appendChild(Widget::label(__('Password'), Widget::input('fields[user][password]', $fields['user']['password'], 'password')));
			$Div->appendChild(Widget::label(__('Confirm Password'), Widget::input('fields[user][confirm-password]', $fields['user']['confirm-password'], 'password')));
			$User->appendChild($Div);

			$this->__appendError(
				array('user-no-password', 'user-password-mismatch'),
				$Div, $User
			);

			// Personal information
			$Fieldset = new XMLElement('fieldset');
			$Fieldset->appendChild(new XMLElement('legend', __('Personal Information')));
			$Fieldset->appendChild(new XMLElement('p', __('Please add the following personal details for this user.')));

			// Personal information: First Name, Last Name
			$Div = new XMLElement('div', null, array('class' => 'group'));
			$Div->appendChild(Widget::label(__('First Name'), Widget::input('fields[user][firstname]', $fields['user']['firstname'])));
			$Div->appendChild(Widget::label(__('Last Name'), Widget::input('fields[user][lastname]', $fields['user']['lastname'])));
			$Fieldset->appendChild($Div);

			$this->__appendError(
				array('user-no-name'),
				$Div, $Fieldset
			);

			// Personal information: Email Address
			$label = Widget::label(__('Email Address'), Widget::input('fields[user][email]', $fields['user']['email']));
			$Fieldset->appendChild($label);

			$this->__appendError(
				array('user-invalid-email'),
				$label, $Fieldset
			);

			$User->appendChild($Fieldset);

			$this->Form->appendChild($User);

			/* -----------------------------------------------
			 * Submit area
			 * -----------------------------------------------
			 */

			$this->Form->appendChild(new XMLElement('h2', __('Install Symphony')));
			$this->Form->appendChild(new XMLElement('p', __('Make sure that you delete the %s folder after Symphony has installed successfully.', array('<code>install</code>'))));

			$Submit = new XMLElement('div', null, array('class' => 'submit'));
			$Submit->appendChild(Widget::input('submit', __('Install Symphony'), 'submit'));

			$this->Form->appendChild($Submit);
		}

		private function __appendError(array $codes, XMLElement $element, XMLElement $container){
			foreach($codes as $i => $c){
				if(!isset($this->_params['errors'][$c])) unset($codes[$i]);
			}

			if(!empty($codes)){
				$class = 'warning';
				if($element->getAttribute('class')) $class = $element->getAttribute('class') . ' ' . $class;

				$element->setAttribute('class', $class);

				if(count($codes) > 1){
					$container->appendChild(new XMLElement('p', __('The following errors have been reported:'), array('class' => $class)));
					$ul = new XMLElement('ul');

					foreach($codes as $c){
						if(isset($this->_params['errors'][$c])){
							$container->appendChild(new XMLElement('li', $this->_params['errors'][$c]['details'], array('class' => $class)));
						}
					}
				}
				else{
					$code = array_pop($codes);

					if(isset($this->_params['errors'][$code])){
						$container->appendChild(new XMLElement('p', $this->_params['errors'][$code]['details'], array('class' => $class)));
					}
				}
			}
		}

	}
