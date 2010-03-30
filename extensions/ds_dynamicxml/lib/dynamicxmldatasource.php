<?php

	require_once CORE . '/class.cacheable.php';	
	require_once TOOLKIT . '/class.xslproc.php';
	require_once TOOLKIT . '/class.datasource.php';
	
	require_once 'class.gateway.php';
	
	Class DynamicXMLDataSource extends DataSource {

		public function __construct(){
			// Set Default Values
			$this->_about = new StdClass;
			$this->_parameters = (object)array(
				'cache-lifetime' => 60,
				'namespaces' => array(),
				'url' => NULL,
				'xpath' => '/',
				'root-element' => NULL,
				'redirect-on-empty' => false
			);
		}
		
		final public function type(){
			return 'ds_dynamicxml';
		}
		
		public function template(){
			return EXTENSIONS . '/ds_dynamicxml/templates/datasource.php';
		}
		
		public function save(MessageStack &$errors){

			if(strlen(trim($this->parameters()->url)) == 0){
				$errors->append('url', __('This is a required field'));
			}
			
			if(strlen(trim($this->parameters()->xpath)) == 0){
				$errors->append('xpath', __('This is a required field'));
			}
			
			if(!is_numeric($this->parameters()->{'cache-lifetime'})){
				$errors->append('cache-lifetime', __('Must be a valid number'));
			}
			
			elseif($this->parameters()->{'cache-lifetime'} <= 0){
				$errors->append('cache-lifetime', __('Must be greater than zero'));
			}
			
			else{
				$this->parameters()->{'cache-lifetime'} = (int)$this->parameters()->{'cache-lifetime'};
			}
			
			return parent::save($errors);
		}
		
		public function grab(){
			$result = null;
			
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
		
			$cache_id = md5($this->dsParamURL . serialize($this->dsParamFILTERS) . $this->dsParamXPATH);
		
			$cache = new Cacheable(Symphony::Database());
			
			$cachedData = $cache->check($cache_id);
			
			$writeToCache = false;
			$valid = true;
			$result = NULL;
			$creation = DateTimeObj::get('c');
			
			$timeout = 6;
			if(isset($this->dsParamTIMEOUT)){
				$timeout = (int)max(1, $this->dsParamTIMEOUT);
			}
			
			if((!is_array($cachedData) || empty($cachedData)) || (time() - $cachedData['creation']) > ($this->dsParamCACHE * 60)){
				if(Mutex::acquire($cache_id, $timeout, TMP)){
					
					$start = precision_timer();		
					
					$ch = new Gateway;
		
					$ch->init();
					$ch->setopt('URL', $this->dsParamURL);
					$ch->setopt('TIMEOUT', $timeout);
					$xml = $ch->exec();
					$writeToCache = true;
					
					$end = precision_timer('STOP', $start);
					
					$info = $ch->getInfoLast();
								
					Mutex::release($cache_id, TMP);
					
					$xml = trim($xml);
		
					if((int)$info['http_code'] != 200 || !preg_match('/(xml|plain|text)/i', $info['content_type'])){
						
						$writeToCache = false;
						
						if(is_array($cachedData) && !empty($cachedData)){ 
							$xml = trim($cachedData['data']);
							$valid = false;
							$creation = DateTimeObj::get('c', $cachedData['creation']);
						}
						
						else{
							$result = new XMLElement($this->dsParamROOTELEMENT);
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
		
					elseif(strlen($xml) > 0 && !General::validateXML($xml, $errors)){
							
						$writeToCache = false;
						
						if(is_array($cachedData) && !empty($cachedData)){ 
							$xml = trim($cachedData['data']);
							$valid = false;
							$creation = DateTimeObj::get('c', $cachedData['creation']);
						}

						else{
							$result = new XMLElement($this->dsParamROOTELEMENT);
							$result->setAttribute('valid', 'false');
							$result->appendChild(new XMLElement('error', __('XML returned is invalid.')));
						}
						
					}

					elseif(strlen($xml) == 0){
						$this->_force_empty_result = true;
					}
					
				}
				
				elseif(is_array($cachedData) && !empty($cachedData)){ 
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
			
				
			if(!$this->_force_empty_result && !is_object($result)):
			
				$result = new XMLElement($this->dsParamROOTELEMENT);
		
				$ret = XSLProc::transform($xml, $xsl);
		
				if(XSLProc::hasErrors()){
					
					$result->setAttribute('valid', 'false');
					$error = new XMLElement('error', __('XML returned is invalid.'));
					$result->appendChild($error);
					
					$messages = new XMLElement('messages');
					
					foreach($proc->getError() as $e){
						if(strlen(trim($e['message'])) == 0) continue;
						$messages->appendChild(new XMLElement('item', General::sanitize($e['message'])));
					}
					$result->appendChild($messages);
					
				}
				
				elseif(strlen(trim($ret)) == 0){
					$this->_force_empty_result = true;
				}
				
				else{
					
					if($writeToCache) $cache->write($cache_id, $xml);
					
					$result->setValue(self::CRLF . preg_replace('/([\r\n]+)/', '$1	', $ret));
					$result->setAttribute('status', ($valid === true ? 'fresh' : 'stale'));
					$result->setAttribute('creation', $creation);
					
				}
				
			endif;
			
			if ($this->_force_empty_result) $result = $this->emptyXMLSet();
			
			return $result;
		}
	}
