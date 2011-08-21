<?php

	require_once(TOOLKIT . '/class.gateway.php');
	require_once(TOOLKIT . '/class.xsltprocess.php');
	require_once(CORE . '/class.cacheable.php');

	if(!class_exists('RemoteJSONException')) {
		class RemoteJSONException extends Exception {
			public function __construct($message, $code = null, Exception $ex = null) {
				switch ($code) {
					case JSON_ERROR_NONE:
						$message = __('No errors');
					break;
					case JSON_ERROR_DEPTH:
						$message = __('Maximum stack depth exceeded');
					break;
					case JSON_ERROR_STATE_MISMATCH:
						$message = __('Underflow or the modes mismatch');
					break;
					case JSON_ERROR_CTRL_CHAR:
						$message = __('Unexpected control character found');
					break;
					case JSON_ERROR_SYNTAX:
						$message = __('Syntax error, malformed JSON');
					break;
					case JSON_ERROR_UTF8:
						$message = __('Malformed UTF-8 characters, possibly incorrectly encoded');
					break;
				}

				parent::__construct($message, $code, $ex);
			}
		}
	}

	/**
	 * Takes a JSON formatted string and outputs it as XML.
	 *
	 * @author Brent Burgoyne
	 * @link http://brentburgoyne.com
	 */
	if(!class_exists('JSONToXML')) {
		class JSONToXML {
			private static $dom;

			public static function convert($json, $asXML = true) {
				self::$dom = new DomDocument('1.0', 'utf-8');
				self::$dom->formatOutput = TRUE;

				// remove callback functions from JSONP
				if (preg_match('/(\{|\[).*(\}|\])/s', $json, $matches)) {
					$json = $matches[0];
				}
				else {
					throw new RemoteJSONException(__("JSON not formatted correctly"));
				}

				$data = json_decode($json);
				if(json_last_error() !== JSON_ERROR_NONE) {
					throw new RemoteJSONException(__("JSON not formatted correctly"), json_last_error());
				}

				$data_element = self::_process($data, self::$dom->createElement('data'));
				self::$dom->appendChild($data_element);

				if($asXML) {
					return self::$dom->saveXML();
				}
				else {
					return self::$dom->saveXML(self::$dom->documentElement);
				}
			}

			private static function _process($data, $element) {
				if (is_array($data)) {
					foreach ($data as $item) {
						$item_element = self::_process($item, self::$dom->createElement('item'));
						$element->appendChild($item_element);
					}
				}
				else if (is_object($data)) {
					$vars = get_object_vars($data);
					foreach ($vars as $key => $value) {
						$key = self::_valid_element_name($key);

						$var_element = self::_process($value, $key);
						$element->appendChild($var_element);
					}
				}
				else {
					$element->appendChild(self::$dom->createTextNode($data));
				}

				return $element;
			}

			/**
			 * If the $name is not a valid QName it will be ignored in favour
			 * of using 'key'. In that scenario, the $name will be set as the
			 * value attribute of that element.
			 *
			 * @param $name
			 * @return DOMElement
			 */
			private static function _valid_element_name($name) {
				if(Lang::isUnicodeCompiled()) {
					$valid_name = preg_match('/^[\p{L}]([0-9\p{L}\.\-\_]+)?$/u', $name);
				}
				else {
					$valid_name = preg_match('/^[A-z]([\w\d-_\.]+)?$/i', $name);
				}

				if($valid_name) {
					$xKey = self::$dom->createElement(
						Lang::createHandle($name)
					);
				}
				else {
					$xKey = self::$dom->createElement('key');
				}

				$xKey->setAttribute('handle', Lang::createHandle($name));
				$xKey->setAttribute('value', General::sanitize($name));

				return $xKey;
			}
		}
	}

	if(!function_exists('findParametersInString')) {
		// This function finds all the params and flags any with :encoded. An array is returned
		// to be iterated over
		function findParametersInString($value) {
			$result = array();

			if(preg_match_all('@{([^}]+)}@i', $value, $matches, PREG_SET_ORDER)){
				foreach($matches as $m){
					$result[$m[1]] = array(
						'param' => preg_replace('/:encoded$/', NULL, $m[1]),
						'encode' => preg_match('/:encoded$/', $m[1])
					);
				}
			}

			return $result;
		}
	}

	if(isset($this->dsParamURL)) {
		$params = findParametersInString($this->dsParamURL);
		foreach($params as $key => $info){
			$replacement = $this->__processParametersInString($info['param'], $this->_env, false);
			if($info['encode'] == true){
				$replacement = urlencode($replacement);
			}
			$this->dsParamURL = str_replace("{{$key}}", $replacement, $this->dsParamURL);
		}
	}

	// Check the Cache to see if there is already an existing result
	$cache_id = md5($this->dsParamURL . serialize($this->dsParamFILTERS) . $this->dsParamXPATH);

	$cache = new Cacheable(Symphony::Database());
	$cachedData = $cache->check($cache_id);
	$writeToCache = false;
	$valid = true;
	$creation = DateTimeObj::get('c');
	$timeout = (isset($this->dsParamTIMEOUT)) ? (int)max(1, $this->dsParamTIMEOUT) : 6;

	if((!is_array($cachedData) || empty($cachedData)) || (time() - $cachedData['creation']) > ($this->dsParamCACHE * 60)) {
		if(Mutex::acquire($cache_id, $timeout, TMP)){
			$ch = new Gateway;
			$ch->init();
			$ch->setopt('URL', $this->dsParamURL);
			$ch->setopt('TIMEOUT', $timeout);
			$ch->setopt('HTTPHEADER', array('Accept: application/json, */*'));
			$data = $ch->exec();
			$writeToCache = true;

			$info = $ch->getInfoLast();

			Mutex::release($cache_id, TMP);

			$data = trim($data);

			// Handle any response that is not a 200, or the content type does not include xml, plain or text
			// So really, handle the errors when no data is recieved.
			if((int)$info['http_code'] != 200 || !preg_match('/(json|plain|text)/i', $info['content_type'])){
				$writeToCache = false;

				// If the last Cache data exists, invalidate it.
				if(is_array($cachedData) && !empty($cachedData)){
					$data = $cachedData['data'];
					$valid = false;
					$creation = DateTimeObj::get('c', $cachedData['creation']);
				}
				// There is no previous cache, so something has just plain gone wrong
				// Either the request timed out, or the response wasn't what we expected
				else {
					$result->setAttribute('valid', 'false');

					if($info['total_time'] > $timeout){
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
			}

			// Handle where there is `$data`
			else if(strlen($data) > 0) {
				$writeToCache = false;

				// We have data, and we were expecting JSON but we are going to make it into
				// XML so that we can use XPath and just generally work with it in a standard
				// way.
				try {
					if(is_array($cachedData) && !empty($cachedData)){
						$data = $cachedData['data'];
						$valid = false;
						$creation = DateTimeObj::get('c', $cachedData['creation']);
					}
					else {
						$data = JSONToXML::convert($data);
					}
				}
				catch (Exception $ex) {
					$result->setAttribute('valid', 'false');
					$result->appendChild(
						new XMLElement('error', $ex->getMessage())
					);
				}
			}
			// If `$data` is empty, set the `force_empty_result` to true.
			else if(strlen($data) == 0) {
				$this->_force_empty_result = true;
			}
		}
		// There is cached data for us to use which hasn't expired
		else if(is_array($cachedData) && !empty($cachedData)){
			$data = $cachedData['data'];
			$valid = false;
			$creation = DateTimeObj::get('c', $cachedData['creation']);
			if(empty($data)) $this->_force_empty_result = true;
		}

		else {
			$this->_force_empty_result = true;
		}
	}

	else{
		$data = trim($cachedData['data']);
		$creation = DateTimeObj::get('c', $cachedData['creation']);
	}

	// If `force_empty_result` is false and `$result` is not an instance of
	// XMLElement, build the `$result`.
	if(!$this->_force_empty_result && is_object($result)) {
		$proc = new XsltProcess;

		// Build Stylesheet to get desired elements from result
		$stylesheet = new XMLElement('xsl:stylesheet');
		$stylesheet->setAttributeArray(array('version' => '1.0', 'xmlns:xsl' => 'http://www.w3.org/1999/XSL/Transform'));

		$output = new XMLElement('xsl:output');
		$output->setAttributeArray(array('method' => 'xml', 'version' => '1.0', 'encoding' => 'utf-8', 'indent' => 'yes', 'omit-xml-declaration' => 'yes'));
		$stylesheet->appendChild($output);

		$template = new XMLElement('xsl:template');
		$template->setAttribute('match', '/');

		if(isset($this->dsParamXPATH) && $this->dsParamXPATH !== '/') {
			$this->dsParamXPATH = '/data' . $this->__processParametersInString($this->dsParamXPATH, $this->_env);
		}
		else {
			$this->dsParamXPATH = '/data/*';
		}
		$instruction = new XMLElement('xsl:copy-of');
		$instruction->setAttribute('select', $this->dsParamXPATH);

		$template->appendChild($instruction);
		$stylesheet->appendChild($template);

		$stylesheet->setIncludeHeader(true);
		$xsl = $stylesheet->generate(true);

		$ret = $proc->process($data, $xsl);

		if($proc->isErrors()){
			$result->setAttribute('valid', 'false');
			$error = new XMLElement('error', __('XML returned is invalid.'));
			$result->appendChild($error);
			$element = new XMLElement('errors');
			foreach($proc->getError() as $e) {
				if(strlen(trim($e['message'])) == 0) continue;
				$element->appendChild(new XMLElement('item', General::sanitize($e['message'])));
			}
			$result->appendChild($element);
		}

		else if(strlen(trim($ret)) == 0){
			$this->_force_empty_result = true;
		}

		else{
			if($writeToCache) $cache->write($cache_id, trim($data));

			$result->setValue(self::CRLF . preg_replace('/([\r\n]+)/', '$1	', $ret));
			$result->setAttribute('status', ($valid === true ? 'fresh' : 'stale'));
			$result->setAttribute('creation', $creation);
		}
	}
