<?php

	

	Class ParseXML{

		var $_parser;
		var $_data;                 
		var $_lastTag;
		var $_output;
	
		function __construct($args=NULL){
	
			if($args['data'])
				$this->parseString($args['data']);
			
	     	if($args['file'])
	     		$this->parseFile($args['filename']);			
		
		}
	
		function __destruct(){
		
		}
	
		function parseFile($file){
     		
	     	if(!is_readable($file)) $this->__error(__("Can't open file %s", array($file)));
     			
	     	$this->_flush();
     		
			$this->_data = file_get_contents($file);
		
			return $this->__parse();
		}
	
		function parseString($data){
		
	     	$this->_flush();	
     		
			$this->_data = $data;
		
			return $this->__parse();
		
		}
	
		function __parse() {
			$this->_index = array();
			$parser = xml_parser_create();
		
			xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
			xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		
			xml_set_object($parser, $this);
			xml_set_element_handler($parser, "__parseXMLopenTag", "__parseXMLcloseTag");
			xml_set_character_data_handler($parser, "__parseXMLcdata");
		
			$this->_data = eregi_replace('>' . "[[:space:]]+" . '<' , '><', $this->_data);
		
			$result = xml_parse($parser, $this->_data);
		
			//print $this->_data;
			//print_r($this->_output);
			//print_r($this->_index);
			//die();
		
			$this->errorCode = xml_get_error_code($parser);
			$this->errorString = xml_error_string($this->errorCode);
		
			xml_parser_free($parser);	
		
			return $result;
		
		}
	
		function __parseXMLopenTag($parser, $tag, $attributes) {
			$tag = trim($tag);
			if(!empty($tag)) {
			
				$this->_lastTag = $tag;
			
				if(empty($this->_index[$tag])){
					$this->_index[$tag] = 0;
				}	
			
				if(isset($attributes) && !empty($attributes))
					$this->_output[$this->_lastTag][$this->_index[$tag]]["attributes"] = $attributes;
			
			}
		}
	
		function __parseXMLcdata($parser, $cdata) {
			$cdata = trim($cdata);

			if(!empty($cdata)) {
				$index = $this->_index[$this->_lastTag];
				$this->_output[$this->_lastTag][$index] .= $cdata;
			}
		}
	
		function __parseXMLcloseTag($parser, $tag) {
			$this->_index[$tag]++;
		}

		
		function _flush(){
			$this->_data = NULL; 
	     	$this->_output = array(); 				
		}
	
		function getArray(){
			return $this->_output;	
		}

		function __error($error) {
			trigger_error($error, E_USER_ERROR);
		}	

	}
