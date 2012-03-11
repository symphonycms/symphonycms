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

			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.basic.css', 'screen', 40);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.buttons.css', 'screen', 41);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.frames.css', 'screen', 42);
			$this->addStylesheetToHead(SYMPHONY_URL . '/assets/css/symphony.grids.css', 'screen', 42);
			$this->addStylesheetToHead(INSTALL_URL . '/assets/main.css', 'screen', 49);

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

			$this->Form->appendChild($title);

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

				$this->Form->appendChild($languages);
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

			$this->Form->appendChild($h2);

			if(!empty($this->_params['errors'])){
				$div = new XMLElement('div');
				$this->__appendError(array_keys($this->_params['errors']), $div, __('Symphony needs the following requirements satisfied before installation can proceed.'));

				$this->Form->appendChild($div);
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
			$Submit->appendChild(Widget::Input('action[proceed]', __('Proceed with installation'), 'submit'));

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
				new XMLElement('h2', __('The floor is yours'))
			);
			$div->appendChild(
				new XMLElement('p', __('Thanks for taking the quick but epic installation journey with us. It\'s now your turn to shine!'))
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
					__('Before proceeding, we recommend that the %s folder be removed to keep things nice and secure.', array('<code>' . basename(INSTALL) . '</code>'))
				)
			);

			$submit = new XMLElement('div', null, array('class' => 'submit'));
			$submit->appendChild(Widget::Input('submit', __('Ok, now take me to the login page'), 'submit'));

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
				new XMLElement('h2', __('Find something sturdy to hold on to because things are about to get awesome.'))
			);
			$div->appendChild(
				new XMLElement('p', __('Think of this as a pre-game warm up. You know you\'re going to kick-ass, so you\'re savouring every moment before the show. Welcome to the Symphony install page.'))
			);

			$this->Form->appendChild($div);

		/* -----------------------------------------------
		 * Environment settings
		 * -----------------------------------------------
		 */

			$fieldset = new XMLElement('fieldset');
			$div = new XMLElement('div');
			$this->__appendError(array('no-write-permission-root', 'no-write-permission-workspace'), $div);
			if($div->getNumberOfChildren() > 0) {
				$fieldset->appendChild($div);
				$this->Form->appendChild($fieldset);
			}

		/* -----------------------------------------------
		 * Website & Locale settings
		 * -----------------------------------------------
		 */

			$Environment = new XMLElement('fieldset');
			$Environment->appendChild(new XMLElement('legend', __('Website Preferences')));

			$label = Widget::label(__('Name'), Widget::Input('fields[general][sitename]', $fields['general']['sitename']));

			$this->__appendError(array('general-no-sitename'), $label);
			$Environment->appendChild($label);

			$Fieldset = new XMLElement('fieldset', null, array('class' => 'frame'));
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
			$label = Widget::label(__('Database'), Widget::Input('fields[database][db]', $fields['database']['db']), $class);

			$this->__appendError(array('database-incorrect-version', 'unknown-database'), $label);
			$Database->appendChild($label);

			// Database credentials
			$Div = new XMLElement('div', null, array('class' => 'two columns'));
			$Div->appendChild(Widget::label(__('Username'), Widget::Input('fields[database][user]', $fields['database']['user']), 'column'));
			$Div->appendChild(Widget::label(__('Password'), Widget::Input('fields[database][password]', $fields['database']['password'], 'password'), 'column'));

			$this->__appendError(array('database-invalid-credentials'), $Div);
			$Database->appendChild($Div);

			// Advanced configuration
			$Fieldset = new XMLElement('fieldset', null, array('class' => 'frame'));
			$Fieldset->appendChild(new XMLElement('legend', __('Advanced Configuration')));
			$Fieldset->appendChild(new XMLElement('p', __('Leave these fields unless you are sure they need to be changed.')));

			// Advanced configuration: Host, Port
			$Div = new XMLElement('div', null, array('class' => 'two columns'));
			$Div->appendChild(Widget::label(__('Host'), Widget::Input('fields[database][host]', $fields['database']['host']), 'column'));
			$Div->appendChild(Widget::label(__('Port'), Widget::Input('fields[database][port]', $fields['database']['port']), 'column'));

			$this->__appendError(array('no-database-connection'), $Div);
			$Fieldset->appendChild($Div);

			// Advanced configuration: Table Prefix
			$label = Widget::label(__('Table Prefix'), Widget::Input('fields[database][tbl_prefix]', $fields['database']['tbl_prefix']));

			$this->__appendError(array('database-table-clash'), $label);
			$Fieldset->appendChild($label);

			// Advanced configuration: Table Prefix: Use UTF-8 at all times unless otherwise specified
			$Fieldset->appendChild(Widget::label(
				__('Always use %s encoding', array('<code>UTF-8</code>')),
				Widget::Input(
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

			$Div = new XMLElement('div', null, array('class' => 'two columns'));
			$Div->appendChild(Widget::label(__('Files'), Widget::Input('fields[file][write_mode]', $fields['file']['write_mode']), 'column'));
			$Div->appendChild(Widget::label(__('Directories'), Widget::Input('fields[directory][write_mode]', $fields['directory']['write_mode']), 'column'));

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
			$label = Widget::label(__('Username'), Widget::Input('fields[user][username]', $fields['user']['username']));

			$this->__appendError(array('user-no-username'), $label);
			$User->appendChild($label);

			// Password
			$Div = new XMLElement('div', null, array('class' => 'two columns'));
			$Div->appendChild(Widget::label(__('Password'), Widget::Input('fields[user][password]', $fields['user']['password'], 'password'), 'column'));
			$Div->appendChild(Widget::label(__('Confirm Password'), Widget::Input('fields[user][confirm-password]', $fields['user']['confirm-password'], 'password'), 'column'));

			$this->__appendError(array('user-no-password', 'user-password-mismatch'), $Div);
			$User->appendChild($Div);

			// Personal information
			$Fieldset = new XMLElement('fieldset', null, array('class' => 'frame'));
			$Fieldset->appendChild(new XMLElement('legend', __('Personal Information')));
			$Fieldset->appendChild(new XMLElement('p', __('Please add the following personal details for this user.')));

			// Personal information: First Name, Last Name
			$Div = new XMLElement('div', null, array('class' => 'two columns'));
			$Div->appendChild(Widget::label(__('First Name'), Widget::Input('fields[user][firstname]', $fields['user']['firstname']), 'column'));
			$Div->appendChild(Widget::label(__('Last Name'), Widget::Input('fields[user][lastname]', $fields['user']['lastname']), 'column'));

			$this->__appendError(array('user-no-name'), $Div);
			$Fieldset->appendChild($Div);

			// Personal information: Email Address
			$label = Widget::label(__('Email Address'), Widget::Input('fields[user][email]', $fields['user']['email']));

			$this->__appendError(array('user-invalid-email'), $label);
			$Fieldset->appendChild($label);

			$User->appendChild($Fieldset);
			$this->Form->appendChild($User);

		/* -----------------------------------------------
		 * Submit area
		 * -----------------------------------------------
		 */

			$this->Form->appendChild(new XMLElement('h2', __('Install Symphony')));
			$this->Form->appendChild(new XMLElement('p', __('The installation process goes by really quickly. Make sure to take a deep breath before you press that sweet button.', array('<code>' . basename(INSTALL_URL) . '</code>'))));

			$Submit = new XMLElement('div', null, array('class' => 'submit'));
			$Submit->appendChild(Widget::Input('lang', Lang::get(), 'hidden'));

			$Submit->appendChild(Widget::Input('action[install]', __('Install Symphony'), 'submit'));

			$this->Form->appendChild($Submit);
		}

		private function __appendError(array $codes, XMLElement &$element, $message = null){
			if(is_null($message)) {
				$message =  __('The following errors have been reported:');
			}

			foreach($codes as $i => $c){
				if(!isset($this->_params['errors'][$c])) unset($codes[$i]);
			}

			if(!empty($codes)){
				if(count($codes) > 1){
					$ul = new XMLElement('ul');

					foreach($codes as $c){
						if(isset($this->_params['errors'][$c])){
							$ul->appendChild(new XMLElement('li', $this->_params['errors'][$c]['details'], array('class' => $class)));
						}
					}

					$element = Widget::Error($element, $message);
					$element->appendChild($ul);
				}
				else{
					$code = array_pop($codes);

					if(isset($this->_params['errors'][$code])){
						$element = Widget::Error($element, $this->_params['errors'][$code]['details']);
					}
				}
			}
		}

	}
