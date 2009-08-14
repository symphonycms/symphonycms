<?php

	require_once(TOOLKIT . '/class.gateway.php');
	require_once(TOOLKIT . '/class.xsltprocess.php');
	require_once(CORE . '/class.cacheable.php');
	
	if(isset($this->dsParamURL)) $this->dsParamURL = $this->__processParametersInString($this->dsParamURL, $this->_env, true, true);
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
		foreach($this->dsParamFILTERS as $name => $uri) $instruction->setAttribute('xmlns' . ($name ? ":$name" : NULL), $uri);
	}

	## XPath
	$instruction->setAttribute('select', $this->dsParamXPATH);

	$template->appendChild($instruction);
	$stylesheet->appendChild($template);

	$stylesheet->setIncludeHeader(true);

	$xsl = $stylesheet->generate(true);

	$proc =& new XsltProcess;

	$cache_id = md5($this->dsParamURL . serialize($this->dsParamFILTERS) . $this->dsParamXPATH);

	$cache = new Cacheable($this->_Parent->Database);
	
	$cachedData = $cache->check($cache_id);
	
	$writeToCache = false;
	$valid = true;
	$result = NULL;
	$creation = DateTimeObj::get('c');
	
	if(!$cachedData || (time() - $cachedData['creation']) > ($this->dsParamCACHE * 60)){

		if(Mutex::acquire($cache_id, 6, TMP)){
		
			$ch = new Gateway;

			$ch->init();
			$ch->setopt('URL', $this->dsParamURL);
			$ch->setopt('TIMEOUT', 6);
			$xml = $ch->exec();	
			$writeToCache = true;
			
			Mutex::release($cache_id, TMP);
			
			$xml = trim($xml);
			
			if(!empty($xml)){
				$valid = General::validateXML($xml, $errors, false, $proc);
				if(!$valid){
					if($cachedData) $xml = $cachedData['data'];
					else{
						$result = new XMLElement($this->dsParamROOTELEMENT);
						$result->setAttribute('valid', 'false');
						$result->appendChild(new XMLElement('error', __('XML returned is invalid.')));
					}
				}
			}
			
			else $this->_force_empty_result = true;
			
		}
		
		elseif($cachedData){ 
			$xml = trim($cachedData['data']);
			$valid = false;
			if(empty($xml)) $this->_force_empty_result = true;
		}
		
		else $this->_force_empty_result = true;
		
	}
	
	else $xml = $cachedData['data'];

		
	if(!$this->_force_empty_result && !is_object($result)):
	
		$result = new XMLElement($this->dsParamROOTELEMENT);

		$ret = $proc->process($xml, $xsl);
	
		if($proc->isErrors()){
			$result->setAttribute('valid', 'false');
			$result->appendChild(new XMLElement('error', __('XML returned is invalid.')));
		}
		
		elseif(trim($ret) == '') $this->_force_empty_result = true;
		
		else{
			
			if($writeToCache) $cache->write($cache_id, $xml);
			
			$result->setValue(self::CRLF . preg_replace('/([\r\n]+)/', '$1	', $ret));
			$result->setAttribute('status', ($valid ? 'fresh' : 'stale'));
			$result->setAttribute('creation', $creation);
			
		}
		
	endif;
?>