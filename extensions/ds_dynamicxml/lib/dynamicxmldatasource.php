<?php

	require_once CORE . '/class.cache.php';
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

		public function render(Register &$ParameterOutput){
			$result = null;

			$doc = new XMLDocument;
/*	TODO: Look over __processParametersInString function
			if(isset($this->parameters()->url)) $this->parameters()->url = $this->__processParametersInString($this->parameters()->url, $this->_env, true, true);
			if(isset($this->parameters()->xpath)) $this->parameters()->xpath = $this->__processParametersInString($this->parameters()->xpath, $this->_env);
*/
			$xsl = $doc->createElement('xsl:stylesheet');
			$xsl->setAttributeArray(array('version' => '1.0', 'xmlns:xsl' => 'http://www.w3.org/1999/XSL/Transform'));

			$output = $doc->createElement('xsl:output');
			$output->setAttributeArray(array('method' => 'xml', 'version' => '1.0', 'encoding' => 'utf-8', 'indent' => 'yes', 'omit-xml-declaration' => 'yes'));
			$xsl->appendChild($output);

			$template = $doc->createElement('xsl:template');
			$template->setAttribute('match', '/');

			$instruction = $doc->createElement('xsl:copy-of');

			## Namespaces
			if(is_array($this->parameters()->namespaces) && !empty($this->parameters()->namespaces)){
				foreach($this->parameters()->namespaces as $name => $uri) $instruction->setAttribute('xmlns' . ($name ? ":{$name}" : NULL), $uri);
			}

			## XPath
			$instruction->setAttribute('select', $this->parameters()->xpath);

			$template->appendChild($instruction);
			$xsl->appendChild($template);
			$doc->appendChild($xsl);

			$cache_id = md5($this->parameters()->url . serialize($this->parameters()->namespaces) . $this->parameters()->xpath);

			$cache = Cache::instance();
			$cachedData = $cache->read($cache_id);

			$writeToCache = false;
			$valid = true;
			$result = NULL;
			$creation = DateTimeObj::get('c');

			if(isset($this->parameters()->timeout)){
				$timeout = (int)max(1, $this->parameters()->timeout);
			}

			if((!is_array($cachedData) || empty($cachedData)) || (time() - $cachedData['creation']) > ($this->parameters()->{'cache-timeout'} * 60)){
				if(Mutex::acquire($cache_id, $timeout, TMP)){

					$start = precision_timer();

					$ch = new Gateway;

					$ch->init();
					$ch->setopt('URL', $this->parameters()->url);
					$ch->setopt('TIMEOUT', $this->parameters()->timeout);
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
							$result = $doc->createElement($this->parameters()->{'root-element'});
							$result->setAttribute('valid', 'false');

							if($end > $timeout){
								$result->appendChild(
									$doc->createElement('error',
										sprintf('Request timed out. %d second limit reached.', $timeout)
									)
								);
							}
							else{
								$result->appendChild(
									$doc->createElement('error',
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
							$result = $doc->createElement(
								$this->parameters()->{'root-element'},
								$doc->createElement('error', __('XML returned is invalid.')),
								array('valid' => 'false')
							);
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

			if(!$this->_force_empty_result && !is_object($result)) {

				$result = new XMLDocument;
				$root =	$result->createElement($this->parameters()->{'root-element'});

				$ret = XSLProc::transform($xml, $doc->saveXML());

				if(XSLProc::hasErrors()){

					$root->setAttribute('valid', 'false');
					$root->appendChild(
						$result->createElement('error', __('XML returned is invalid.'))
					);

					$messages = $result->createElement('messages');

					foreach(XSLProc::getErrors() as $e){
						if(strlen(trim($e->message)) == 0) continue;
						$messages->appendChild(
							$result->createElement('item', General::sanitize($e->message))
						);
					}
					$root->appendChild($messages);

				}

				elseif(strlen(trim($ret)) == 0){
					$this->_force_empty_result = true;
				}

				else{

					if($writeToCache) $cache->write($cache_id, $xml);

					$fragment = $result->createDocumentFragment();
					$fragment->appendXML($ret);

					$root->appendChild($fragment);
					$root->setAttribute('status', ($valid === true ? 'fresh' : 'stale'));
					$root->setAttribute('creation', $creation);

				}

				$result->appendChild($root);

			}

			if ($this->_force_empty_result) $result = $this->emptyXMLSet();

			return $result;
		}
	}
