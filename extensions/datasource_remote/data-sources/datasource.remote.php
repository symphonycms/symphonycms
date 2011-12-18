<?php

	Interface iDatasource {

		public static function getTemplate();

		public static function buildEditor(XMLElement $wrapper, array &$errors = array(), array $settings = null, $handle = null);

		public static function save($file, array $parameters, array &$errors, $existing_file);

		public static function delete($file);

	}

	require_once TOOLKIT . '/class.datasource.php';

	Class DatasourceRemote extends DataSource implements iDatasource {

	/*-------------------------------------------------------------------------
		Utilities
	-------------------------------------------------------------------------*/

		/**
		 * Returns the type of Datasource provided and it's name.
		 *
		 * @return array
		 * An associative array containing the Datasource classname and the
		 * name of the Datasource.
		 */
		public static function getName() {
			return __('Remote Datasource');
		}

		public static function getHandle() {
			return 'RemoteDatasource';
		}

		/**
		 * Returns the path to the Datasource template
		 *
		 * @return string
		 */
		public static function getTemplate(){
			return EXTENSIONS . '/datasource_remote/templates/datasource.php';
		}

		/**
		 * Returns the source value for display in the Datasources index
		 *
		 * @param string $file
		 *  The path to the Datasource file
		 * @return string
		 */
		public static function getSourceColumn($file) {
			return 'RemoteDatasource';
		}

		/**
		 * Given a `$url` and `$timeout`, this function will use the `Gateway`
		 * class to determine that it is a valid URL and returns successfully
		 * before the `$timeout`. If it does not, an error message will be
		 * returned, otherwise true.
		 *
		 * @since Symphony 2.3
		 * @param string $url
		 * @param integer $timeout
		 *  If not provided, this will default to 6 seconds
		 * @param boolean $fetch_URL
		 *  Defaults to false, but when set to true, this function will use the
		 *  `Gateway` class to attempt to validate the URL's existence and it
		 *  returns before the `$timeout`
		 * @return string|array
		 *  Returns an array with the 'data' if it is a valid URL, otherwise a string
		 *  containing an error message.
		 */
		public static function __isValidURL($url, $timeout = 6, $fetch_URL = false) {
			// Check that URL was provided
			if(trim($url) == '') {
				return __('This is a required field');
			}
			// Check to see the URL works.
			else if ($fetch_URL === true) {
				$gateway = new Gateway;
				$gateway->init($url);
				$gateway->setopt('TIMEOUT', $timeout);
				$data = $gateway->exec();

				$info = $gateway->getInfoLast();

				// 28 is CURLE_OPERATION_TIMEOUTED
				if($info['curl_error'] == 28) {
					return __('Request timed out. %d second limit reached.', array($timeout));
				}
				else if($data === false || $info['http_code'] != 200) {
					return __('Failed to load URL, status code %d was returned.', array($info['http_code']));
				}
			}

			return array('data' => $data);
		}

		/**
		 * This function generates the UI for the Datasource Editor required
		 * to create or edit an instance of this Datasource type. If the editor
		 * is for editing, an instance of this Datasource should be passed with
		 * the function so the interface can be built correctly
		 *
		 * @param XMLElement $wrapper
		 *  An XMLElement for the Editor to be appended to. This is usually
		 *  `AdministrationPage->Form`.
		 * @param string $handle
		 *  The handle of the datasource as returned by `DatasourceManager::__getHandleFromFilename`
		 */
		public static function buildEditor(XMLElement $wrapper, array &$errors = array(), array $settings = null, $handle = null) {
			if(!is_null($handle)) {
				$instance = DatasourceManager::create($handle, array(), false);
				$cache = new Cacheable(Symphony::Database());
			}

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual ' . __CLASS__);
			$fieldset->appendChild(new XMLElement('legend', self::getName()));

	// URL
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group offset');

			$label = Widget::Label(__('URL'));
			$label->appendChild(Widget::Input('fields[' . self::getHandle() . '][url]', General::sanitize($settings[self::getHandle()]['url']), 'text', array('placeholder' => 'http://')));
			if(isset($errors[self::getHandle()]['url'])) {
				$group->appendChild(Widget::wrapFormElementWithError($label, $errors[self::getHandle()]['url']));
			}
			else {
				$group->appendChild($label);
			}

			$p = new XMLElement('p',
				__('Use %s syntax to specify dynamic portions of the URL.', array(
					'<code>{' . __('$param') . '}</code>'
				))
			);
			$p->setAttribute('class', 'help');
			$label->appendChild($p);

			$label = Widget::Label(__('Format'));
			$label->appendChild(
				Widget::Select('fields[' . self::getHandle() . '][format]', array(
					array('xml', $settings[self::getHandle()]['format'] == 'xml', 'XML'),
					array('json', $settings[self::getHandle()]['format'] == 'json', 'JSON')
				))
			);
			if(isset($errors[self::getHandle()]['format'])) $group->appendChild(Widget::wrapFormElementWithError($label, $errors[self::getHandle()]['format']));
			else $group->appendChild($label);

			$fieldset->appendChild($group);

	// Namespaces

			$div = new XMLElement('div');
			$p = new XMLElement('p', __('Namespace Declarations'));
			$p->appendChild(new XMLElement('i', __('Optional')));
			$p->setAttribute('class', 'label');
			$div->appendChild($p);

			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'filters-duplicator');

			if(is_array($settings[self::getHandle()]['namespace']) && !empty($settings[self::getHandle()]['namespace'])){
				$ii = 0;
				foreach($settings[self::getHandle()]['namespace'] as $name => $uri) {
					// Namespaces get saved to the file as $name => $uri, however in
					// the $_POST they are represented as $index => array. This loop
					// patches the difference.
					if(is_array($uri)) {
						$name = $uri['name'];
						$uri = $uri['uri'];
					}

					$li = new XMLElement('li');
					$li->appendChild(new XMLElement('h4', 'Namespace'));

					$group = new XMLElement('div');
					$group->setAttribute('class', 'group');

					$label = Widget::Label(__('Name'));
					$label->appendChild(Widget::Input("fields[" . self::getHandle() . "][namespace][$ii][name]", General::sanitize($name)));
					$group->appendChild($label);

					$label = Widget::Label(__('URI'));
					$label->appendChild(Widget::Input("fields[" . self::getHandle() . "][namespace][$ii][uri]", General::sanitize($uri)));
					$group->appendChild($label);

					$li->appendChild($group);
					$ol->appendChild($li);
					$ii++;
				}
			}

			$li = new XMLElement('li');
			$li->setAttribute('class', 'template');
			$li->appendChild(new XMLElement('h4', __('Namespace')));

			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');

			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[' . self::getHandle() . '][namespace][-1][name]'));
			$group->appendChild($label);

			$label = Widget::Label(__('URI'));
			$label->appendChild(Widget::Input('fields[' . self::getHandle() . '][namespace][-1][uri]'));
			$group->appendChild($label);

			$li->appendChild($group);
			$ol->appendChild($li);

			$div->appendChild($ol);
			$div->appendChild(
				new XMLElement('p', __('Namespaces will automatically be discovered when saving this datasource if it does not include any dynamic portions.'), array('class' => 'help'))
			);

			$fieldset->appendChild($div);

	// Included Elements

			$label = Widget::Label(__('Included Elements'));
			$label->appendChild(Widget::Input('fields[' . self::getHandle() . '][xpath]', General::sanitize($settings[self::getHandle()]['xpath'])));
			if(isset($errors[self::getHandle()]['xpath'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $errors[self::getHandle()]['xpath']));
			else $fieldset->appendChild($label);

			$p = new XMLElement('p', __('Use an XPath expression to select which elements from the source XML to include.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);

	// Caching

			$label = Widget::Label();
			$input = Widget::Input('fields[' . self::getHandle() . '][cache]', max(1, intval($settings[self::getHandle()]['cache'])), NULL, array('size' => '6'));
			$label->setValue(__('Update cached result every %s minutes', array($input->generate(false))));
			if(isset($errors[self::getHandle()]['cache'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $errors[self::getHandle()]['cache']));
			else $fieldset->appendChild($label);

			// Check for existing Cache objects
			if(isset($cache_id)) {
				self::buildCacheInformation($fieldset, $cache, $cache_id);
			}

	// Timeout

			$label = Widget::Label();
			$input = Widget::Input('fields[' . self::getHandle() . '][timeout]', max(1, intval($settings[self::getHandle()]['timeout'])), NULL, array('type' => 'hidden'));
			$label->appendChild($input);
			$fieldset->appendChild($label);

			$wrapper->appendChild($fieldset);
		}

		/**
		 * Helper function to build Cache information block
		 *
		 * @param XMLElement $wrapper
		 * @param Cacheable $cache
		 * @param string $cache_id
		 */
		public static function buildCacheInformation(XMLElement $wrapper, Cacheable $cache, $cache_id) {
			$cachedData = $cache->check($cache_id);
			if(is_array($cachedData) && !empty($cachedData) && (time() < $cachedData['expiry'])) {
				$a = Widget::Anchor(__('Clear now'), SYMPHONY_URL . getCurrentPage() . 'clear_cache/');
				$wrapper->appendChild(
					new XMLElement('p', __('Cache expires in %d minutes. %s', array(
						($cachedData['expiry'] - time()) / 60,
						$a->generate(false)
					)), array('class' => 'help'))
				);
			}
			else {
				$wrapper->appendChild(
					new XMLElement('p', __('Cache has expired or does not exist.'), array('class' => 'help'))
				);
			}
		}

	/*-------------------------------------------------------------------------
		CRUD
	-------------------------------------------------------------------------*/

		public static function validate(array &$settings, array &$errors) {
			if(trim($fields[self::getHandle()]['url']) == '') {
				$errors[self::getHandle()]['url'] = __('This is a required field');
			}

			// Use the TIMEOUT that was specified by the user for a real world indication
			$timeout = isset($fields[self::getHandle()]['timeout'])
				? (int)$fields[self::getHandle()]['timeout']
				: 6;

			// If there is a parameter in the URL, we can't validate the existence of the URL
			// as we don't have the environment details of where this datasource is going
			// to be executed.
			if(!preg_match('@{([^}]+)}@i', $fields[self::getHandle()]['url'])) {
				$valid_url = self::__isValidURL($fields[self::getHandle()]['url'], $timeout, $error);

				if($valid_url) {
					$data = $valid_url['data'];
				}
				else {
					$errors[self::getHandle()]['url'] = $error;
				}
			}

			if(trim($fields[self::getHandle()]['xpath']) == '') {
				$errors[self::getHandle()]['xpath'] = __('This is a required field');
			}

			if(!is_numeric($fields[self::getHandle()]['cache'])) {
				$errors[self::getHandle()]['cache'] = __('Must be a valid number');
			}
			else if($fields[self::getHandle()]['cache'] < 1) {
				$errors[self::getHandle()]['cache'] = __('Must be greater than zero');
			}

			return empty($errors[self::getHandle()]);
		}

		/**
		 * Similar to the create function, edit takes `$handle` and `$parameters`
		 * to update an existing datasource. A third parameter, `$existing_handle` takes
		 * the current datasource handle, which will allow this function to know which
		 * file to load (and potentially rename if the handle has changed).
		 *
		 * @param string $file
		 *  The desired filename to save the `$parameters` too using this Datasource's
		 *  template.
		 * @param array $parameters
		 *  An associative array of parameters for this datasource, where the key
		 *  is the name of the setting.
		 * @param string $existing_file
		 *  The current file representing the Datasource prior to being saved
		 * @return boolean
		 */
		public static function save($file, array $parameters, array &$errors, $existing_file) {
			var_dump($file, $parameters, $existing_file);
			exit;

			// If errors occured, return false.
			//if(!empty(self::validate($parameters, $errors))) return false;
			$classname = Lang::createHandle($parameters['name'], NULL, '_', false, true, array('@^[^a-z\d]+@i' => '', '/[^\w-\.]/i' => ''));

			$data = array(
				$classname,
				// Name
				$parameters['name'],
				// Author
				Administration::instance()->Author->getFullName(),
				URL,
				Administration::instance()->Author->get('email'),
				// About
				DateTimeObj::getGMT('c'),
				'Symphony ' . Symphony::Configuration()->get('version', 'symphony')
			);

			// Load template, and insert parameters into it.
			$template = file_get_contents(self::getTemplate());


			var_dump($template, $data);

		}

		/**
		 * Given a Datasource handle, this function will remove the Datasource from
		 * the filesystem and from any pages that it was attached to.
		 *
		 * @see toolkit.DatasourceManager#__getHandleFromFilename
		 * @param string $handle
		 *  The handle of the datasource as returned by `DatasourceManager::__getHandleFromFilename`
		 * @return boolean
		 */
		public static function delete($name) {

		}

	/*-------------------------------------------------------------------------
		Execution
	-------------------------------------------------------------------------*/


	}

	return 'DatasourceRemote';