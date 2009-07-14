<?php

	static $processErrors = array();
   
	function trapXMLError($errno, $errstr, $errfile, $errline, $errcontext, $ret=false){
		
		global $processErrors;
		
		if($ret === true) return $processErrors;
		
		$tag = 'DOMDocument::';
		$processErrors[] = array('type' => 'xml', 'number' => $errno, 'message' => str_replace($tag, '', $errstr), 'file' => $errfile, 'line' => $errline);
	}
	
	function trapXSLError($errno, $errstr, $errfile, $errline, $errcontext, $ret=false){
		
		global $processErrors;
		
		if($ret === true) return $processErrors;
		
		$tag = 'DOMDocument::';
		$processErrors[] = array('type' => 'xsl', 'number' => $errno, 'message' => str_replace($tag, '', $errstr), 'file' => $errfile, 'line' => $errline);
	}	

	Class XsltProcess{
	
		private $_xml;
		private $_xsl;
		private $_errors;
		
		function __construct($xml=null, $xsl=null){
			
			if(!self::isXSLTProcessorAvailable()) return false;
			
			$this->_xml = $xml;
			$this->_xsl = $xsl;
			
			$this->_errors = array();
			
			return true;
			
		}
		
		public static function isXSLTProcessorAvailable(){
			return (class_exists('XsltProcessor') || function_exists('xslt_process'));
		}
		
		private function __process($xsltproc, $xml_arg, $xsl_arg, $xslcontainer = null, $args = null, $params = null) {
		                         
			// Start with preparing the arguments
			$xml_arg = str_replace('arg:', '', $xml_arg);
			$xsl_arg = str_replace('arg:', '', $xsl_arg);
			
			// Create instances of the DomDocument class
			$xml = new DomDocument;
			$xsl = new DomDocument;	     
			 
			// Set up error handling					
			if(function_exists('ini_set')){
				$ehOLD = ini_set('html_errors', false);
			}	
				
			// Load the xml document
			set_error_handler('trapXMLError');	
			$xml->loadXML($args[$xml_arg]);
			
			// Must restore the error handler to avoid problems
			restore_error_handler();
			
			// Load the xml document
			set_error_handler('trapXSLError');	
			$xsl->loadXML($args[$xsl_arg]);

			// Load the xsl template
			$xsltproc->importStyleSheet($xsl);
			
			// Set parameters when defined
			if ($params) {
				General::flattenArray($params);
				
				foreach ($params as $param => $value) {
					$xsltproc->setParameter('', $param, $value);
				}
			}
			
			restore_error_handler();
			
			// Start the transformation
			set_error_handler('trapXMLError');	
			$processed = $xsltproc->transformToXML($xml);

			// Restore error handling
			if(function_exists('ini_set') && isset($ehOLD)){
				ini_set('html_errors', $ehOLD);
			}
			
			restore_error_handler();	
				
			// Put the result in a file when specified
			if($xslcontainer) return @file_put_contents($xslcontainer, $processed);	
			else return $processed;
			
		}	
		
		public function process($xml=null, $xsl=null, $param=array()){

			global $processErrors;
			
			$processErrors = array();
			
			if($xml) $this->_xml = $xml;
			if($xsl) $this->_xsl = $xsl;
			
			$xml = trim($xml);
			$xsl = trim($xsl);
			
			if(!is_array($param)) $param = array();
			
			if(!self::isXSLTProcessorAvailable()) return false; //dont let process continue if no xsl functionality exists
			
			$arguments = array(
		   		'/_xml' => $this->_xml,
		   		'/_xsl' => $this->_xsl
			);
			
			$xsltproc = new XsltProcessor();
				
			$result = @$this->__process(
			   $xsltproc,
			   'arg:/_xml',
			   'arg:/_xsl',
			   null,
			   $arguments,
			   $param
			);	
				
			while($error = @array_shift($processErrors)) $this->__error($error['number'], $error['message'], $error['type'], $error['line']);
			
			unset($xsltproc);
			
			return $result;		
		}
		
		private function __error($number, $message, $type=NULL, $line=NULL){
			
			$context = NULL;
			
			if($type == 'xml') $context = $this->_xml;
			if($type == 'xsl') $context = $this->_xsl;
			
			$this->_errors[] = array(
									'number' => $number, 
									'message' => $message, 
									'type' => $type, 
									'line' => $line,
									'context' => $context);		
		}
		
		public function isErrors(){		
			return (!empty($this->_errors) ? true : false);				
		}
		
		public function getError($all=false, $rewind=false){
			if($rewind) reset($this->_errors);
			return ($all ? $this->_errors : each($this->_errors));				
		}
		
	}

