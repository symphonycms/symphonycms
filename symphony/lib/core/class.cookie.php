<?php

	Class Cookie{
		
		private $_index;
		private $_timeout;
		private $_path;
		private $_domain;		
		
		function __construct($index, $timeout, $path, $domain=NULL){
			$this->_index = $index;
			$this->_timeout = $timeout;
			$this->_path = $path;
			$this->_domain = ($domain ? $domain : $this->__getDomain());				
		}
		
		public function set($name, $value){
			setcookie($this->_index . "[$name]", $value, time() + $this->_timeout, $this->_path);

		}
		
		public function get($name){
			return $_COOKIE[$this->_index][$name];
		}
		
		public function expire(){		
			if(!is_array($_COOKIE[$this->_index]) || empty($_COOKIE[$this->_index])) return;

			foreach($_COOKIE[$this->_index] as $name => $val){
				setcookie($this->_index . "[$name]", ' ', time() - $this->_timeout, $this->_path);
			}

		}
		
		private function __getDomain() {
			
			if(isset($_SERVER['HTTP_HOST'])){

				$dom = $_SERVER['HTTP_HOST'];

				if(strtolower(substr($dom, 0, 4)) == 'www.') $dom = substr($dom, 4);

				$uses_port = strpos($dom, ':');
				if($uses_port) $dom = substr($dom, 0, $uses_port);

				$dom = '.' . $dom;

				return $dom; 
			} 

			return false;
		    
		}
	
	}

?>