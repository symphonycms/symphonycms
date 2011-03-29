<?php

	require_once(TOOLKIT . '/class.gateway.php');
	require_once(TOOLKIT . '/class.xsltprocess.php');
	require_once(CORE . '/class.cacheable.php');

	if(!function_exists('findParametersInString')){
		// This function finds all the params and flags any with :encoded. An array is returned
		// to be iterated over
		function findParametersInString($value){
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

	if(isset($this->dsParamURL)){
		$params = findParametersInString($this->dsParamURL);
		foreach($params as $key => $info){
			$replacement = $this->__processParametersInString($info['param'], $this->_env, false);
			if($info['encode'] == true){
				$replacement = urlencode($replacement);
			}
			$this->dsParamURL = str_replace("{{$key}}", $replacement, $this->dsParamURL);
		}
	}

	if(isset($this->dsParamXPATH)) $this->dsParamXPATH = $this->__processParametersInString($this->dsParamXPATH, $this->_env);


	$stylesheet = new XMLElement('xsl:stylesheet');
	$stylesheet->setAttributeArray(array('version' => '1.0', 'xmlns:xsl' => 'http://www.w3.org/1999/XSL/Transform'));

	$output = new XMLElement('xsl:output');
	$output->setAttributeArray(array('method' => 'xml', 'version' => '1.0', 'encoding' => 'utf-8', 'indent' => 'yes', 'omit-xml-declaration' => 'yes'));
	$stylesheet->appendChild($output);

	$template = new XMLElement('xsl:template');
	$template->setAttribute('match', '/');

	$instruction = new XMLElement('xsl:copy-of');

	## Namespaces
	if(isset($this->dsParamFILTERS) && is_array($this->dsParamFILTERS)){
		foreach($this->dsParamFILTERS as $name => $uri) $instruction->setAttribute('xmlns' . ($name ? ":{$name}" : NULL), $uri);
	}

	## XPath
	$instruction->setAttribute('select', $this->dsParamXPATH);

	$template->appendChild($instruction);
	$stylesheet->appendChild($template);

	$stylesheet->setIncludeHeader(true);

	$xsl = $stylesheet->generate(true);

	$cache_id = md5($this->dsParamURL . serialize($this->dsParamFILTERS) . $this->dsParamXPATH);

	$cache = new Cacheable(Symphony::Database());

	$cachedData = $cache->check($cache_id);
	$writeToCache = false;
	$valid = true;
	$creation = DateTimeObj::get('c');
	$timeout = (isset($this->dsParamTIMEOUT)) ? (int)max(1, $this->dsParamTIMEOUT) : 6;

	if((!is_array($cachedData) || empty($cachedData)) || (time() - $cachedData['creation']) > ($this->dsParamCACHE * 60)){
		if(Mutex::acquire($cache_id, $timeout, TMP)){

			$start = precision_timer();

			$ch = new Gateway;

			$ch->init();
			$ch->setopt('URL', $this->dsParamURL);
			$ch->setopt('TIMEOUT', $timeout);
			$xml = $ch->exec();
			$writeToCache = true;

			$end = precision_timer('stop', $start);

			$info = $ch->getInfoLast();

			Mutex::release($cache_id, TMP);

			$xml = trim($xml);

			// Handle any response that is not a 200, or the content type does not include xml, plain or text
			if((int)$info['http_code'] != 200 || !preg_match('/(xml|plain|text)/i', $info['content_type'])){
				$writeToCache = false;

				if(is_array($cachedData) && !empty($cachedData)){
					$xml = trim($cachedData['data']);
					$valid = false;
					$creation = DateTimeObj::get('c', $cachedData['creation']);
				}
				else{
					$result->setAttribute('valid', 'false');

					if($end > $timeout){
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
			// Handle where there is `$xml` and the XML is valid
			else if(strlen($xml) > 0 && !General::validateXML($xml, $errors, false, new XsltProcess)){
				$writeToCache = false;

				if(is_array($cachedData) && !empty($cachedData)){
					$xml = trim($cachedData['data']);
					$valid = false;
					$creation = DateTimeObj::get('c', $cachedData['creation']);
				}
				else{
					$result->setAttribute('valid', 'false');
					$result->appendChild(new XMLElement('error', __('XML returned is invalid.')));
					$element = new XMLElement('errors');
					foreach($errors as $e) {
						if(strlen(trim($e['message'])) == 0) continue;
						$element->appendChild(new XMLElement('item', General::sanitize($e['message'])));
					}
					$result->appendChild($element);
				}
			}
			// If `$xml` is empty, set the `force_empty_result` to true.
			elseif(strlen($xml) == 0){
				$this->_force_empty_result = true;
			}
		}

		else if(is_array($cachedData) && !empty($cachedData)){
			$xml = trim($cachedData['data']);
			$valid = false;
			$creation = DateTimeObj::get('c', $cachedData['creation']);
			if(empty($xml)) $this->_force_empty_result = true;
		}

		else $this->_force_empty_result = true;

	}

	else{
		$xml = trim($cachedData['data']);
		$creation = DateTimeObj::get('c', $cachedData['creation']);
	}

	// If `force_empty_result` is false and `$result` is not an instance of
	// XMLElement, build the `$result`.
	if(!$this->_force_empty_result && is_object($result)) {

		$proc = new XsltProcess;
		$ret = $proc->process($xml, $xsl);

		if($proc->isErrors()){
			$result->setAttribute('valid', 'false');
			$error = new XMLElement('error', __('XML returned is invalid.'));
			$result->appendChild($error);
			$element = new XMLElement('errors');
			foreach($errors as $e) {
				if(strlen(trim($e['message'])) == 0) continue;
				$element->appendChild(new XMLElement('item', General::sanitize($e['message'])));
			}
			$result->appendChild($element);
		}

		else if(strlen(trim($ret)) == 0){
			$this->_force_empty_result = true;
		}

		else{
			if($writeToCache) $cache->write($cache_id, $xml);

			$result->setValue(self::CRLF . preg_replace('/([\r\n]+)/', '$1	', $ret));
			$result->setAttribute('status', ($valid === true ? 'fresh' : 'stale'));
			$result->setAttribute('creation', $creation);
		}

	}
