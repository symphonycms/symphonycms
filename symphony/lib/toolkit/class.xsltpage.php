<?php

	require_once(TOOLKIT . '/class.page.php');
	require_once(TOOLKIT . '/class.xsltprocess.php');

	Class XSLTPage extends Page{
		
		protected $_xml;
		protected $_xsl;
		protected $_param;
		public $Proc;
		
		function __construct(){
			
			if(!XsltProcess::isXSLTProcessorAvailable()) trigger_error(__('No suitable XSLT processor was found.'), E_USER_ERROR);
			
			$this->Proc =& new XsltProcess;
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
			$this->_param = $param;
		}
		
		public function getError(){
			return $this->Proc->getError();
		}
		
		public function generate(){
						
			$result = $this->Proc->process($this->_xml, $this->_xsl, $this->_param);	
			
			if($this->Proc->isErrors()) return false;
			
			parent::generate();
			
			return $result;
		}
			
	}


