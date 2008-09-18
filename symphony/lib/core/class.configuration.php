<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	Class Configuration {
		
		private $_vars = array();
		private $_forceLowerCase = false;
		
		const CRLF = "\r\n";
		
		function __construct($forceLowerCase = false){
			$this->_forceLowerCase = $forceLowerCase;
		}
		
		public function flush(){
			$this->_vars = array();	
		}
		
		public function get($name=NULL, $index=NULL) {
			
			## Return the whole array if no name or index is requested
			if(!$name && !$index) return $this->_vars;
			
			if($this->_forceLowerCase){ $name = strtolower($name); $index = strtolower($index); }
					
			if($index) return $this->_vars[$index][$name];
				
			return $this->_vars[$name];
		}
		
		public function set($name, $val, $index=NULL) {
			
			if($this->_forceLowerCase) { $name = strtolower($name); $index = strtolower($index); }
			
			if($index){
				$this->_vars[$index][$name] = $val;
				
			}else{
				$this->_vars[$name] = $val;
			}
		}
		
		public function remove($name, $index=NULL){
			
			if($this->_forceLowerCase) { $name = strtolower($name); $index = strtolower($index); }
			
			if($index && isset($this->_vars[$index][$name]))
				unset($this->_vars[$index][$name]);
				
			elseif($this->_vars[$name])
				unset($this->_vars[$name]);
				
					
		}
				
		public function setArray($arr){
			$this->_vars = array_merge($this->_vars, $arr);
		}
		
		public function create(){
			
			$data = NULL;

			foreach($this->_vars as $set => $array) {
				
				if(is_array($array) && !empty($array)){
					foreach($array as $key => $val) {
						$data .= "\t" . '$'."settings['$set']['$key'] = '".addslashes($val)."';" . self::CRLF;
					}
				}
			}

			return (empty($data) ? false : $data);
		}
		
	}

