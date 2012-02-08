<?php

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
		foreach($this->dsParamFILTERS as $name => $uri) $instruction->setAttribute('xmlns' . ($name ? ":{$name}" : NULL), $uri);
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
	$timeout = (isset($this->dsParamTIMEOUT)) ? (int)max(1, $this->dsParamTIMEOUT) : 6;

	// Execute if the cache doesn't exist, or if it is old.
	if(
		(!is_array($cachedData) || empty($cachedData)) // There's no cache.
		|| (time() - $cachedData['creation']) > ($this->dsParamCACHE * 60) // The cache is old.
	){
		if(Mutex::acquire($cache_id, $timeout, TMP)) {
			$ch = new Gateway;
			$ch->init($this->dsParamURL);
			$ch->setopt('TIMEOUT', $timeout);

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
					$element = new XMLElement('errors');

					$result->setAttribute('valid', 'false');

					$result->appendChild(new XMLElement('error', __('Data returned is invalid.')));

					foreach($errors as $e) {
						if(strlen(trim($e['message'])) == 0) continue;
						$element->appendChild(new XMLElement('item', General::sanitize($e['message'])));
					}

					$result->appendChild($element);

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
				new XMLElement('error', __('The %s class failed to acquire a lock, check that %s exists and is writable.', array(
					'<code>Mutex</code>',
					'<code>' . TMP . '</code>'
				)))
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
			$element = new XMLElement('errors');
			foreach($proc->getError() as $e) {
				if(strlen(trim($e['message'])) == 0) continue;
				$element->appendChild(new XMLElement('item', General::sanitize($e['message'])));
			}
			$result->appendChild($element);
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
