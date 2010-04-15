<?php

	require_once(TOOLKIT . '/class.documentheaders.php');
	require_once(TOOLKIT . '/class.xslproc.php');

	Class XSLTPage{
		
		protected $xml;
		protected $xsl;
		protected $parameters;
		protected $registered_php_functions;

		public $Headers;
		
		public function __construct(){
			if(!XSLProc::isXSLTProcessorAvailable()) throw new Exception(__('No suitable XSLT processor was found.'));
			$this->registered_php_functions = array();
			$this->Headers = new DocumentHeaders;
		}
		
		public function setXML($xml, $file=false){
			$this->xml = ($file ? @file_get_contents($xml) : $xml);
		}
		
		public function setXSL($xsl, $file=false){
			$this->xsl = ($file ? @file_get_contents($xsl) : $xsl);
		}
		
		public function getXML(){
			return $this->xml;
		}
		
		public function getXSL(){
			return $this->xsl;
		}
		
		public function setRuntimeParam($param){
			$this->parameters = str_replace("'", "&apos;", $param);
		}
		
		public function getError(){
			return $this->Proc->getError();
		}
		
		public function registerPHPFunction($function){
			if(!is_array($this->registered_php_functions)) $this->registered_php_functions = array();
				
			if(is_array($function)) $this->registered_php_functions += $function;
			else $this->registered_php_functions[] = $function;
		}
		
		public function generate(){
			
			$result = XSLProc::transform($this->xml, $this->xsl, XSLProc::XML, $this->parameters, $this->registered_php_functions);
			
			if(XSLProc::hasErrors()) return false;
			
			return $result;
		}
			
	}
