<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	/****************
	XMLParser
	
	Adaptation of class by "monte at NOT-SP-AM dot ohrt dot com"
	http://au2.php.net/xml
	
	Modified by Alistair Kearney <alistair@21degrees.com.au>
	18 November 2005 
	
	*****************/
	
	Class XMLParser {
		
		var $_filename;
		var $_xml;
		var $_data;
		
		function XMLParser(){
			$this->_filename = NULL;
			$this->_xml = NULL;
			$this->_data = array();			
		}
		
		function parseFromFile($xml_file){
			$this->_filename = $xml_file; 
			return $this->__parse();
		}
		
		function parseFromString($string){
		   	$this->_filename = TMP . '/' . md5(time());
		   	if(!@file_put_contents($this->_filename, $string)) return false; 		   
		   	$ret = $this->__parse();
		   	unlink($this->_filename);
			return $ret;
		}           
		
		function getData(){
			return $this->_data;
		}
		
		function __parse(){

			$this->_parser = xml_parser_create();
			xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, 0);
			xml_set_object($this->_parser, $this);
			xml_set_element_handler($this->_parser, '__startHandler', '__endHandler');
			xml_set_character_data_handler($this->_parser, '__dataHandler');

			$bytes_to_parse = 512;		   

			if(!($fp = @fopen($this->_filename, 'r'))) {
				$this->__error('Cannot open XML data file: '.$this->_filename);
				return false;
			}

			while ($data = fread($fp, $bytes_to_parse)) {
			    if(!$parse = @xml_parse($this->_parser, $data, feof($fp))) return false;
			}	       
			
			
			return true;
		}
		
		function __startHandler($parser, $name, $attributes){
			$data[$name] = array();
			if($attributes) $data[$name]['attributes'] = $attributes;
			$this->_data[] = $data;
		}
	
		function __dataHandler($parser, $data){
			if($data = trim($data)){
				$index = count($this->_data) -1;
				$node = $this->__getNodeFromIndex($index);
				
				if(isset($this->_data[$index][$node][0]))			
					$this->_data[$index][$node][0] .= $data;
				else
					$this->_data[$index][$node][] = $data;
			}
		}
		
		function __endHandler($parser, $name){
			if(count($this->_data) > 1) {
		       	$data = array_pop($this->_data);
		       	$index = count($this->_data) - 1;
		       	$node = $this->__getNodeFromIndex($index);
		       
				$this->_data[$index][$node][] = $data;
			}
		}
		
		function __getNodeFromIndex($index){
			$keys = array_keys($this->_data[$index]);
			$node = end($keys);			
			return $node;
		}
		
		function __error($error) {
			trigger_error($error, E_USER_ERROR);
		}		
	}
	
	Class XmlDoc{
	
		var $_parser;
		var $_data;
	 	var $_array;             
		
		function __construct($args=NULL){
				
			$this->_parser =& new XMLParser;
		
			if($args['data'])
				$this->parseString($args['data']);
				
	     	if($args['file'])
	     		$this->parseFile($args['file']);
		}
		
		function __destruct(){
			unset($this->_parser);
		}
		
		function parseFile($file){	
	     	if(!is_readable($file)) return false;			
	     	$this->__flush();	     		
	     	return $this->_parser->parseFromFile($file);
		}
		
		function parseString($data){			
	     	$this->__flush();		     		
			return $this->_parser->parseFromString($data);			
		}
		
		function __flush(){
	     	$this->_array = array();
		}
		
		function getArray($return_root = true, $strip_cdata=true){
			
			$arr = $this->_parser->getData();

			if($return_root) $arr = $arr[0];		
				
			//For php4 users this will deal with an annoying bug which
			//prevents the CDATA tags from being stripped. Bit of a hack
			//but does the trick
			if($strip_cdata){
				$data = serialize($arr);
				$data = str_replace("<![CDATA[", "         ", $data); //<-- replaced string MUST be same length
				$data = str_replace("]]>", "   ", $data); //<-- replacement string MUST be same length
				$arr = unserialize($data);	
			}			
			return $arr;	
		}
	
		function __error($error) {
			trigger_error($error, E_USER_ERROR);
		}	
	
	}
