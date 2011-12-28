<?php

	Interface iDatasource {

		public static function getTemplate();

		public static function buildEditor(XMLElement $wrapper, array &$errors = array(), array $settings = null, $handle = null);

		public static function prepare(array $fields, array $parameters, $template);

		public function grab(array $param_pool);

	}

	require_once TOOLKIT . '/class.datasource.php';

	Class DatasourceRemote extends DataSource implements iDatasource {

		private static $url_result = null;

		protected $parameters = array();

		public function __construct($env = array(), $process_params = false) {
			parent::__construct(null, $env, $process_params);

			$this->parameters = array(
				'url' => '',
				'format' => 'xml',
				'xpath' => '/',
				'cache' => 30,
				'timeout' => 6,
				'namespaces' => array()
			);
		}

	/*-------------------------------------------------------------------------
		Utilities
	-------------------------------------------------------------------------*/

		/**
		 * Returns the Datasource name.
		 *
		 * @return string
		 */
		public static function getName() {
			return __('Remote Datasource');
		}

		/**
		 * Returns a safe handle used by the Datasource Editor to distinguish
		 * the settings for this datasource.
		 *
		 * @return string
		 */
		public static function getHandle() {
			return 'RemoteDatasource';
		}


		public function getSource(){
			return __CLASS__;
		}

		/**
		 * Returns the path to the Datasource template
		 *
		 * @return string
		 */
		public static function getTemplate(){
			return EXTENSIONS . '/datasource_remote/templates/blueprints.datasource.tpl';
		}

		/**
		 * Returns the source value for display in the Datasources index
		 *
		 * @param string $file
		 *  The path to the Datasource file
		 * @return string
		 */
		public static function getSourceColumn($file) {
			// @todo Load the file and return the URL.
			return 'RemoteDatasource';
		}

		/**
		 * Given a `$url` and `$timeout`, this function will use the `Gateway`
		 * class to determine that it is a valid URL and returns successfully
		 * before the `$timeout`. If it does not, an error message will be
		 * returned, otherwise true.
		 *
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
		public static function isValidURL($url, $timeout = 6, $fetch_URL = false) {
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
		 * Builds the namespaces out to be saved in the Datasource file
		 *
		 * @param array $namespaces
		 *  An associative array of where the key is the namespace prefix
		 *  and the value is the namespace URL.
		 * @param string $template
		 *  The template file, as defined by `getTemplate()`
		 * @return string
		 *  The template injected with the Namespaces (if any).
		 */
		public static function injectNamespaces(array $namespaces, &$template) {
			if(empty($namespaces)) return;

			$placeholder = '<!-- NAMESPACES -->';
			$string = 'public $dsParamNAMESPACES = array(' . PHP_EOL;

			foreach($filters as $key => $val){
				if(trim($val) == '') continue;
				$string .= "\t\t\t\t'$key' => '" . addslashes($val) . "'," . PHP_EOL;
			}

			$string .= "\t\t);" . PHP_EOL . "\t\t" . $placeholder;

			$shell = str_replace($placeholder, trim($string), $shell);
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
			if(trim($settings[self::getHandle()]['url']) == '') {
				$errors[self::getHandle()]['url'] = __('This is a required field');
			}

			// Use the TIMEOUT that was specified by the user for a real world indication
			$timeout = isset($settings[self::getHandle()]['timeout'])
				? (int)$settings[self::getHandle()]['timeout']
				: 6;

			// If there is a parameter in the URL, we can't validate the existence of the URL
			// as we don't have the environment details of where this datasource is going
			// to be executed.
			if(!preg_match('@{([^}]+)}@i', $settings[self::getHandle()]['url'])) {
				$valid_url = self::isValidURL($settings[self::getHandle()]['url'], $timeout, $error);

				if(is_array($valid_url)) {
					self::$url_result = $valid_url['data'];
				}
				else {
					$errors[self::getHandle()]['url'] = $error;
				}
			}

			if(trim($settings[self::getHandle()]['xpath']) == '') {
				$errors[self::getHandle()]['xpath'] = __('This is a required field');
			}

			if(!is_numeric($settings[self::getHandle()]['cache'])) {
				$errors[self::getHandle()]['cache'] = __('Must be a valid number');
			}
			else if($settings[self::getHandle()]['cache'] < 1) {
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
		 * @param array $fields
		 *  An associative array of settings for this datasource, where the key
		 *  is the name of the setting. These are user defined through the Datasource
		 *  Editor.
		 * @param array $params
		 *  An associative array of parameters for this datasource, where the key
		 *  is the name of the parameter.
		 * @param string $template
		 *  The template file, which has already been altered by Symphony to remove
		 *  any named tokens (ie. `<!-- CLASS NAME -->`).
		 * @return string
		 *  The completed template, ready to be saved.
		 */
		public static function prepare(array $fields, array $params, $template) {
			// Automatically detect namespaces
			if(!is_null(self::$url_result)) {
				preg_match_all('/xmlns:([a-z][a-z-0-9\-]*)="([^\"]+)"/i', self::$url_result, $matches);

				if(!is_array($fields[self::getHandle()]['namespace'])) {
					$fields[self::getHandle()]['namespace'] = array();
				}

				if (isset($matches[2][0])) {
					$detected_namespaces = array();

					foreach ($fields[self::getHandle()]['namespace'] as $index => $namespace) {
						$detected_namespaces[] = $namespace['name'];
						$detected_namespaces[] = $namespace['uri'];
					}

					foreach ($matches[2] as $index => $uri) {
						$name = $matches[1][$index];

						if (in_array($name, $detected_namespaces) or in_array($uri, $detected_namespaces)) continue;

						$detected_namespaces[] = $name;
						$detected_namespaces[] = $uri;

						$fields[self::getHandle()]['namespace'][] = array(
							'name' => $name,
							'uri' => $uri
						);
					}
				}
			}

			$namespaces = array();
			if(is_array($parameters[self::getHandle()]['namespace'])) {
				foreach($parameters[self::getHandle()]['namespace'] as $index => $data) {
					$namespaces[$data['name']] = $data['uri'];
				}
			}
			self::injectNamespaces($namespaces, $template);

			$timeout = isset($fields[self::getHandle()]['timeout']) ? (int)$fields[self::getHandle()]['timeout'] : 6;

			return sprintf($template,
				$params['rootelement'], // rootelement
				$fields[self::getHandle()]['url'], // url
				$fields[self::getHandle()]['format'], // format
				$fields[self::getHandle()]['xpath'], // xpath
				$fields[self::getHandle()]['cache'], // cache
				$timeout// timeout
			);
		}

		public function load(Datasource $existing) {
			$fields = array();

			$fields[self::getHandle()]['namespace'] = $existing->dsParamFILTERS;
			$fields[self::getHandle()]['url'] = $existing->dsParamURL;
			$fields[self::getHandle()]['xpath'] = $existing->dsParamXPATH;
			$fields[self::getHandle()]['cache'] = $existing->dsParamCACHE;
			$fields[self::getHandle()]['format'] = $existing->dsParamFORMAT;
			$fields[self::getHandle()]['timeout'] = isset($existing->dsParamTIMEOUT) ? $existing->dsParamTIMEOUT : 6;

			return $fields;
		}

	/*-------------------------------------------------------------------------
		Execution
	-------------------------------------------------------------------------*/

		public function grab(array $param_pool) {
			$result = new XMLElement($this->dsParamROOTELEMENT);

			try {
				require_once(TOOLKIT . '/class.gateway.php');
				require_once(TOOLKIT . '/class.xsltprocess.php');
				require_once(CORE . '/class.cacheable.php');

				$this->dsParamURL = $this->parseParamURL($this->dsParamURL);

				if(isset($this->dsParamXPATH)) $this->dsParamXPATH = $this->__processParametersInString($this->dsParamXPATH, $this->_env);

				if(!isset($this->dsParamFORMAT)) $this->dsParamFORMAT = 'xml';

				$stylesheet = new XMLElement('xsl:stylesheet');
				$stylesheet->setAttributeArray(array('version' => '1.0', 'xmlns:xsl' => 'http://www.w3.org/1999/XSL/Transform'));

				$output = new XMLElement('xsl:output');
				$output->setAttributeArray(array('method' => 'xml', 'version' => '1.0', 'encoding' => 'utf-8', 'indent' => 'yes', 'omit-xml-declaration' => 'yes'));
				$stylesheet->appendChild($output);

				$template = new XMLElement('xsl:template');
				$template->setAttribute('match', '/');

				$instruction = new XMLElement('xsl:copy-of');

				// Namespaces
				if(isset($this->dsParamFILTERS) && is_array($this->dsParamFILTERS)){
					foreach($this->dsParamFILTERS as $name => $uri) {
						$instruction->setAttribute('xmlns' . ($name ? ":{$name}" : NULL), $uri);
					}
				}

				// XPath
				$instruction->setAttribute('select', $this->dsParamXPATH);

				$template->appendChild($instruction);
				$stylesheet->appendChild($template);

				$stylesheet->setIncludeHeader(true);

				$xsl = $stylesheet->generate(true);

				$cache_id = md5($this->dsParamURL . serialize($this->dsParamFILTERS) . $this->dsParamXPATH . $this->dsParamFORMAT);

				$cache = new Cacheable(Symphony::Database());

				$cachedData = $cache->check($cache_id);
				$writeToCache = false;
				$valid = true;
				$creation = DateTimeObj::get('c');

				// Execute if the cache doesn't exist, or if it is old.
				if(
					(!is_array($cachedData) || empty($cachedData)) // There's no cache.
					|| (time() - $cachedData['creation']) > ($this->dsParamCACHE * 60) // The cache is old.
				){
					if(Mutex::acquire($cache_id, $this->dsParamTIMEOUT, TMP)) {
						$ch = new Gateway;
						$ch->init($this->dsParamURL);
						$ch->setopt('TIMEOUT', $this->dsParamTIMEOUT);

						// Set the approtiate Accept: headers depending on the format of the URL.
						if($this->dsParamFORMAT == 'xml') {
							$ch->setopt('HTTPHEADER', array('Accept: text/xml, */*'));
						}
						else {
							$ch->setopt('HTTPHEADER', array('Accept: application/json, */*'));
						}

						$data = $ch->exec();
						$info = $ch->getInfoLast();

						Mutex::release($cache_id, TMP);

						$data = trim($data);
						$writeToCache = true;

						// Handle any response that is not a 200, or the content type does not include XML, JSON, plain or text
						if((int)$info['http_code'] != 200 || !preg_match('/(xml|json|plain|text)/i', $info['content_type'])){
							$writeToCache = false;

							$result->setAttribute('valid', 'false');

							// 28 is CURLE_OPERATION_TIMEOUTED
							if($info['curl_error'] == 28) {
								$result->appendChild(
									new XMLElement('error',
										sprintf('Request timed out. %d second limit reached.', $timeout)
									)
								);
							}
							else{
								$result->appendChild(
									new XMLElement('error',
										sprintf('Status code %d was returned. Content-type: %s', $info['http_code'], $info['content_type'])
									)
								);
							}

							return $result;
						}

						// Handle where there is `$data`
						else if(strlen($data) > 0) {
							// If it's JSON, convert it to XML
							if($this->dsParamFORMAT == 'json') {
								try {
									require_once TOOLKIT . '/class.json.php';
									$data = JSON::convertToXML($data);
								}
								catch (Exception $ex) {
									$writeToCache = false;
									$errors = array(
										array('message' => $ex->getMessage())
									);
								}
							}
							// If the XML doesn't validate..
							else if(!General::validateXML($data, $errors, false, new XsltProcess)) {
								$writeToCache = false;
							}

							// If the `$data` is invalid, return a result explaining why
							if($writeToCache === false) {
								$result = new XMLElement('errors');

								$result->setAttribute('valid', 'false');

								$result->appendChild(new XMLElement('error', __('Data returned is invalid.')));

								foreach($errors as $e) {
									if(strlen(trim($e['message'])) == 0) continue;
									$result->appendChild(new XMLElement('item', General::sanitize($e['message'])));
								}

								$result->appendChild($result);

								return $result;
							}
						}
						// If `$data` is empty, set the `force_empty_result` to true.
						else if(strlen($data) == 0){
							$this->_force_empty_result = true;
						}
					}

					// Failed to acquire a lock
					else {
						$result->appendChild(
							new XMLElement('error', __('The %s class failed to acquire a lock.', array('<code>Mutex</code>')))
						);
					}
				}

				// The cache is good, use it!
				else {
					$data = trim($cachedData['data']);
					$creation = DateTimeObj::get('c', $cachedData['creation']);
				}

				// If `$writeToCache` is set to false, invalidate the old cache if it existed.
				if(is_array($cachedData) && !empty($cachedData) && $writeToCache === false) {
					$data = trim($cachedData['data']);
					$valid = false;
					$creation = DateTimeObj::get('c', $cachedData['creation']);

					if(empty($data)) $this->_force_empty_result = true;
				}

				// If `force_empty_result` is false and `$result` is an instance of
				// XMLElement, build the `$result`.
				if(!$this->_force_empty_result && is_object($result)) {
					$proc = new XsltProcess;
					$ret = $proc->process($data, $xsl);

					if($proc->isErrors()) {
						$result->setAttribute('valid', 'false');
						$error = new XMLElement('error', __('Transformed XML is invalid.'));
						$result->appendChild($error);
						$result = new XMLElement('errors');
						foreach($proc->getError() as $e) {
							if(strlen(trim($e['message'])) == 0) continue;
							$result->appendChild(new XMLElement('item', General::sanitize($e['message'])));
						}
						$result->appendChild($result);
					}

					else if(strlen(trim($ret)) == 0) {
						$this->_force_empty_result = true;
					}

					else {
						if($writeToCache) $cache->write($cache_id, $data, $this->dsParamCACHE);

						$result->setValue(PHP_EOL . str_repeat("\t", 2) . preg_replace('/([\r\n]+)/', "$1\t", $ret));
						$result->setAttribute('status', ($valid === true ? 'fresh' : 'stale'));
						$result->setAttribute('creation', $creation);
					}
				}
			}
			catch(Exception $e){
				$result->appendChild(new XMLElement('error', $e->getMessage()));
				return $result;
			}

			return $result;
		}

	}

	return 'DatasourceRemote';