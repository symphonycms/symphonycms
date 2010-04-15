<?php
	
	require_once(TOOLKIT . '/class.messagestack.php');
	
	Class XMLDocument extends DOMDocument{
		
		private $errors;
		
		public function __construct($version='1.0', $encoding='utf-8'){
			parent::__construct($version, $encoding);
			$this->preserveWhitespace = false;
			$this->formatOutput = false;
			$this->errors = new MessageStack;
		}
	
		public function xpath($query){
			$xpath = new DOMXPath($this);
			return $xpath->query($query);
		}
		
		public function flush(){
			$this->errors->empty();
		}
		
		public function loadXML($xml){
			
			$this->flushLog();
			
			libxml_use_internal_errors(true);

			$result = parent::loadXML($xml);

			self::processLibXMLerrors();
			
			return $result;
		}
		
		static public function setAttributeArray(DOMElement $obj, array $attr){

			if(empty($attr)) return;

			foreach($attr as $key => $val)
				$obj->setAttribute($key, $val);

		}
		
		static private function processLibXMLerrors(){
			if(!is_array(self::$_errorLog)) self::$_errorLog = array();

			foreach(libxml_get_errors() as $error){
				$error->type = $type;
				$this->errors->append(NULL, $error);
			}

			libxml_clear_errors();
		}
		
		public function hasErrors(){
			return (bool)($this->errors instanceof MessageStack && $this->errors->valid());
		}
		
		public function getErrors(){
			return $this->errors;
		}
			
	}