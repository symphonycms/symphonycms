<?php

	require_once(TOOLKIT . '/class.page.php');
	require_once(TOOLKIT . '/class.xslproc.php');

	Class XSLTPage extends Page{
		
		protected $_xml;
		protected $_xsl;
		protected $_parameters;
		protected $_registered_php_functions;
		
		public function __construct(){
			
			if(!XSLProc::isXSLTProcessorAvailable()) throw new SymphonyErrorPage(__('No suitable XSLT processor was found.'));

			$this->_registered_php_functions = array();
		}
		
		public function setXML($xml, $file=false){
			$this->_xml = ($file ? @file_get_contents($xml) : $xml);
		}
		
		public function setXSL($xsl, $file=false){
			$this->_xsl = ($file ? @file_get_contents($xsl) : $xsl);
		}
		
		public function getXML(){
			return $this->_xml;
		}
		
		public function getXSL(){
			return $this->_xsl;
		}
		
		public function setRuntimeParam($param){
			$this->_parameters = str_replace("'", "&apos;", $param);
		}
		
		public function getError(){
			return $this->Proc->getError();
		}
		
		public function registerPHPFunction($function){
			if(!is_array($this->_registered_php_functions)) $this->_registered_php_functions = array();
				
			if(is_array($function)) $this->_registered_php_functions += $function;
			else $this->_registered_php_functions[] = $function;
		}
		
		public function generate(){
			
			$result = XSLProc::transform($this->_xml, $this->_xsl, XSLProc::XML, $this->_parameters, $this->_registered_php_functions);
			
			if(XSLProc::hasErrors()) return false;
			
			//$result = $this->Proc->process($this->_xml, $this->_xsl, $this->_param, $this->_registered_php_functions);	
			
			//if($this->Proc->isErrors()) return false;
			
			parent::generate();
			
			return $result;
		}
			
	}
