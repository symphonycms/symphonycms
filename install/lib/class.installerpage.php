<?php

	/**
	 * @package content
	 */

	require_once(TOOLKIT . '/class.htmlpage.php');

	Class InstallerPage extends HTMLPage {

		private $_template;

		protected $_params;

		protected $_page_title;

		public function __construct($template, $params = array()) {
			parent::__construct();

			$this->_template = $template;
			$this->_params = $params;

			$this->_page_title = __('Install Symphony');
		}

		public function generate(){
			$this->Html->setDTD('<!DOCTYPE html>');
			$this->Html->setAttribute('lang', Lang::get());

			$this->setTitle($this->_page_title);
			$this->addElementToHead(new XMLElement('meta', NULL, array('charset' => 'UTF-8')), 1);

			$this->addStylesheetToHead(INSTALL_URL . '/assets/main.css', 'screen', 40);
			$this->addScriptToHead(INSTALL_URL . '/assets/main.js', 50);

			return parent::generate();
		}

		protected function __build($version = VERSION, XMLElement $extra = null) {
			parent::__build();

			$this->Form = Widget::Form(INSTALL_URL . '/index.php', 'post');

			$title = new XMLElement('h1', $this->_page_title);
			$version = new XMLElement('em', __('Version %s', array($version)));

			$title->appendChild($version);

			if(!is_null($extra)){
				$title->appendChild($extra);
			}

			$this->Body->appendChild($title);

			if(isset($this->_params['show-languages']) && $this->_params['show-languages']){
				$languages = new XMLElement('ul');

				foreach(Lang::getAvailableLanguages(false) as $code => $lang) {
					$languages->appendChild(new XMLElement(
						'li',
						Widget::Anchor(
							$lang,
							'?lang=' . $code
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

				$this->Body->appendChild($languages);
			}

			$this->Body->appendChild($this->Form);

			$function = 'view' . str_replace('-', '', ucfirst($this->_template));
			$this->$function();
		}

		protected function viewMissinglog() {
			$h2 = new XMLElement('h2', __('Missing log file'));
			$p = new XMLElement('p', __('Symphony couldn’t create a valid log file. Make sure the %s folder is writable.', array('<code>' . basename(INSTALL) . '</code>')));

			$this->Form->appendChild($h2);
			$this->Form->appendChild($p);
		}

		protected function viewRequirements() {
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

		protected function viewLanguages() {
			$h2 = new XMLElement('h2', __('Language selection'));
			$p = new XMLElement('p', __('This installation can speak in different languages, which one are you fluent in?'));

			$this->Form->appendChild($h2);
			$this->Form->appendChild($p);

			$languages = array();

			foreach(Lang::getAvailableLanguages(false) as $code => $lang) {
				$languages[] = array($code, ($code === 'en'), $lang);
			}

			if(count($languages) > 1){
				$languages[0][1] = false;
				$languages[1][1] = true;
			}

			$this->Form->appendChild(Widget::Select('lang', $languages));

			$Submit = new XMLElement('div', null, array('class' => 'submit'));
			$Submit->appendChild(Widget::input('action[proceed]', __('Proceed with installation'), 'submit'));

			$this->Form->appendChild($Submit);
		}

		protected function viewFailure() {
			$h2 = new XMLElement('h2', __('Installation Failure'));
			$p = new XMLElement('p', __('An error occurred during installation.') . ' ' . __('View the %s for more details', array('<a href="' . INSTALL_URL . '/logs/install">log</a>')));

			$this->Form->appendChild($h2);
			$this->Form->appendChild($p);
		}

		protected function viewSuccess() {
			$this->Form->setAttribute('action', SYMPHONY_URL);

			$div = new XMLElement('div');
			$div->appendChild(
				new XMLElement('h2', __('All done!'))
			);
			$div->appendChild(
				new XMLElement('p', __('Symphony has now been installed and it\'s only a click away. Excited?'))
			);
			$this->Form->appendChild($div);


			$extensions = ' ';
			foreach($this->_params['disabled-extensions'] as $handle){
				$extensions .= '<code>' . $handle . '</code>, ';
			}
			$extensions = rtrim($extensions, ', ');

			if(trim($extensions) != ''){
				$this->Form->appendChild(
					new XMLElement('p',
						__('Unfortunately the following extensions couldn’t be enabled and must be manually installed. Sorry about that.') . $extensions
					)
				);
			}

			$this->Form->appendChild(
				new XMLElement('p',
					__('Before proceeding, we recommend that the %s folder been removed to keep things nice and secure.', array('<code>' . basename(INSTALL) . '</code>'))
				)
			);

			$submit = new XMLElement('div', null, array('class' => 'submit'));
			$submit->appendChild(Widget::input('submit', __('Ok, now take me to the login page'), 'submit'));

			$this->Form->appendChild($submit);
		}

		protected function viewConfiguration() {
			/* -----------------------------------------------
			 * Populating fields array
			 * -----------------------------------------------
			 */

			if(isset($_POST['fields'])){
				$fields = $_POST['fields'];

				if(!$fields['database']['use-server-encoding']){
					$fields['database']['use-server-encoding'] = 'no';
				}
			}
			else{
				$fields = $this->_params['default-config'];

				$fields['database']['use-server-encoding'] = 'no';
				$fields['permissions']['file'] = '0644';
				$fields['permissions']['directory'] = '0755';
			}

			/* -----------------------------------------------
			 * Welcome
			 * -----------------------------------------------
			 */
			$div = new XMLElement('div');
			$div->appendChild(
				new XMLElement('h2', __('Hello! You are about to install Symphony, a flexible and powerful CMS that gives you full control of your content.'))
			);
			$div->appendChild(
				new XMLElement('p', __('It won\'t take more than a minute or two to install, so there\'s no need to put the kettle on just yet.'))
			);
			$this->Form->appendChild($div);

			/* -----------------------------------------------
			 * Environment settings
			 * -----------------------------------------------
			 */

			$div = new XMLElement('div');
			$this->Form->appendChild($div);

			$this->__appendError(
				array('no-write-permission-root', 'no-write-permission-workspace'),
				$div, $div
			);

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

			// Advanced configuration: Table Prefix: Use UTF-8 at all times unless otherwise specified
			$Fieldset->appendChild(Widget::label(
				__('Always use %s encoding', array('<code>UTF-8</code>')),
				Widget::input(
					'fields[database][use-server-encoding]',
					'yes', 'checkbox',
					$fields['database']['use-server-encoding'] == 'no' ? array('checked' => 'checked') : array()
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
			$Div->appendChild(Widget::label(__('Files'), Widget::input('fields[file][write_mode]', $fields['file']['write_mode'])));
			$Div->appendChild(Widget::label(__('Directories'), Widget::input('fields[directory][write_mode]', $fields['directory']['write_mode'])));

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
			$this->Form->appendChild(new XMLElement('p', __('Make sure that you delete the %s folder after Symphony has installed successfully.', array('<code>' . basename(INSTALL_URL) . '</code>'))));

			$Submit = new XMLElement('div', null, array('class' => 'submit'));
			$Submit->appendChild(Widget::input('action[install]', __('Install Symphony'), 'submit'));

			$Submit->appendChild(Widget::input('lang', Lang::get(), 'hidden'));

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
